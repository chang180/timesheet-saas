import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import {
    buildHolidayMap,
    calculateWorkRangeStats,
    getHolidayMarkerDates,
} from '@/lib/holiday-utils';
import tenantRoutes from '@/routes/tenant';
import * as weeklyRoutes from '@/routes/tenant/weekly-reports';
import { type BreadcrumbItem, type SharedData } from '@/types';
import {
    closestCenter,
    DndContext,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,
    type DragEndEvent,
} from '@dnd-kit/core';
import {
    arrayMove,
    SortableContext,
    sortableKeyboardCoordinates,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowLeft,
    CheckCircle2,
    Clock,
    ExternalLink,
    Lock,
    PlusCircle,
    RotateCcw,
    type LucideIcon,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { SortableCurrentWeekRow } from './components/sortable-current-week-row';
import { SortableNextWeekRow } from './components/sortable-next-week-row';
import type { HolidayEntry, WeeklyReportItemInput } from './components/types';

interface WeeklyReportFormProps {
    mode: 'create' | 'edit';
    report: {
        id: number;
        workYear: number;
        workWeek: number;
        status: string;
        summary: string | null;
        currentWeek: Omit<WeeklyReportItemInput, 'localKey' | 'tagsText'>[];
        nextWeek: Omit<WeeklyReportItemInput, 'localKey' | 'tagsText'>[];
    } | null;
    company?: {
        id: number;
        slug: string;
        name: string;
    };
    defaults: {
        year: number;
        week: number;
    };
    weekDateRange?: {
        startDate: string;
        endDate: string;
    };
    nextWeekDateRange?: {
        startDate: string;
        endDate: string;
    };
    holidayCalendar?: {
        currentWeek: {
            year: number;
            week: number;
            holidays: HolidayEntry[];
        };
        nextWeek: {
            year: number;
            week: number;
            holidays: HolidayEntry[];
        };
        source: {
            name: string;
            dataset_url: string;
            api_url: string;
            provider: string;
        };
    };
    prefill: {
        currentWeek: Omit<WeeklyReportItemInput, 'localKey' | 'tagsText'>[];
        nextWeek: Omit<WeeklyReportItemInput, 'localKey' | 'tagsText'>[];
    };
    canReopen?: boolean;
}

type FormData = {
    work_year: number;
    work_week: number;
    summary: string;
    current_week: WeeklyReportItemInput[];
    next_week: WeeklyReportItemInput[];
};

const STATUS_CONFIG: Record<
    string,
    { text: string; icon: LucideIcon; className: string }
> = {
    draft: {
        text: '草稿',
        icon: Clock,
        className:
            'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20',
    },
    submitted: {
        text: '已送出',
        icon: CheckCircle2,
        className:
            'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20',
    },
    locked: {
        text: '已鎖定',
        icon: Lock,
        className:
            'bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-500/10 dark:text-slate-400 dark:border-slate-500/20',
    },
};

const toFormItem = (
    item: Omit<WeeklyReportItemInput, 'localKey' | 'tagsText'>,
    prefix: string,
    index: number,
    defaults: { hours_spent?: number; planned_hours?: number | null } = {},
): WeeklyReportItemInput => {
    // 確保 hours_spent 和 planned_hours 始終是數字類型，避免後端返回字符串時導致計算錯誤
    const hoursSpent = item.hours_spent ?? defaults.hours_spent ?? 0;
    const plannedHours = item.planned_hours ?? defaults.planned_hours ?? null;

    // 處理日期：如果後端返回的是 ISO 字符串，轉換為 YYYY-MM-DD 格式
    const formatDate = (date: string | null | undefined): string | null => {
        if (!date) {
            return null;
        }
        if (typeof date === 'string') {
            // 如果是 ISO 格式，提取日期部分
            return date.split('T')[0];
        }
        return null;
    };

    return {
        id: item.id,
        localKey: `${prefix}-${item.id ?? `new-${index}`}-${Date.now()}`,
        title: item.title ?? '',
        content: item.content ?? '',
        hours_spent:
            typeof hoursSpent === 'number'
                ? hoursSpent
                : Number(hoursSpent) || 0,
        planned_hours:
            plannedHours === null || plannedHours === undefined
                ? null
                : typeof plannedHours === 'number'
                  ? plannedHours
                  : Number(plannedHours) || null,
        issue_reference: item.issue_reference ?? '',
        is_billable: item.is_billable ?? false,
        tags: item.tags ?? [],
        tagsText: (item.tags ?? []).join(', '),
        started_at: formatDate(item.started_at),
        ended_at: formatDate(item.ended_at),
    };
};

export default function WeeklyReportForm({
    mode,
    report,
    defaults,
    weekDateRange,
    nextWeekDateRange,
    holidayCalendar,
    prefill,
    company,
    canReopen,
}: WeeklyReportFormProps) {
    const { tenant, flash } = usePage<
        SharedData & {
            flash: { success?: string; info?: string; warning?: string };
        }
    >().props;

    const sharedCompanySlug = (tenant?.company as { slug?: string } | undefined)
        ?.slug;
    const companySlug = company?.slug ?? sharedCompanySlug ?? '';
    const canNavigate = companySlug.length > 0;
    const isCreate = mode === 'create';

    const initialData = {
        work_year: report?.workYear ?? defaults.year,
        work_week: report?.workWeek ?? defaults.week,
        summary: report?.summary ?? '',
        current_week: (report?.currentWeek ?? prefill.currentWeek ?? []).map(
            (item, index) => toFormItem(item, 'current', index),
        ),
        next_week: (report?.nextWeek ?? prefill.nextWeek ?? []).map(
            (item, index) =>
                toFormItem(item, 'next', index, {
                    planned_hours: item.planned_hours ?? null,
                }),
        ),
    };

    const form = useForm<FormData>(initialData);
    const currentWeekHolidayMap = buildHolidayMap(
        holidayCalendar?.currentWeek.holidays ?? [],
    );
    const nextWeekHolidayMap = buildHolidayMap(
        holidayCalendar?.nextWeek.holidays ?? [],
    );
    const currentWeekHolidayMarkers = getHolidayMarkerDates(
        currentWeekHolidayMap,
    );
    const nextWeekHolidayMarkers = getHolidayMarkerDates(nextWeekHolidayMap);

    const formTopRef = useRef<HTMLDivElement>(null);
    const [showNoChangesMessage, setShowNoChangesMessage] = useState(false);

    // 當有表單錯誤時，自動捲動到頂部
    useEffect(() => {
        if (Object.keys(form.errors).length > 0 && formTopRef.current) {
            formTopRef.current.scrollIntoView({
                behavior: 'smooth',
                block: 'start',
            });
        }
    }, [form.errors]);

    const scrollToTop = () => {
        if (formTopRef.current) {
            formTopRef.current.scrollIntoView({
                behavior: 'smooth',
                block: 'start',
            });
        }
    };

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    const handleCurrentWeekDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;

        if (over && active.id !== over.id) {
            const oldIndex = form.data.current_week.findIndex(
                (item) => item.localKey === active.id,
            );
            const newIndex = form.data.current_week.findIndex(
                (item) => item.localKey === over.id,
            );

            if (oldIndex !== -1 && newIndex !== -1) {
                form.setData(
                    'current_week',
                    arrayMove(form.data.current_week, oldIndex, newIndex),
                );
            }
        }
    };

    const handleNextWeekDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;

        if (over && active.id !== over.id) {
            const oldIndex = form.data.next_week.findIndex(
                (item) => item.localKey === active.id,
            );
            const newIndex = form.data.next_week.findIndex(
                (item) => item.localKey === over.id,
            );

            if (oldIndex !== -1 && newIndex !== -1) {
                form.setData(
                    'next_week',
                    arrayMove(form.data.next_week, oldIndex, newIndex),
                );
            }
        }
    };

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: '週報工作簿',
            href: canNavigate
                ? tenantRoutes.weeklyReports.url({ company: companySlug })
                : '#',
        },
        {
            title: isCreate ? '建立週報' : '編輯週報',
            href: '#',
        },
    ];

    const parseTags = (input?: string, fallback: string[] = []): string[] => {
        if (!input) {
            return fallback;
        }

        return input
            .split(',')
            .map((tag) => tag.trim())
            .filter((tag) => tag.length > 0);
    };

    const addCurrentItem = () => {
        form.setData('current_week', [
            ...form.data.current_week,
            {
                id: undefined, // 明確設為 undefined，表示這是新項目
                localKey: `current-new-${Date.now()}`,
                title: '',
                content: '',
                hours_spent: 0,
                planned_hours: null,
                issue_reference: '',
                is_billable: false,
                tags: [],
                tagsText: '',
                started_at: null,
                ended_at: null,
            },
        ]);
    };

    const addNextItem = () => {
        form.setData('next_week', [
            ...form.data.next_week,
            {
                id: undefined, // 明確設為 undefined，表示這是新項目
                localKey: `next-new-${Date.now()}`,
                title: '',
                content: '',
                hours_spent: 0,
                planned_hours: null,
                issue_reference: '',
                tags: [],
                tagsText: '',
                started_at: null,
                ended_at: null,
            },
        ]);
    };

    const removeItem = (type: 'current_week' | 'next_week', index: number) => {
        const items = [...form.data[type]];
        items.splice(index, 1);
        form.setData(type, items);
    };

    const moveItem = (
        type: 'current_week' | 'next_week',
        index: number,
        direction: 'up' | 'down',
    ) => {
        const items = form.data[type];
        const newIndex = direction === 'up' ? index - 1 : index + 1;

        if (newIndex < 0 || newIndex >= items.length) {
            return;
        }

        // Use arrayMove for smooth animation
        const reorderedItems = arrayMove(items, index, newIndex);
        form.setData(type, reorderedItems);
    };

    const updateItem = (
        type: 'current_week' | 'next_week',
        index: number,
        key: keyof WeeklyReportItemInput,
        value: unknown,
    ) => {
        const items = form.data[type].map((item, idx) =>
            idx === index ? { ...item, [key]: value } : item,
        );
        form.setData(type, items);
    };

    // 檢查表單是否有變更
    const hasChanges = (): boolean => {
        // 比較基本欄位
        if (form.data.work_year !== initialData.work_year) return true;
        if (form.data.work_week !== initialData.work_week) return true;
        if (form.data.summary !== initialData.summary) return true;

        // 比較本週項目數量
        if (form.data.current_week.length !== initialData.current_week.length)
            return true;

        // 比較本週項目內容
        for (let i = 0; i < form.data.current_week.length; i++) {
            const current = form.data.current_week[i];
            const initial = initialData.current_week[i];

            if (!initial) return true;

            if (current.title !== initial.title) return true;
            if (current.content !== initial.content) return true;
            if (current.hours_spent !== initial.hours_spent) return true;
            if (current.planned_hours !== initial.planned_hours) return true;
            if (current.issue_reference !== initial.issue_reference)
                return true;
            if (current.is_billable !== initial.is_billable) return true;
            if (current.tagsText !== initial.tagsText) return true;
            if (current.started_at !== initial.started_at) return true;
            if (current.ended_at !== initial.ended_at) return true;
        }

        // 比較下週項目數量
        if (form.data.next_week.length !== initialData.next_week.length)
            return true;

        // 比較下週項目內容
        for (let i = 0; i < form.data.next_week.length; i++) {
            const current = form.data.next_week[i];
            const initial = initialData.next_week[i];

            if (!initial) return true;

            if (current.title !== initial.title) return true;
            if (current.content !== initial.content) return true;
            if (current.planned_hours !== initial.planned_hours) return true;
            if (current.issue_reference !== initial.issue_reference)
                return true;
            if (current.tagsText !== initial.tagsText) return true;
            if (current.started_at !== initial.started_at) return true;
            if (current.ended_at !== initial.ended_at) return true;
        }

        return false;
    };

    const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        // 檢查是否有變更
        if (!hasChanges()) {
            setShowNoChangesMessage(true);
            // 3秒後自動隱藏訊息
            setTimeout(() => setShowNoChangesMessage(false), 3000);
            return;
        }

        // 有變更時隱藏「沒有變更」的訊息
        setShowNoChangesMessage(false);

        form.transform((data) => ({
            work_year: data.work_year,
            work_week: data.work_week,
            summary: data.summary,
            current_week: data.current_week.map((item) => ({
                // 只有當 id 存在且為有效數字時才包含，避免發送 undefined 導致驗證失敗
                ...(item.id && typeof item.id === 'number'
                    ? { id: item.id }
                    : {}),
                title: item.title.trim(),
                content: item.content?.trim() || null,
                hours_spent: Number(item.hours_spent) || 0,
                planned_hours:
                    item.planned_hours === null ||
                    item.planned_hours === undefined
                        ? null
                        : Number(item.planned_hours),
                issue_reference: item.issue_reference?.trim() || null,
                is_billable: Boolean(item.is_billable),
                tags: parseTags(item.tagsText, item.tags),
                started_at: item.started_at || null,
                ended_at: item.ended_at || null,
            })),
            next_week: data.next_week.map((item) => ({
                // 只有當 id 存在且為有效數字時才包含，避免發送 undefined 導致驗證失敗
                ...(item.id && typeof item.id === 'number'
                    ? { id: item.id }
                    : {}),
                title: item.title.trim(),
                content: item.content?.trim() || null,
                planned_hours:
                    item.planned_hours === null ||
                    item.planned_hours === undefined
                        ? null
                        : Number(item.planned_hours),
                issue_reference: item.issue_reference?.trim() || null,
                tags: parseTags(item.tagsText, item.tags),
                started_at: item.started_at || null,
                ended_at: item.ended_at || null,
            })),
        }));

        const resetTransform = () => form.transform((data) => data);
        const options = {
            preserveScroll: true,
            onFinish: resetTransform,
            onSuccess: () => {
                // 清除「沒有變更」的訊息
                setShowNoChangesMessage(false);
            },
        } as const;

        if (!canNavigate) {
            return;
        }

        if (isCreate) {
            form.post(
                weeklyRoutes.store.url({ company: companySlug }),
                options,
            );
        } else if (report) {
            form.put(
                weeklyRoutes.update.url({
                    company: companySlug,
                    weeklyReport: report.id,
                }),
                options,
            );
        }
    };

    const getError = (path: string): string | undefined => {
        const errors = form.errors as Record<string, string>;
        return errors[path];
    };

    // 計算工時合計
    // hours_spent 和 planned_hours 在表單狀態中已經是數字類型
    const totalCurrentWeekHours = form.data.current_week.reduce(
        (sum, item) => sum + (item.hours_spent || 0),
        0,
    );
    const totalCurrentWeekItemPlannedHours = form.data.current_week.reduce(
        (sum, item) => sum + (item.planned_hours || 0),
        0,
    );
    const totalNextWeekHours = form.data.next_week.reduce(
        (sum, item) => sum + (item.planned_hours || 0),
        0,
    );
    const currentWeekCapacity = weekDateRange
        ? calculateWorkRangeStats(
              weekDateRange.startDate,
              weekDateRange.endDate,
              currentWeekHolidayMap,
          )
        : null;
    const nextWeekCapacity = nextWeekDateRange
        ? calculateWorkRangeStats(
              nextWeekDateRange.startDate,
              nextWeekDateRange.endDate,
              nextWeekHolidayMap,
          )
        : null;
    const currentWeekTargetHours = currentWeekCapacity?.availableHours ?? 40;
    const nextWeekTargetHours = nextWeekCapacity?.availableHours ?? 40;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={isCreate ? '建立週報' : '編輯週報'} />

            <form onSubmit={handleSubmit} className="space-y-6 md:space-y-8">
                {/* 用於捲動的錨點 */}
                <div ref={formTopRef} className="absolute -top-20" />

                <div className="flex flex-col gap-4 border-b border-border/60 pb-6 md:flex-row md:items-center">
                    {canNavigate ? (
                        <Button
                            asChild
                            variant="ghost"
                            size="sm"
                            className="gap-2 self-start"
                        >
                            <Link
                                href={tenantRoutes.weeklyReports.url({
                                    company: companySlug,
                                })}
                            >
                                <ArrowLeft className="size-4" />
                                <span className="hidden sm:inline">
                                    返回列表
                                </span>
                                <span className="sm:hidden">返回</span>
                            </Link>
                        </Button>
                    ) : (
                        <Button
                            variant="ghost"
                            size="sm"
                            disabled
                            className="gap-2 self-start"
                        >
                            <ArrowLeft className="size-4" />
                            <span className="hidden sm:inline">返回列表</span>
                            <span className="sm:hidden">返回</span>
                        </Button>
                    )}
                    <div className="flex-1">
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
                            <h1 className="text-xl font-bold text-foreground sm:text-2xl md:text-3xl">
                                {isCreate ? '建立週報草稿' : '編輯週報'}
                            </h1>
                            {report &&
                                (() => {
                                    const statusConfig = STATUS_CONFIG[
                                        report.status
                                    ] ?? {
                                        text: report.status,
                                        icon: Clock,
                                        className:
                                            'bg-muted text-muted-foreground border-border',
                                    };
                                    const StatusIcon = statusConfig.icon;
                                    return (
                                        <span
                                            className={`inline-flex w-fit items-center gap-1.5 rounded-full border-2 px-3.5 py-1.5 text-xs font-semibold shadow-sm ${statusConfig.className}`}
                                        >
                                            <StatusIcon className="size-3.5" />
                                            {statusConfig.text}
                                        </span>
                                    );
                                })()}
                        </div>
                        <p className="mt-2 text-sm text-muted-foreground md:mt-3 md:text-base">
                            {form.data.work_year} 年第 {form.data.work_week} 週
                            {weekDateRange && (
                                <span className="text-muted-foreground">
                                    {' '}
                                    <span className="hidden sm:inline">
                                        ({weekDateRange.startDate} ~{' '}
                                        {weekDateRange.endDate})
                                    </span>
                                    <span className="sm:hidden">
                                        ({weekDateRange.startDate.substring(5)}{' '}
                                        ~ {weekDateRange.endDate.substring(5)})
                                    </span>
                                </span>
                            )}
                        </p>
                    </div>
                </div>

                {isCreate && prefill.currentWeek.length > 0 && (
                    <div className="rounded-lg border-2 border-indigo-200 bg-linear-to-r from-indigo-50 to-indigo-100/50 p-4 shadow-sm dark:border-indigo-500/30 dark:from-indigo-500/10 dark:to-indigo-500/5">
                        <p className="text-sm font-semibold text-indigo-900 dark:text-indigo-100">
                            已帶入上一週的「下週預計」項目，記得調整內容與工時。
                        </p>
                    </div>
                )}

                {holidayCalendar && (
                    <div className="rounded-xl border-2 border-slate-200 bg-linear-to-r from-slate-50 to-white p-4 shadow-sm dark:border-slate-700 dark:from-slate-900 dark:to-slate-950">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <p className="text-sm font-semibold text-foreground">
                                    假日資料已串接政府行事曆
                                </p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    來源：{holidayCalendar.source.provider}／
                                    {holidayCalendar.source.name}
                                </p>
                            </div>
                            <div className="flex flex-wrap items-center gap-2 text-xs font-medium">
                                <span className="rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-rose-700 dark:border-rose-800 dark:bg-rose-950/30 dark:text-rose-300">
                                    紅色：假日
                                </span>
                                <span className="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300">
                                    綠色：補班日
                                </span>
                                <a
                                    href={holidayCalendar.source.dataset_url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center gap-1 rounded-full border border-slate-300 px-3 py-1 text-slate-700 transition-colors hover:border-slate-400 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
                                >
                                    查看資料來源
                                    <ExternalLink className="size-3.5" />
                                </a>
                            </div>
                        </div>
                    </div>
                )}

                <Card className="border-2 border-border/60 shadow-md">
                    <CardHeader className="border-b-2 border-border/60 bg-linear-to-r from-muted/50 to-muted/30 pb-5">
                        <CardTitle className="text-xl font-bold text-foreground sm:text-2xl">
                            本週完成事項
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="pt-6">
                        {form.data.current_week.length === 0 ? (
                            <div className="rounded-xl border-2 border-dashed border-border/50 bg-linear-to-br from-muted/30 to-muted/10 p-12 text-center">
                                <p className="mb-6 text-sm font-medium text-muted-foreground sm:text-base">
                                    目前沒有任何項目，點擊下方按鈕開始紀錄
                                </p>
                                <Button
                                    type="button"
                                    size="lg"
                                    variant="default"
                                    onClick={addCurrentItem}
                                    className="gap-2 shadow-md transition-all hover:shadow-lg"
                                >
                                    <PlusCircle className="size-5" />
                                    新增本週完成項目
                                </Button>
                            </div>
                        ) : (
                            <>
                                <DndContext
                                    sensors={sensors}
                                    collisionDetection={closestCenter}
                                    onDragEnd={handleCurrentWeekDragEnd}
                                >
                                    <SortableContext
                                        items={form.data.current_week.map(
                                            (item) => item.localKey,
                                        )}
                                        strategy={verticalListSortingStrategy}
                                    >
                                        {form.data.current_week.map(
                                            (item, index) => (
                                                <SortableCurrentWeekRow
                                                    key={item.localKey}
                                                    item={item}
                                                    index={index}
                                                    totalItems={
                                                        form.data.current_week
                                                            .length
                                                    }
                                                    updateItem={updateItem}
                                                    removeItem={removeItem}
                                                    moveItem={moveItem}
                                                    getError={getError}
                                                    weekDateRange={
                                                        weekDateRange
                                                    }
                                                    holidayMap={
                                                        currentWeekHolidayMap
                                                    }
                                                    holidayDates={
                                                        currentWeekHolidayMarkers.holidayDates
                                                    }
                                                    workdayOverrideDates={
                                                        currentWeekHolidayMarkers.workdayOverrideDates
                                                    }
                                                />
                                            ),
                                        )}
                                    </SortableContext>
                                </DndContext>

                                {/* 新增按鈕移到底部 */}
                                <div className="mt-6 flex flex-col gap-4">
                                    <Button
                                        type="button"
                                        size="lg"
                                        variant="outline"
                                        onClick={addCurrentItem}
                                        className="w-full gap-2 border-2 border-dashed border-primary/30 bg-primary/5 py-6 text-base font-semibold transition-all hover:border-primary/50 hover:bg-primary/10 hover:shadow-md"
                                    >
                                        <PlusCircle className="size-5" />
                                        新增本週完成項目
                                    </Button>

                                    {/* 工時小計 */}
                                    <div className="grid gap-3 rounded-lg border-2 border-blue-200/60 bg-linear-to-r from-blue-50/50 to-blue-100/30 p-4 md:grid-cols-2 md:p-5 dark:border-blue-800/60 dark:from-blue-950/20 dark:to-blue-900/10">
                                        <div className="flex items-center justify-between gap-3">
                                            <span className="text-sm font-bold text-foreground md:text-base">
                                                實際工時小計：
                                            </span>
                                            <span className="text-xl font-bold text-blue-600 md:text-2xl dark:text-blue-400">
                                                {totalCurrentWeekHours.toFixed(
                                                    1,
                                                )}{' '}
                                                <span className="text-base font-semibold md:text-lg">
                                                    小時
                                                </span>
                                            </span>
                                        </div>
                                        <div className="flex items-center justify-between gap-3">
                                            <span className="text-sm font-bold text-foreground md:text-base">
                                                本週項目預估工時小計：
                                            </span>
                                            <span className="text-xl font-bold text-sky-600 md:text-2xl dark:text-sky-400">
                                                {totalCurrentWeekItemPlannedHours.toFixed(
                                                    1,
                                                )}{' '}
                                                <span className="text-base font-semibold md:text-lg">
                                                    小時
                                                </span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </>
                        )}
                    </CardContent>
                </Card>

                <Card className="border-2 border-border/60 shadow-md">
                    <CardHeader className="border-b-2 border-border/60 bg-linear-to-r from-muted/50 to-muted/30 pb-5">
                        <CardTitle className="text-xl font-bold text-foreground sm:text-2xl">
                            下週預計事項
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="pt-6">
                        {form.data.next_week.length === 0 ? (
                            <div className="rounded-xl border-2 border-dashed border-border/50 bg-linear-to-br from-muted/30 to-muted/10 p-12 text-center">
                                <p className="mb-6 text-sm font-medium text-muted-foreground sm:text-base">
                                    目前沒有任何預計事項，點擊下方按鈕規劃下週工作
                                </p>
                                <Button
                                    type="button"
                                    size="lg"
                                    variant="default"
                                    onClick={addNextItem}
                                    className="gap-2 shadow-md transition-all hover:shadow-lg"
                                >
                                    <PlusCircle className="size-5" />
                                    新增下週預計項目
                                </Button>
                            </div>
                        ) : (
                            <>
                                <DndContext
                                    sensors={sensors}
                                    collisionDetection={closestCenter}
                                    onDragEnd={handleNextWeekDragEnd}
                                >
                                    <SortableContext
                                        items={form.data.next_week.map(
                                            (item) => item.localKey,
                                        )}
                                        strategy={verticalListSortingStrategy}
                                    >
                                        {form.data.next_week.map(
                                            (item, index) => (
                                                <SortableNextWeekRow
                                                    key={item.localKey}
                                                    item={item}
                                                    index={index}
                                                    totalItems={
                                                        form.data.next_week
                                                            .length
                                                    }
                                                    updateItem={updateItem}
                                                    removeItem={removeItem}
                                                    moveItem={moveItem}
                                                    getError={getError}
                                                    nextWeekDateRange={
                                                        nextWeekDateRange
                                                    }
                                                    holidayMap={
                                                        nextWeekHolidayMap
                                                    }
                                                    holidayDates={
                                                        nextWeekHolidayMarkers.holidayDates
                                                    }
                                                    workdayOverrideDates={
                                                        nextWeekHolidayMarkers.workdayOverrideDates
                                                    }
                                                />
                                            ),
                                        )}
                                    </SortableContext>
                                </DndContext>

                                {/* 新增按鈕移到底部 */}
                                <div className="mt-6">
                                    <Button
                                        type="button"
                                        size="lg"
                                        variant="outline"
                                        onClick={addNextItem}
                                        className="w-full gap-2 border-2 border-dashed border-primary/30 bg-primary/5 py-6 text-base font-semibold transition-all hover:border-primary/50 hover:bg-primary/10 hover:shadow-md"
                                    >
                                        <PlusCircle className="size-5" />
                                        新增下週預計項目
                                    </Button>
                                </div>
                            </>
                        )}
                    </CardContent>
                </Card>

                {/* 工時合計顯示 */}
                <Card className="border-2 border-border/60 shadow-lg">
                    <CardHeader className="border-b-2 border-border/60 bg-linear-to-r from-muted/50 to-muted/30 pb-5">
                        <CardTitle className="text-xl font-bold text-foreground sm:text-2xl">
                            工時統計
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="pt-8">
                        <div className="grid gap-6 xl:grid-cols-4">
                            <div className="relative rounded-xl border-2 border-blue-300 bg-linear-to-br from-blue-50 via-blue-100/50 to-blue-50 p-8 shadow-lg dark:border-blue-700 dark:from-blue-950/30 dark:via-blue-900/20 dark:to-blue-950/30">
                                <div className="absolute top-4 right-4">
                                    <Clock className="size-6 text-blue-400/40 dark:text-blue-500/30" />
                                </div>
                                <div className="text-sm font-semibold text-muted-foreground">
                                    本週實際總工時
                                </div>
                                <div className="mt-3 text-4xl font-bold text-blue-600 sm:text-5xl dark:text-blue-400">
                                    {totalCurrentWeekHours.toFixed(1)}
                                </div>
                                <div className="mt-2 text-sm font-medium text-muted-foreground">
                                    小時
                                </div>
                                <div className="mt-4 text-sm font-medium text-muted-foreground">
                                    {form.data.current_week.length} 個項目
                                </div>
                                {currentWeekCapacity &&
                                    totalCurrentWeekHours >
                                        currentWeekCapacity.availableHours && (
                                        <div className="mt-3 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200">
                                            已高於本週可排工時{' '}
                                            {Math.abs(
                                                totalCurrentWeekHours -
                                                    currentWeekCapacity.availableHours,
                                            ).toFixed(1)}{' '}
                                            小時
                                        </div>
                                    )}
                            </div>
                            <div className="relative rounded-xl border-2 border-sky-300 bg-linear-to-br from-sky-50 via-sky-100/50 to-sky-50 p-8 shadow-lg dark:border-sky-700 dark:from-sky-950/30 dark:via-sky-900/20 dark:to-sky-950/30">
                                <div className="absolute top-4 right-4">
                                    <Clock className="size-6 text-sky-400/40 dark:text-sky-500/30" />
                                </div>
                                <div className="text-sm font-semibold text-muted-foreground">
                                    本週應填總工時
                                </div>
                                <div className="mt-3 text-4xl font-bold text-sky-600 sm:text-5xl dark:text-sky-400">
                                    {currentWeekTargetHours.toFixed(1)}
                                </div>
                                <div className="mt-2 text-sm font-medium text-muted-foreground">
                                    小時
                                </div>
                                <div className="mt-4 text-sm font-medium text-muted-foreground">
                                    與實際差異{' '}
                                    {(
                                        totalCurrentWeekHours -
                                        currentWeekTargetHours
                                    ).toFixed(1)}{' '}
                                    小時
                                </div>
                                <div className="mt-3 text-sm text-muted-foreground">
                                    依本週假日與補班日自動計算
                                </div>
                            </div>
                            <div className="relative rounded-xl border-2 border-emerald-300 bg-linear-to-br from-emerald-50 via-emerald-100/50 to-emerald-50 p-8 shadow-lg dark:border-emerald-700 dark:from-emerald-950/30 dark:via-emerald-900/20 dark:to-emerald-950/30">
                                <div className="absolute top-4 right-4">
                                    <Clock className="size-6 text-emerald-400/40 dark:text-emerald-500/30" />
                                </div>
                                <div className="text-sm font-semibold text-muted-foreground">
                                    下週目前已排工時
                                </div>
                                <div className="mt-3 text-4xl font-bold text-emerald-600 sm:text-5xl dark:text-emerald-400">
                                    {totalNextWeekHours.toFixed(1)}
                                </div>
                                <div className="mt-2 text-sm font-medium text-muted-foreground">
                                    小時
                                </div>
                                <div className="mt-4 text-sm font-medium text-muted-foreground">
                                    {form.data.next_week.length} 個項目
                                </div>
                            </div>
                            <div className="relative rounded-xl border-2 border-teal-300 bg-linear-to-br from-teal-50 via-teal-100/50 to-teal-50 p-8 shadow-lg dark:border-teal-700 dark:from-teal-950/30 dark:via-teal-900/20 dark:to-teal-950/30">
                                <div className="absolute top-4 right-4">
                                    <Clock className="size-6 text-teal-400/40 dark:text-teal-500/30" />
                                </div>
                                <div className="text-sm font-semibold text-muted-foreground">
                                    下週可排總工時
                                </div>
                                <div className="mt-3 text-4xl font-bold text-teal-600 sm:text-5xl dark:text-teal-400">
                                    {nextWeekTargetHours.toFixed(1)}
                                </div>
                                <div className="mt-2 text-sm font-medium text-muted-foreground">
                                    小時
                                </div>
                                <div className="mt-4 text-sm font-medium text-muted-foreground">
                                    與已排差異{' '}
                                    {(
                                        totalNextWeekHours - nextWeekTargetHours
                                    ).toFixed(1)}{' '}
                                    小時
                                </div>
                                {nextWeekCapacity && (
                                    <div className="mt-3 text-sm text-muted-foreground">
                                        依下週假日與補班日自動計算
                                    </div>
                                )}
                                {nextWeekCapacity &&
                                    totalNextWeekHours >
                                        nextWeekCapacity.availableHours && (
                                        <div className="mt-3 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200">
                                            已高於下週可排工時{' '}
                                            {Math.abs(
                                                totalNextWeekHours -
                                                    nextWeekCapacity.availableHours,
                                            ).toFixed(1)}{' '}
                                            小時
                                        </div>
                                    )}
                            </div>
                        </div>
                        {(currentWeekCapacity || nextWeekCapacity) && (
                            <div className="mt-6 grid gap-4 lg:grid-cols-2">
                                {currentWeekCapacity && (
                                    <div className="rounded-xl border border-border/60 bg-muted/20 p-4">
                                        <div className="text-sm font-semibold text-foreground">
                                            本週可排工時基準
                                        </div>
                                        <div className="mt-2 text-sm text-muted-foreground">
                                            工作日{' '}
                                            {currentWeekCapacity.workingDays}{' '}
                                            天，扣除假日{' '}
                                            {currentWeekCapacity.holidayDays} 天
                                            {currentWeekCapacity.makeupWorkdays >
                                                0 &&
                                                `，含補班日 ${currentWeekCapacity.makeupWorkdays} 天`}
                                        </div>
                                    </div>
                                )}
                                {nextWeekCapacity && (
                                    <div className="rounded-xl border border-border/60 bg-muted/20 p-4">
                                        <div className="text-sm font-semibold text-foreground">
                                            下週可排工時基準
                                        </div>
                                        <div className="mt-2 text-sm text-muted-foreground">
                                            工作日{' '}
                                            {nextWeekCapacity.workingDays}{' '}
                                            天，扣除假日{' '}
                                            {nextWeekCapacity.holidayDays} 天
                                            {nextWeekCapacity.makeupWorkdays >
                                                0 &&
                                                `，含補班日 ${nextWeekCapacity.makeupWorkdays} 天`}
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card className="border-2 border-border/60 shadow-md">
                    <CardHeader className="border-b-2 border-border/60 bg-linear-to-r from-muted/50 to-muted/30 pb-5">
                        <CardTitle className="text-xl font-bold text-foreground sm:text-2xl">
                            摘要
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="pt-6">
                        <div>
                            <Label
                                htmlFor="summary"
                                className="text-base font-semibold text-foreground"
                            >
                                週報摘要
                            </Label>
                            <Textarea
                                id="summary"
                                rows={5}
                                value={form.data.summary}
                                data-testid="summary"
                                onChange={(event) =>
                                    form.setData('summary', event.target.value)
                                }
                                placeholder="可輸入本週亮點、風險提醒或需要協助的事項（選填）"
                                className="mt-3 text-base leading-relaxed"
                            />
                            <InputError
                                message={form.errors.summary}
                                className="mt-2"
                            />
                        </div>
                    </CardContent>
                </Card>

                {/* 底部留出空間給固定操作欄 */}
                <div className="h-32" />

                {/* 固定底部操作欄 */}
                <div className="fixed inset-x-0 bottom-0 z-50 border-t-2 border-border/60 bg-background/95 shadow-2xl backdrop-blur-md supports-[backdrop-filter]:bg-background/80">
                    <div className="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
                        {/* 沒有變更的提示訊息 */}
                        {showNoChangesMessage && (
                            <div className="mb-3 rounded-lg border-2 border-sky-300 bg-sky-50/90 px-4 py-3 shadow-sm dark:border-sky-700 dark:bg-sky-950/40">
                                <div className="flex items-center gap-2">
                                    <Clock className="size-5 shrink-0 text-sky-600 dark:text-sky-400" />
                                    <p className="text-sm font-semibold text-sky-900 dark:text-sky-200">
                                        沒有需要儲存的變更
                                    </p>
                                </div>
                            </div>
                        )}

                        {/* 表單驗證錯誤訊息 */}
                        {Object.keys(form.errors).length > 0 && (
                            <div className="mb-3 rounded-lg border-2 border-red-300 bg-red-50/90 px-4 py-3 shadow-sm dark:border-red-700 dark:bg-red-950/40">
                                <div className="flex items-start justify-between gap-3">
                                    <div className="flex items-start gap-2">
                                        <AlertTriangle className="size-5 shrink-0 text-red-600 dark:text-red-400" />
                                        <div className="flex-1">
                                            <p className="text-sm font-bold text-red-900 dark:text-red-200">
                                                儲存失敗！請修正表單錯誤
                                            </p>
                                            <div className="mt-2 space-y-1">
                                                {Object.entries(
                                                    form.errors,
                                                ).map(([key, message]) => {
                                                    // 解析錯誤欄位名稱
                                                    let fieldName = '未知欄位';
                                                    if (
                                                        key.startsWith(
                                                            'current_week.',
                                                        )
                                                    ) {
                                                        const match = key.match(
                                                            /current_week\.(\d+)\.(\w+)/,
                                                        );
                                                        if (match) {
                                                            const index =
                                                                parseInt(
                                                                    match[1],
                                                                ) + 1;
                                                            const field =
                                                                match[2];
                                                            const fieldMap: Record<
                                                                string,
                                                                string
                                                            > = {
                                                                id: 'ID',
                                                                title: '標題',
                                                                content:
                                                                    '詳細說明',
                                                                hours_spent:
                                                                    '實際工時',
                                                                planned_hours:
                                                                    '預計工時',
                                                                issue_reference:
                                                                    'Issue 編號',
                                                                is_billable:
                                                                    '是否計費',
                                                                tags: '標籤',
                                                                started_at:
                                                                    '開始日期',
                                                                ended_at:
                                                                    '結束日期',
                                                            };
                                                            fieldName = `本週第 ${index} 項 - ${fieldMap[field] || field}`;
                                                        }
                                                    } else if (
                                                        key.startsWith(
                                                            'next_week.',
                                                        )
                                                    ) {
                                                        const match = key.match(
                                                            /next_week\.(\d+)\.(\w+)/,
                                                        );
                                                        if (match) {
                                                            const index =
                                                                parseInt(
                                                                    match[1],
                                                                ) + 1;
                                                            const field =
                                                                match[2];
                                                            const fieldMap: Record<
                                                                string,
                                                                string
                                                            > = {
                                                                id: 'ID',
                                                                title: '標題',
                                                                content:
                                                                    '詳細說明',
                                                                planned_hours:
                                                                    '預計工時',
                                                                issue_reference:
                                                                    'Issue 編號',
                                                                tags: '標籤',
                                                                started_at:
                                                                    '開始日期',
                                                                ended_at:
                                                                    '結束日期',
                                                            };
                                                            fieldName = `下週第 ${index} 項 - ${fieldMap[field] || field}`;
                                                        }
                                                    } else {
                                                        const fieldMap: Record<
                                                            string,
                                                            string
                                                        > = {
                                                            work_year: '年份',
                                                            work_week: '週次',
                                                            summary: '摘要',
                                                        };
                                                        fieldName =
                                                            fieldMap[key] ||
                                                            key;
                                                    }

                                                    return (
                                                        <p
                                                            key={key}
                                                            className="text-xs text-red-800 dark:text-red-300"
                                                        >
                                                            • {fieldName}：
                                                            {message}
                                                        </p>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={scrollToTop}
                                        className="shrink-0 text-red-700 hover:bg-red-100 hover:text-red-900 dark:text-red-300 dark:hover:bg-red-900/30 dark:hover:text-red-200"
                                    >
                                        查看
                                    </Button>
                                </div>
                            </div>
                        )}

                        {/* Flash 訊息 - 成功、資訊、警告 */}
                        {flash?.success && (
                            <div className="mb-3 rounded-lg border-2 border-emerald-300 bg-emerald-50/90 px-4 py-3 shadow-sm dark:border-emerald-700 dark:bg-emerald-950/40">
                                <div className="flex items-center gap-2">
                                    <CheckCircle2 className="size-5 shrink-0 text-emerald-600 dark:text-emerald-400" />
                                    <p className="text-sm font-semibold text-emerald-900 dark:text-emerald-200">
                                        {flash.success}
                                    </p>
                                </div>
                            </div>
                        )}
                        {flash?.info && (
                            <div className="mb-3 rounded-lg border-2 border-sky-300 bg-sky-50/90 px-4 py-3 shadow-sm dark:border-sky-700 dark:bg-sky-950/40">
                                <div className="flex items-center gap-2">
                                    <Clock className="size-5 shrink-0 text-sky-600 dark:text-sky-400" />
                                    <p className="text-sm font-semibold text-sky-900 dark:text-sky-200">
                                        {flash.info}
                                    </p>
                                </div>
                            </div>
                        )}
                        {flash?.warning && (
                            <div className="mb-3 rounded-lg border-2 border-amber-300 bg-amber-50/90 px-4 py-3 shadow-sm dark:border-amber-700 dark:bg-amber-950/40">
                                <div className="flex items-center gap-2">
                                    <Clock className="size-5 shrink-0 text-amber-600 dark:text-amber-400" />
                                    <p className="text-sm font-semibold text-amber-900 dark:text-amber-200">
                                        {flash.warning}
                                    </p>
                                </div>
                            </div>
                        )}

                        {/* 狀態提示訊息 - 草稿狀態僅在沒有 flash 訊息時顯示 */}
                        {!flash?.success &&
                            !flash?.info &&
                            !flash?.warning &&
                            report &&
                            report.status === 'draft' && (
                                <div className="mb-3 rounded-lg border border-amber-300 bg-amber-50/80 px-3 py-2 dark:border-amber-700 dark:bg-amber-950/30">
                                    <p className="text-xs font-semibold text-amber-900 dark:text-amber-200">
                                        💡
                                        提示：點擊「發佈週報」後，狀態將變更為「已送出」
                                    </p>
                                </div>
                            )}

                        {/* 已發佈狀態提示 - 即使有 flash 訊息也要顯示，提醒使用者週報狀態 */}
                        {report && report.status === 'submitted' && (
                            <div className="mb-3 rounded-lg border border-emerald-300/60 bg-emerald-50/60 px-3 py-2 dark:border-emerald-700/60 dark:bg-emerald-950/20">
                                <p className="text-xs font-semibold text-emerald-900 dark:text-emerald-200">
                                    ✓ 此週報已發佈，您可以繼續編輯內容
                                </p>
                            </div>
                        )}
                        <div className="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center sm:justify-end">
                            {report && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="lg"
                                    disabled={!canNavigate}
                                    asChild
                                    className="gap-2 shadow-md transition-all hover:shadow-lg"
                                >
                                    <Link
                                        href={
                                            canNavigate
                                                ? `/app/${companySlug}/weekly-reports/${report.id}/preview`
                                                : '#'
                                        }
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        預覽週報
                                    </Link>
                                </Button>
                            )}
                            {report?.status === 'draft' && (
                                <Button
                                    type="button"
                                    variant="default"
                                    size="lg"
                                    disabled={form.processing || !canNavigate}
                                    className="gap-2 shadow-md transition-all hover:shadow-lg"
                                    onClick={() => {
                                        if (!canNavigate || !report) {
                                            return;
                                        }
                                        router.post(
                                            `/app/${companySlug}/weekly-reports/${report.id}/submit`,
                                            {},
                                            {
                                                preserveScroll: true,
                                                onSuccess: () => {
                                                    // 重新載入頁面以更新狀態
                                                    router.reload({
                                                        only: ['report'],
                                                    });
                                                },
                                            },
                                        );
                                    }}
                                    data-testid="submit-weekly-report"
                                >
                                    <CheckCircle2 className="size-5" />
                                    發佈週報
                                </Button>
                            )}
                            {report?.status === 'submitted' && canReopen && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="lg"
                                    disabled={form.processing || !canNavigate}
                                    className="gap-2 border-amber-300 text-amber-700 shadow-md transition-all hover:bg-amber-50 hover:shadow-lg dark:border-amber-700 dark:text-amber-400 dark:hover:bg-amber-950/30"
                                    onClick={() => {
                                        if (!canNavigate || !report) {
                                            return;
                                        }
                                        router.post(
                                            `/app/${companySlug}/weekly-reports/${report.id}/reopen`,
                                            {},
                                            {
                                                preserveScroll: true,
                                                onSuccess: () => {
                                                    router.reload({
                                                        only: [
                                                            'report',
                                                            'canReopen',
                                                        ],
                                                    });
                                                },
                                            },
                                        );
                                    }}
                                    data-testid="reopen-weekly-report"
                                >
                                    <RotateCcw className="size-5" />
                                    重新開啟
                                </Button>
                            )}
                            <Button
                                type="submit"
                                size="lg"
                                disabled={form.processing || !canNavigate}
                                className="gap-2 bg-primary font-bold shadow-md transition-all hover:shadow-lg"
                                data-testid="save-weekly-report"
                            >
                                {form.processing ? '儲存中...' : '儲存週報'}
                            </Button>
                        </div>
                    </div>
                </div>
            </form>
        </AppLayout>
    );
}
