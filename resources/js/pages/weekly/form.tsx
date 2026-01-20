import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { isWeekend } from '@/lib/date-utils';
import tenantRoutes from '@/routes/tenant';
import * as weeklyRoutes from '@/routes/tenant/weekly-reports';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, GripVertical, PlusCircle, Trash2, CheckCircle2, Clock, Lock, type LucideIcon } from 'lucide-react';
import {
    DndContext,
    closestCenter,
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
    useSortable,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

type WeeklyReportItemInput = {
    id?: number;
    localKey: string;
    title: string;
    content?: string | null;
    hours_spent: number;
    planned_hours?: number | null;
    issue_reference?: string | null;
    is_billable?: boolean;
    tags?: string[];
    tagsText?: string;
    started_at?: string | null;
    ended_at?: string | null;
};

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
    prefill: {
        currentWeek: Omit<WeeklyReportItemInput, 'localKey' | 'tagsText'>[];
        nextWeek: Omit<WeeklyReportItemInput, 'localKey' | 'tagsText'>[];
    };
}

type FormData = {
    work_year: number;
    work_week: number;
    summary: string;
    current_week: WeeklyReportItemInput[];
    next_week: WeeklyReportItemInput[];
};

const STATUS_CONFIG: Record<string, { text: string; icon: LucideIcon; className: string }> = {
    draft: {
        text: '草稿',
        icon: Clock,
        className: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20',
    },
    submitted: {
        text: '已送出',
        icon: CheckCircle2,
        className: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20',
    },
    locked: {
        text: '已鎖定',
        icon: Lock,
        className: 'bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-500/10 dark:text-slate-400 dark:border-slate-500/20',
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
        hours_spent: typeof hoursSpent === 'number' ? hoursSpent : Number(hoursSpent) || 0,
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

type SortableCurrentWeekRowProps = {
    item: WeeklyReportItemInput;
    index: number;
    updateItem: (
        type: 'current_week' | 'next_week',
        index: number,
        key: keyof WeeklyReportItemInput,
        value: unknown,
    ) => void;
    removeItem: (type: 'current_week' | 'next_week', index: number) => void;
    getError: (path: string) => string | undefined;
    weekDateRange?: {
        startDate: string;
        endDate: string;
    };
};

function SortableCurrentWeekRow({
    item,
    index,
    updateItem,
    removeItem,
    getError,
    weekDateRange,
}: SortableCurrentWeekRowProps) {
    const startedAtIsWeekend = isWeekend(item.started_at);
    const endedAtIsWeekend = isWeekend(item.ended_at);
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
        id: item.localKey,
    });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.5 : 1,
    };

    return (
        <tr ref={setNodeRef} style={style} className="transition-colors hover:bg-muted/30">
            <td className="px-4 py-3 cursor-move" {...attributes} {...listeners}>
                <div className="flex items-center justify-center text-muted-foreground hover:text-foreground">
                    <GripVertical className="size-4" />
                </div>
            </td>
            <td className="px-4 py-4">
                <Input
                    value={item.title}
                    placeholder="任務名稱"
                    className="min-w-[180px] border-2 border-border/60 focus:border-primary focus:ring-2 focus:ring-primary/20"
                    data-testid={`current_week.${index}.title`}
                    onChange={(event) => updateItem('current_week', index, 'title', event.target.value)}
                />
                <InputError message={getError(`current_week.${index}.title`)} className="mt-1" />
            </td>
            <td className="px-4 py-4">
                <Textarea
                    rows={2}
                    value={item.content ?? ''}
                    placeholder="詳細說明"
                    className="min-w-[200px] border-2 border-border/60 focus:border-primary focus:ring-2 focus:ring-primary/20"
                    data-testid={`current_week.${index}.content`}
                    onChange={(event) => updateItem('current_week', index, 'content', event.target.value)}
                />
                <InputError message={getError(`current_week.${index}.content`)} className="mt-1" />
            </td>
            <td className="px-4 py-3">
                <div className="space-y-2">
                    <div>
                        <Label htmlFor={`started_at_${item.localKey}`} className="text-xs text-muted-foreground">
                            開始日期
                        </Label>
                        <DatePicker
                            id={`started_at_${item.localKey}`}
                            value={item.started_at ?? null}
                            onChange={(date) => updateItem('current_week', index, 'started_at', date)}
                            minDate={weekDateRange?.startDate}
                            maxDate={item.ended_at ?? weekDateRange?.endDate}
                            weekRange={weekDateRange}
                            placeholder="選擇開始日期"
                            className={`w-full ${startedAtIsWeekend ? 'bg-yellow-50 dark:bg-yellow-950/20 border-yellow-300 dark:border-yellow-700' : ''}`}
                        />
                        {startedAtIsWeekend && (
                            <p className="text-xs text-yellow-600 dark:text-yellow-400 mt-1">週末日期</p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor={`ended_at_${item.localKey}`} className="text-xs text-muted-foreground">
                            結束日期
                        </Label>
                        <DatePicker
                            id={`ended_at_${item.localKey}`}
                            value={item.ended_at ?? null}
                            onChange={(date) => updateItem('current_week', index, 'ended_at', date)}
                            minDate={item.started_at ?? weekDateRange?.startDate}
                            maxDate={weekDateRange?.endDate}
                            weekRange={weekDateRange}
                            placeholder="選擇結束日期"
                            className={`w-full ${endedAtIsWeekend ? 'bg-yellow-50 dark:bg-yellow-950/20 border-yellow-300 dark:border-yellow-700' : ''}`}
                        />
                        {item.started_at && item.ended_at && item.ended_at < item.started_at && (
                            <p className="text-xs text-red-600 dark:text-red-400 mt-1">結束日期不能早於開始日期</p>
                        )}
                        {endedAtIsWeekend && (
                            <p className="text-xs text-yellow-600 dark:text-yellow-400 mt-1">週末日期</p>
                        )}
                    </div>
                </div>
                <InputError message={getError(`current_week.${index}.started_at`)} className="mt-1" />
                <InputError message={getError(`current_week.${index}.ended_at`)} className="mt-1" />
            </td>
            <td className="px-4 py-4">
                <Input
                    type="number"
                    step="0.5"
                    min="0"
                    value={item.planned_hours ?? ''}
                    className="w-20 border-2 border-border/60 focus:border-primary focus:ring-2 focus:ring-primary/20"
                    data-testid={`current_week.${index}.planned_hours`}
                    onChange={(event) =>
                        updateItem(
                            'current_week',
                            index,
                            'planned_hours',
                            event.target.value === '' ? null : Number(event.target.value),
                        )
                    }
                />
                <InputError message={getError(`current_week.${index}.planned_hours`)} className="mt-1" />
            </td>
            <td className="px-4 py-4">
                <Input
                    type="number"
                    step="0.5"
                    min="0"
                    value={item.hours_spent}
                    className="w-20 border-2 border-border/60 focus:border-primary focus:ring-2 focus:ring-primary/20 font-semibold"
                    data-testid={`current_week.${index}.hours_spent`}
                    onChange={(event) =>
                        updateItem(
                            'current_week',
                            index,
                            'hours_spent',
                            event.target.value === '' ? 0 : Number(event.target.value),
                        )
                    }
                />
                <InputError message={getError(`current_week.${index}.hours_spent`)} className="mt-1" />
            </td>
            <td className="px-4 py-4">
                <Input
                    value={item.issue_reference ?? ''}
                    placeholder="JIRA-1234"
                    className="min-w-[120px] border-2 border-border/60 focus:border-primary focus:ring-2 focus:ring-primary/20"
                    onChange={(event) => updateItem('current_week', index, 'issue_reference', event.target.value)}
                />
                <InputError message={getError(`current_week.${index}.issue_reference`)} className="mt-1" />
            </td>
            <td className="px-4 py-4">
                <Input
                    value={item.tagsText ?? ''}
                    placeholder="標籤"
                    className="min-w-[120px] border-2 border-border/60 focus:border-primary focus:ring-2 focus:ring-primary/20"
                    onChange={(event) => updateItem('current_week', index, 'tagsText', event.target.value)}
                />
                <InputError message={getError(`current_week.${index}.tags`)} className="mt-1" />
            </td>
            <td className="px-4 py-4 text-center">
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    onClick={() => removeItem('current_week', index)}
                    className="text-muted-foreground transition-colors hover:bg-destructive/10 hover:text-destructive"
                >
                    <Trash2 className="size-4" />
                </Button>
            </td>
        </tr>
    );
}

type SortableNextWeekRowProps = {
    item: WeeklyReportItemInput;
    index: number;
    updateItem: (
        type: 'current_week' | 'next_week',
        index: number,
        key: keyof WeeklyReportItemInput,
        value: unknown,
    ) => void;
    removeItem: (type: 'current_week' | 'next_week', index: number) => void;
    getError: (path: string) => string | undefined;
    nextWeekDateRange?: {
        startDate: string;
        endDate: string;
    };
};

function SortableNextWeekRow({ item, index, updateItem, removeItem, getError, nextWeekDateRange }: SortableNextWeekRowProps) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
        id: item.localKey,
    });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.5 : 1,
    };

    const startedAtIsWeekend = isWeekend(item.started_at);
    const endedAtIsWeekend = isWeekend(item.ended_at);

    return (
        <tr ref={setNodeRef} style={style} className="transition-all duration-200 hover:bg-muted/40">
            <td className="px-4 py-4 cursor-move" {...attributes} {...listeners}>
                <div className="flex items-center justify-center text-muted-foreground transition-colors hover:text-foreground">
                    <GripVertical className="size-5" />
                </div>
            </td>
            <td className="px-4 py-4">
                <Input
                    value={item.title}
                    placeholder="預計事項"
                    className="min-w-[180px] border-2 border-border/60 focus:border-primary focus:ring-2 focus:ring-primary/20"
                    data-testid={`next_week.${index}.title`}
                    onChange={(event) => updateItem('next_week', index, 'title', event.target.value)}
                />
                <InputError message={getError(`next_week.${index}.title`)} className="mt-1" />
            </td>
            <td className="px-4 py-4">
                <Textarea
                    rows={2}
                    value={item.content ?? ''}
                    placeholder="說明"
                    className="min-w-[200px] border-2 border-border/60 focus:border-primary focus:ring-2 focus:ring-primary/20"
                    data-testid={`next_week.${index}.content`}
                    onChange={(event) => updateItem('next_week', index, 'content', event.target.value)}
                />
                <InputError message={getError(`next_week.${index}.content`)} className="mt-1" />
            </td>
            <td className="px-4 py-3">
                <div className="space-y-2">
                    <div>
                        <Label htmlFor={`next_started_at_${item.localKey}`} className="text-xs text-muted-foreground">
                            開始日期
                        </Label>
                        <DatePicker
                            id={`next_started_at_${item.localKey}`}
                            value={item.started_at ?? null}
                            onChange={(date) => updateItem('next_week', index, 'started_at', date)}
                            minDate={nextWeekDateRange?.startDate}
                            maxDate={item.ended_at ?? nextWeekDateRange?.endDate}
                            weekRange={nextWeekDateRange}
                            placeholder="選擇開始日期"
                            className={`w-full ${startedAtIsWeekend ? 'bg-yellow-50 dark:bg-yellow-950/20 border-yellow-300 dark:border-yellow-700' : ''}`}
                        />
                        {startedAtIsWeekend && (
                            <p className="text-xs text-yellow-600 dark:text-yellow-400 mt-1">週末日期</p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor={`next_ended_at_${item.localKey}`} className="text-xs text-muted-foreground">
                            結束日期
                        </Label>
                        <DatePicker
                            id={`next_ended_at_${item.localKey}`}
                            value={item.ended_at ?? null}
                            onChange={(date) => updateItem('next_week', index, 'ended_at', date)}
                            minDate={item.started_at ?? nextWeekDateRange?.startDate}
                            maxDate={nextWeekDateRange?.endDate}
                            weekRange={nextWeekDateRange}
                            placeholder="選擇結束日期"
                            className={`w-full ${endedAtIsWeekend ? 'bg-yellow-50 dark:bg-yellow-950/20 border-yellow-300 dark:border-yellow-700' : ''}`}
                        />
                        {item.started_at && item.ended_at && item.ended_at < item.started_at && (
                            <p className="text-xs text-red-600 dark:text-red-400 mt-1">結束日期不能早於開始日期</p>
                        )}
                        {endedAtIsWeekend && (
                            <p className="text-xs text-yellow-600 dark:text-yellow-400 mt-1">週末日期</p>
                        )}
                    </div>
                </div>
                <InputError message={getError(`next_week.${index}.started_at`)} className="mt-1" />
                <InputError message={getError(`next_week.${index}.ended_at`)} className="mt-1" />
            </td>
            <td className="px-4 py-4">
                <Input
                    type="number"
                    step="0.5"
                    min="0"
                    value={item.planned_hours ?? ''}
                    className="w-20 border-2 border-border/60 focus:border-primary focus:ring-2 focus:ring-primary/20 font-semibold"
                    data-testid={`next_week.${index}.planned_hours`}
                    onChange={(event) =>
                        updateItem(
                            'next_week',
                            index,
                            'planned_hours',
                            event.target.value === '' ? null : Number(event.target.value),
                        )
                    }
                />
                <InputError message={getError(`next_week.${index}.planned_hours`)} className="mt-1" />
            </td>
            <td className="px-4 py-4">
                <Input
                    value={item.issue_reference ?? ''}
                    placeholder="JIRA-2030"
                    className="min-w-[120px] border-2 border-border/60 focus:border-primary focus:ring-2 focus:ring-primary/20"
                    onChange={(event) => updateItem('next_week', index, 'issue_reference', event.target.value)}
                />
                <InputError message={getError(`next_week.${index}.issue_reference`)} className="mt-1" />
            </td>
            <td className="px-4 py-4">
                <Input
                    value={item.tagsText ?? ''}
                    placeholder="標籤"
                    className="min-w-[120px] border-2 border-border/60 focus:border-primary focus:ring-2 focus:ring-primary/20"
                    onChange={(event) => updateItem('next_week', index, 'tagsText', event.target.value)}
                />
                <InputError message={getError(`next_week.${index}.tags`)} className="mt-1" />
            </td>
            <td className="px-4 py-4 text-center">
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    onClick={() => removeItem('next_week', index)}
                    className="text-muted-foreground transition-colors hover:bg-destructive/10 hover:text-destructive"
                >
                    <Trash2 className="size-4" />
                </Button>
            </td>
        </tr>
    );
}

export default function WeeklyReportForm({
    mode,
    report,
    defaults,
    weekDateRange,
    nextWeekDateRange,
    prefill,
    company,
}: WeeklyReportFormProps) {
    const { tenant, flash } = usePage<
        SharedData & { flash: { success?: string; info?: string; warning?: string } }
    >().props;

    const sharedCompanySlug = (tenant?.company as { slug?: string } | undefined)?.slug;
    const companySlug = company?.slug ?? sharedCompanySlug ?? '';
    const canNavigate = companySlug.length > 0;
    const isCreate = mode === 'create';

    const form = useForm<FormData>({
        work_year: report?.workYear ?? defaults.year,
        work_week: report?.workWeek ?? defaults.week,
        summary: report?.summary ?? '',
        current_week: (report?.currentWeek ?? prefill.currentWeek ?? []).map((item, index) =>
            toFormItem(item, 'current', index),
        ),
        next_week: (report?.nextWeek ?? prefill.nextWeek ?? []).map((item, index) =>
            toFormItem(item, 'next', index, { planned_hours: item.planned_hours ?? null }),
        ),
    });

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    const handleCurrentWeekDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;

        if (over && active.id !== over.id) {
            const oldIndex = form.data.current_week.findIndex((item) => item.localKey === active.id);
            const newIndex = form.data.current_week.findIndex((item) => item.localKey === over.id);

            if (oldIndex !== -1 && newIndex !== -1) {
                form.setData('current_week', arrayMove(form.data.current_week, oldIndex, newIndex));
            }
        }
    };

    const handleNextWeekDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;

        if (over && active.id !== over.id) {
            const oldIndex = form.data.next_week.findIndex((item) => item.localKey === active.id);
            const newIndex = form.data.next_week.findIndex((item) => item.localKey === over.id);

            if (oldIndex !== -1 && newIndex !== -1) {
                form.setData('next_week', arrayMove(form.data.next_week, oldIndex, newIndex));
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
                localKey: `current-new-${Date.now()}`,
                title: '',
                content: '',
                hours_spent: 0,
                planned_hours: null,
                issue_reference: '',
                is_billable: false,
                tags: [],
                tagsText: '',
            },
        ]);
    };

    const addNextItem = () => {
        form.setData('next_week', [
            ...form.data.next_week,
            {
                localKey: `next-new-${Date.now()}`,
                title: '',
                content: '',
                hours_spent: 0,
                planned_hours: null,
                issue_reference: '',
                tags: [],
                tagsText: '',
            },
        ]);
    };

    const removeItem = (type: 'current_week' | 'next_week', index: number) => {
        const items = [...form.data[type]];
        items.splice(index, 1);
        form.setData(type, items);
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

    const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        form.transform((data) => ({
            work_year: data.work_year,
            work_week: data.work_week,
            summary: data.summary,
            current_week: data.current_week.map((item) => ({
                id: item.id,
                title: item.title.trim(),
                content: item.content?.trim() || null,
                hours_spent: Number(item.hours_spent) || 0,
                planned_hours:
                    item.planned_hours === null || item.planned_hours === undefined
                        ? null
                        : Number(item.planned_hours),
                issue_reference: item.issue_reference?.trim() || null,
                is_billable: Boolean(item.is_billable),
                tags: parseTags(item.tagsText, item.tags),
                started_at: item.started_at || null,
                ended_at: item.ended_at || null,
            })),
            next_week: data.next_week.map((item) => ({
                id: item.id,
                title: item.title.trim(),
                content: item.content?.trim() || null,
                planned_hours:
                    item.planned_hours === null || item.planned_hours === undefined
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
        } as const;

        if (!canNavigate) {
            return;
        }

        if (isCreate) {
            form.post(weeklyRoutes.store.url({ company: companySlug }), options);
        } else if (report) {
            form.put(
                weeklyRoutes.update.url({ company: companySlug, weeklyReport: report.id }),
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
    const totalNextWeekHours = form.data.next_week.reduce(
        (sum, item) => sum + (item.planned_hours || 0),
        0,
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={isCreate ? '建立週報' : '編輯週報'} />

            <form onSubmit={handleSubmit} className="space-y-8">
                <div className="flex items-center gap-4 border-b border-border/60 pb-6">
                    {canNavigate ? (
                        <Button asChild variant="ghost" size="sm" className="gap-2">
                            <Link href={tenantRoutes.weeklyReports.url({ company: companySlug })}>
                                <ArrowLeft className="size-4" />
                                返回列表
                            </Link>
                        </Button>
                    ) : (
                        <Button variant="ghost" size="sm" disabled className="gap-2">
                            <ArrowLeft className="size-4" />
                            返回列表
                        </Button>
                    )}
                    <div className="flex-1">
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-bold text-foreground sm:text-3xl">
                                {isCreate ? '建立週報草稿' : '編輯週報'}
                            </h1>
                            {report && (() => {
                                const statusConfig = STATUS_CONFIG[report.status] ?? {
                                    text: report.status,
                                    icon: Clock,
                                    className: 'bg-muted text-muted-foreground border-border',
                                };
                                const StatusIcon = statusConfig.icon;
                                return (
                                    <span
                                        className={`inline-flex items-center gap-1.5 rounded-full border-2 px-3.5 py-1.5 text-xs font-semibold shadow-sm ${statusConfig.className}`}
                                    >
                                        <StatusIcon className="size-3.5" />
                                        {statusConfig.text}
                                    </span>
                                );
                            })()}
                        </div>
                        <p className="mt-3 text-base text-muted-foreground">
                            {form.data.work_year} 年第 {form.data.work_week} 週
                            {weekDateRange && (
                                <span className="text-muted-foreground">
                                    {' '}
                                    ({weekDateRange.startDate} ~ {weekDateRange.endDate})
                                </span>
                            )}
                        </p>
                    </div>
                </div>

                {(flash?.success || flash?.info || flash?.warning) && (
                    <div className="rounded-xl border-2 border-border/60 bg-muted/50 p-4 shadow-md backdrop-blur-sm">
                        {flash.success && (
                            <div className="flex items-center gap-2">
                                <CheckCircle2 className="size-5 text-emerald-600 dark:text-emerald-400" />
                                <p className="text-sm font-semibold text-emerald-700 dark:text-emerald-400">{flash.success}</p>
                            </div>
                        )}
                        {flash.info && (
                            <div className="flex items-center gap-2">
                                <Clock className="size-5 text-sky-600 dark:text-sky-400" />
                                <p className="text-sm font-semibold text-sky-700 dark:text-sky-400">{flash.info}</p>
                            </div>
                        )}
                        {flash.warning && (
                            <div className="flex items-center gap-2">
                                <Clock className="size-5 text-amber-600 dark:text-amber-400" />
                                <p className="text-sm font-semibold text-amber-700 dark:text-amber-400">{flash.warning}</p>
                            </div>
                        )}
                    </div>
                )}

                {isCreate && prefill.currentWeek.length > 0 && (
                    <div className="rounded-lg border-2 border-indigo-200 bg-linear-to-r from-indigo-50 to-indigo-100/50 p-4 shadow-sm dark:border-indigo-500/30 dark:from-indigo-500/10 dark:to-indigo-500/5">
                        <p className="text-sm font-semibold text-indigo-900 dark:text-indigo-100">
                            已帶入上一週的「下週預計」項目，記得調整內容與工時。
                        </p>
                    </div>
                )}

                <Card className="border-2 border-border/60 shadow-md">
                    <CardHeader className="border-b-2 border-border/60 bg-linear-to-r from-muted/50 to-muted/30 pb-5">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <CardTitle className="text-xl font-bold text-foreground sm:text-2xl">本週完成事項</CardTitle>
                            <Button type="button" size="sm" variant="default" onClick={addCurrentItem} className="gap-2 shadow-sm">
                                <PlusCircle className="size-4" />
                                新增項目
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent className="pt-6">
                        {form.data.current_week.length === 0 ? (
                            <div className="rounded-xl border-2 border-dashed border-border/50 bg-linear-to-br from-muted/30 to-muted/10 p-12 text-center">
                                <p className="text-sm font-medium text-muted-foreground sm:text-base">
                                    目前沒有任何項目，點擊「新增項目」開始紀錄。
                                </p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <DndContext
                                    sensors={sensors}
                                    collisionDetection={closestCenter}
                                    onDragEnd={handleCurrentWeekDragEnd}
                                >
                                    <table className="min-w-full divide-y divide-border/50 text-sm">
                                        <thead className="bg-linear-to-r from-muted/60 to-muted/40">
                                            <tr>
                                                <th className="px-4 py-4 w-10"></th>
                                                <th className="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-foreground">標題</th>
                                                <th className="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-foreground">內容</th>
                                                <th className="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-foreground whitespace-nowrap">時間範圍</th>
                                                <th className="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-foreground whitespace-nowrap">預計工時</th>
                                                <th className="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-foreground whitespace-nowrap">實際工時</th>
                                                <th className="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-foreground">Issue</th>
                                                <th className="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-foreground">標籤</th>
                                                <th className="px-4 py-4 text-center text-xs font-bold uppercase tracking-wider text-foreground">操作</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-border/50 bg-card">
                                            <SortableContext
                                                items={form.data.current_week.map((item) => item.localKey)}
                                                strategy={verticalListSortingStrategy}
                                            >
                                                {form.data.current_week.map((item, index) => (
                                                    <SortableCurrentWeekRow
                                                        key={item.localKey}
                                                        item={item}
                                                        index={index}
                                                        updateItem={updateItem}
                                                        removeItem={removeItem}
                                                        getError={getError}
                                                        weekDateRange={weekDateRange}
                                                    />
                                                ))}
                                            </SortableContext>
                                        </tbody>
                                        {form.data.current_week.length > 0 && (
                                            <tfoot className="bg-linear-to-r from-blue-50/50 to-blue-100/30 border-t-2 border-blue-200/60 dark:from-blue-950/20 dark:to-blue-900/10 dark:border-blue-800/60">
                                                <tr>
                                                    <td colSpan={4} className="px-4 py-4 text-right text-base font-bold text-foreground">
                                                        實際工時小計：
                                                    </td>
                                                    <td className="px-4 py-4"></td>
                                                    <td className="px-4 py-4 text-center text-xl font-bold text-blue-600 dark:text-blue-400">
                                                        {totalCurrentWeekHours.toFixed(1)} <span className="text-base font-semibold">小時</span>
                                                    </td>
                                                    <td colSpan={3}></td>
                                                </tr>
                                            </tfoot>
                                        )}
                                    </table>
                                </DndContext>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card className="border-2 border-border/60 shadow-md">
                    <CardHeader className="border-b-2 border-border/60 bg-linear-to-r from-muted/50 to-muted/30 pb-5">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <CardTitle className="text-xl font-bold text-foreground sm:text-2xl">下週預計事項</CardTitle>
                            <Button type="button" size="sm" variant="default" onClick={addNextItem} className="gap-2 shadow-sm">
                                <PlusCircle className="size-4" />
                                新增項目
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent className="pt-6">
                        {form.data.next_week.length === 0 ? (
                            <div className="rounded-xl border-2 border-dashed border-border/50 bg-linear-to-br from-muted/30 to-muted/10 p-12 text-center">
                                <p className="text-sm font-medium text-muted-foreground sm:text-base">
                                    目前沒有任何預計事項，點擊「新增項目」規劃下週工作。
                                </p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <DndContext
                                    sensors={sensors}
                                    collisionDetection={closestCenter}
                                    onDragEnd={handleNextWeekDragEnd}
                                >
                                    <table className="min-w-full divide-y divide-border/50 text-sm">
                                        <thead className="bg-linear-to-r from-muted/60 to-muted/40">
                                            <tr>
                                                <th className="px-4 py-4 w-10"></th>
                                                <th className="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-foreground">標題</th>
                                                <th className="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-foreground">補充說明</th>
                                                <th className="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-foreground whitespace-nowrap">時間範圍</th>
                                                <th className="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-foreground whitespace-nowrap">預估工時</th>
                                                <th className="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-foreground">Issue</th>
                                                <th className="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-foreground">標籤</th>
                                                <th className="px-4 py-4 text-center text-xs font-bold uppercase tracking-wider text-foreground">操作</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-border/50 bg-card">
                                            <SortableContext
                                                items={form.data.next_week.map((item) => item.localKey)}
                                                strategy={verticalListSortingStrategy}
                                            >
                                                {form.data.next_week.map((item, index) => (
                                                    <SortableNextWeekRow
                                                        key={item.localKey}
                                                        item={item}
                                                        index={index}
                                                        updateItem={updateItem}
                                                        removeItem={removeItem}
                                                        getError={getError}
                                                        nextWeekDateRange={nextWeekDateRange}
                                                    />
                                                ))}
                                            </SortableContext>
                                        </tbody>
                                    </table>
                                </DndContext>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* 工時合計顯示 */}
                <Card className="border-2 border-border/60 shadow-lg">
                    <CardHeader className="border-b-2 border-border/60 bg-linear-to-r from-muted/50 to-muted/30 pb-5">
                        <CardTitle className="text-xl font-bold text-foreground sm:text-2xl">工時統計</CardTitle>
                    </CardHeader>
                    <CardContent className="pt-8">
                        <div className="grid gap-6 sm:grid-cols-2">
                            <div className="relative rounded-xl border-2 border-blue-300 bg-linear-to-br from-blue-50 via-blue-100/50 to-blue-50 p-8 shadow-lg dark:border-blue-700 dark:from-blue-950/30 dark:via-blue-900/20 dark:to-blue-950/30">
                                <div className="absolute right-4 top-4">
                                    <Clock className="size-6 text-blue-400/40 dark:text-blue-500/30" />
                                </div>
                                <div className="text-sm font-semibold text-muted-foreground">本週完成總工時</div>
                                <div className="mt-3 text-4xl font-bold text-blue-600 dark:text-blue-400 sm:text-5xl">
                                    {totalCurrentWeekHours.toFixed(1)}
                                </div>
                                <div className="mt-2 text-sm font-medium text-muted-foreground">小時</div>
                                <div className="mt-4 text-sm font-medium text-muted-foreground">
                                    {form.data.current_week.length} 個項目
                                </div>
                            </div>
                            <div className="relative rounded-xl border-2 border-emerald-300 bg-linear-to-br from-emerald-50 via-emerald-100/50 to-emerald-50 p-8 shadow-lg dark:border-emerald-700 dark:from-emerald-950/30 dark:via-emerald-900/20 dark:to-emerald-950/30">
                                <div className="absolute right-4 top-4">
                                    <Clock className="size-6 text-emerald-400/40 dark:text-emerald-500/30" />
                                </div>
                                <div className="text-sm font-semibold text-muted-foreground">下週預計總工時</div>
                                <div className="mt-3 text-4xl font-bold text-emerald-600 dark:text-emerald-400 sm:text-5xl">
                                    {totalNextWeekHours.toFixed(1)}
                                </div>
                                <div className="mt-2 text-sm font-medium text-muted-foreground">小時</div>
                                <div className="mt-4 text-sm font-medium text-muted-foreground">
                                    {form.data.next_week.length} 個項目
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card className="border-2 border-border/60 shadow-md">
                    <CardHeader className="border-b-2 border-border/60 bg-linear-to-r from-muted/50 to-muted/30 pb-5">
                        <CardTitle className="text-xl font-bold text-foreground sm:text-2xl">摘要</CardTitle>
                    </CardHeader>
                    <CardContent className="pt-6">
                        <div>
                            <Label htmlFor="summary" className="text-base font-semibold text-foreground">週報摘要</Label>
                            <Textarea
                                id="summary"
                                rows={5}
                                value={form.data.summary}
                                data-testid="summary"
                                onChange={(event) => form.setData('summary', event.target.value)}
                                placeholder="可輸入本週亮點、風險提醒或需要協助的事項（選填）"
                                className="mt-3 text-base leading-relaxed"
                            />
                            <InputError message={form.errors.summary} className="mt-2" />
                        </div>
                    </CardContent>
                </Card>

                <div className="space-y-4 border-t-2 border-border/60 pt-8">
                    {report && report.status === 'draft' && (
                        <div className="rounded-xl border-2 border-amber-300 bg-linear-to-r from-amber-50/80 to-amber-100/50 p-5 shadow-sm dark:border-amber-700 dark:from-amber-950/30 dark:to-amber-900/20">
                            <p className="text-sm font-semibold text-amber-900 dark:text-amber-200 sm:text-base">
                                💡 提示：此週報目前為「草稿」狀態。點擊「發佈週報」後，狀態將變更為「已送出」，並記錄發佈時間。
                            </p>
                        </div>
                    )}
                    {report && report.status === 'submitted' && (
                        <div className="rounded-xl border-2 border-emerald-300 bg-linear-to-r from-emerald-50/80 to-emerald-100/50 p-5 shadow-sm dark:border-emerald-700 dark:from-emerald-950/30 dark:to-emerald-900/20">
                            <p className="text-sm font-semibold text-emerald-900 dark:text-emerald-200 sm:text-base">
                                ✓ 此週報已發佈。您可以繼續編輯內容，但狀態將保持為「已送出」。
                            </p>
                        </div>
                    )}
                    <div className="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center sm:justify-end">
                        {report && (
                            <Button
                                type="button"
                                variant="outline"
                                disabled={!canNavigate}
                                asChild
                                className="gap-2"
                            >
                                <Link
                                    href={canNavigate ? `/app/${companySlug}/weekly-reports/${report.id}/preview` : '#'}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    預覽
                                </Link>
                            </Button>
                        )}
                        {report?.status === 'draft' && (
                            <Button
                                type="button"
                                variant="default"
                                disabled={form.processing || !canNavigate}
                                className="gap-2"
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
                                                router.reload({ only: ['report'] });
                                            },
                                        },
                                    );
                                }}
                                data-testid="submit-weekly-report"
                            >
                                發佈週報
                            </Button>
                        )}
                        <Button
                            type="submit"
                            disabled={form.processing || !canNavigate}
                            className="gap-2"
                            data-testid="save-weekly-report"
                        >
                            {form.processing ? '儲存中...' : '儲存週報'}
                        </Button>
                    </div>
                </div>
            </form>
        </AppLayout>
    );
}
