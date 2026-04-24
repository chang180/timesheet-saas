import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { HolidayBanner } from '@/components/weekly-report/holiday-banner';
import { HoursStatsCard } from '@/components/weekly-report/hours-stats-card';
import AppLayout from '@/layouts/app-layout';
import { isWeekend } from '@/lib/date-utils';
import {
    buildHolidayMap,
    calculateWorkRangeStats,
    getHolidayDateStatus,
    getHolidayMarkerDates,
} from '@/lib/holiday-utils';
import type { HolidayEntry } from '@/pages/weekly/components/types';
import personal from '@/routes/personal';
import * as weeklyReportRoutes from '@/routes/personal/weekly-reports';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    CheckCircle2,
    Copy,
    Eye,
    Globe,
    Plus,
    Save,
    SendHorizonal,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';

type ItemInput = {
    id?: number | null;
    title: string;
    content?: string | null;
    hours_spent?: number | null;
    planned_hours?: number | null;
    issue_reference?: string | null;
    is_billable?: boolean;
    tags?: string[];
    started_at?: string | null;
    ended_at?: string | null;
};

type ReportPayload = {
    id: number;
    workYear: number;
    workWeek: number;
    status: string;
    isPublic?: boolean;
    publishedAt?: string | null;
    summary: string | null;
    currentWeek: ItemInput[];
    nextWeek: ItemInput[];
};

interface PersonalWeeklyReportFormProps {
    mode: 'create' | 'edit';
    report: ReportPayload | null;
    defaults: { year: number; week: number };
    weekDateRange: { startDate: string; endDate: string };
    nextWeekDateRange: { startDate: string; endDate: string };
    holidayCalendar?: {
        currentWeek: { year: number; week: number; holidays: HolidayEntry[] };
        nextWeek: { year: number; week: number; holidays: HolidayEntry[] };
        source: {
            name: string;
            dataset_url: string;
            api_url: string;
            provider: string;
        };
    };
}

const emptyCurrentItem = (): ItemInput => ({
    title: '',
    content: '',
    hours_spent: 0,
    planned_hours: null,
    issue_reference: null,
    is_billable: false,
    tags: [],
    started_at: null,
    ended_at: null,
});

const emptyNextItem = (): ItemInput => ({
    title: '',
    content: '',
    planned_hours: null,
    issue_reference: null,
    tags: [],
    started_at: null,
    ended_at: null,
});

export default function PersonalWeeklyReportForm({
    mode,
    report,
    defaults,
    weekDateRange,
    nextWeekDateRange,
    holidayCalendar,
}: PersonalWeeklyReportFormProps) {
    const { flash } = usePage<{
        flash: { success?: string; info?: string; warning?: string };
    }>().props;

    const { data, setData, post, put, processing, errors } = useForm<{
        work_year: number;
        work_week: number;
        summary: string;
        current_week: ItemInput[];
        next_week: ItemInput[];
    }>({
        work_year: report?.workYear ?? defaults.year,
        work_week: report?.workWeek ?? defaults.week,
        summary: report?.summary ?? '',
        current_week: report?.currentWeek ?? [emptyCurrentItem()],
        next_week: report?.nextWeek ?? [],
    });

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

    const totalCurrentWeekHours = data.current_week.reduce(
        (sum, item) => sum + (item.hours_spent || 0),
        0,
    );
    const totalNextWeekHours = data.next_week.reduce(
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

    const breadcrumbs: BreadcrumbItem[] = [
        { title: '我的週報', href: personal.weeklyReports.url() },
        {
            title: `${data.work_year} 年第 ${data.work_week} 週`,
            href:
                mode === 'edit' && report
                    ? personal.weeklyReports.edit.url({
                          weeklyReport: report.id,
                      })
                    : personal.weeklyReports.create.url(),
        },
    ];

    const submitForm = (e: React.FormEvent) => {
        e.preventDefault();
        if (mode === 'create') {
            post(personal.weeklyReports.store.url(), {
                preserveScroll: true,
            });
        } else if (report) {
            put(
                personal.weeklyReports.update.url({
                    weeklyReport: report.id,
                }),
                { preserveScroll: true },
            );
        }
    };

    const submitReport = () => {
        if (!report) return;
        router.post(
            personal.weeklyReports.submit.url({
                weeklyReport: report.id,
            }),
            {},
            { preserveScroll: true },
        );
    };

    const addCurrent = () =>
        setData('current_week', [...data.current_week, emptyCurrentItem()]);
    const addNext = () =>
        setData('next_week', [...data.next_week, emptyNextItem()]);

    const removeCurrent = (index: number) =>
        setData(
            'current_week',
            data.current_week.filter((_, i) => i !== index),
        );
    const removeNext = (index: number) =>
        setData(
            'next_week',
            data.next_week.filter((_, i) => i !== index),
        );

    const updateCurrent = (index: number, patch: Partial<ItemInput>): void => {
        setData(
            'current_week',
            data.current_week.map((item, i) =>
                i === index ? { ...item, ...patch } : item,
            ),
        );
    };
    const updateNext = (index: number, patch: Partial<ItemInput>): void => {
        setData(
            'next_week',
            data.next_week.map((item, i) =>
                i === index ? { ...item, ...patch } : item,
            ),
        );
    };

    const { auth } = usePage<SharedData>().props;
    const userHandle = (auth?.user as { handle?: string | null } | undefined)
        ?.handle;

    const status = report?.status ?? 'draft';
    const isSubmittable = status === 'draft' && mode === 'edit';
    const canShare = status === 'submitted' && !!userHandle && mode === 'edit';
    const isPublic = Boolean(report?.isPublic);
    const [copied, setCopied] = useState(false);

    const shareUrl =
        userHandle && report
            ? `${window.location.origin}/u/${userHandle}/${report.workYear}/${report.workWeek}`
            : null;

    const togglePublic = (next: boolean) => {
        if (!report) return;
        router.post(
            personal.weeklyReports.togglePublic.url({
                weeklyReport: report.id,
            }),
            { is_public: next ? 1 : 0 },
            { preserveScroll: true },
        );
    };

    const copyShareUrl = async () => {
        if (!shareUrl) return;
        try {
            await navigator.clipboard.writeText(shareUrl);
            setCopied(true);
            setTimeout(() => setCopied(false), 1800);
        } catch {
            // ignore
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head
                title={
                    mode === 'create'
                        ? '建立個人週報'
                        : `${data.work_year} 年第 ${data.work_week} 週`
                }
            />

            <form
                onSubmit={submitForm}
                className="flex flex-col gap-6 px-4 sm:px-6 lg:px-8"
            >
                <header className="flex flex-col gap-3">
                    <div className="flex items-center justify-between">
                        <Link
                            href={personal.weeklyReports.url()}
                            className="inline-flex w-fit items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                        >
                            <ArrowLeft className="size-4" />
                            返回列表
                        </Link>
                        {mode === 'edit' && report && (
                            <Button
                                asChild
                                variant="ghost"
                                size="sm"
                                className="gap-2"
                            >
                                <Link
                                    href={weeklyReportRoutes.show.url({
                                        weeklyReport: report.id,
                                    })}
                                >
                                    <Eye className="size-4" />
                                    檢視
                                </Link>
                            </Button>
                        )}
                    </div>
                    <div className="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between">
                        <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">
                            {data.work_year} 年 第 {data.work_week} 週
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            本週：{weekDateRange.startDate} →{' '}
                            {weekDateRange.endDate}
                            <span className="mx-2">·</span>
                            下週：{nextWeekDateRange.startDate} →{' '}
                            {nextWeekDateRange.endDate}
                        </p>
                    </div>
                </header>

                {(flash?.success || flash?.info || flash?.warning) && (
                    <div className="rounded-xl border border-border/60 bg-muted/50 p-3 text-sm">
                        {flash.success && (
                            <span className="text-emerald-700 dark:text-emerald-400">
                                {flash.success}
                            </span>
                        )}
                        {flash.info && (
                            <span className="text-sky-700 dark:text-sky-400">
                                {flash.info}
                            </span>
                        )}
                        {flash.warning && (
                            <span className="text-amber-700 dark:text-amber-400">
                                {flash.warning}
                            </span>
                        )}
                    </div>
                )}

                <HolidayBanner holidayCalendar={holidayCalendar} />

                <section className="grid gap-3 sm:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="work_year">年度</Label>
                        <Input
                            id="work_year"
                            type="number"
                            value={data.work_year}
                            onChange={(e) =>
                                setData('work_year', Number(e.target.value))
                            }
                            disabled={mode === 'edit'}
                        />
                        {errors.work_year && (
                            <p className="text-xs text-red-600">
                                {errors.work_year}
                            </p>
                        )}
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="work_week">週次</Label>
                        <Input
                            id="work_week"
                            type="number"
                            value={data.work_week}
                            onChange={(e) =>
                                setData('work_week', Number(e.target.value))
                            }
                            disabled={mode === 'edit'}
                        />
                        {errors.work_week && (
                            <p className="text-xs text-red-600">
                                {errors.work_week}
                            </p>
                        )}
                    </div>
                </section>

                <section className="space-y-2">
                    <Label htmlFor="summary">本週摘要</Label>
                    <Textarea
                        id="summary"
                        rows={3}
                        value={data.summary}
                        onChange={(e) => setData('summary', e.target.value)}
                        placeholder="這週整體進度、重點成果或挑戰"
                    />
                    {errors.summary && (
                        <p className="text-xs text-red-600">{errors.summary}</p>
                    )}
                </section>

                <section className="space-y-3">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold">本週工作項目</h2>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={addCurrent}
                            className="gap-1"
                        >
                            <Plus className="size-4" />
                            新增項目
                        </Button>
                    </div>
                    {data.current_week.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            尚未新增項目，點「新增項目」開始記錄。
                        </p>
                    ) : (
                        <div className="space-y-3">
                            {data.current_week.map((item, index) => (
                                <Card
                                    key={item.id ?? `c-${index}`}
                                    className="border-border/60"
                                >
                                    <CardContent className="flex flex-col gap-3 p-4">
                                        <div className="flex items-start gap-2">
                                            <Input
                                                value={item.title}
                                                placeholder="項目標題"
                                                onChange={(e) =>
                                                    updateCurrent(index, {
                                                        title: e.target.value,
                                                    })
                                                }
                                            />
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                onClick={() =>
                                                    removeCurrent(index)
                                                }
                                                aria-label="刪除項目"
                                            >
                                                <Trash2 className="size-4 text-red-600" />
                                            </Button>
                                        </div>
                                        <Textarea
                                            rows={2}
                                            value={item.content ?? ''}
                                            onChange={(e) =>
                                                updateCurrent(index, {
                                                    content: e.target.value,
                                                })
                                            }
                                            placeholder="詳細內容"
                                        />
                                        <div className="grid gap-2 sm:grid-cols-2">
                                            <div>
                                                <Label className="text-xs">
                                                    實際工時
                                                </Label>
                                                <Input
                                                    type="number"
                                                    step="0.5"
                                                    value={
                                                        item.hours_spent ?? 0
                                                    }
                                                    onChange={(e) =>
                                                        updateCurrent(index, {
                                                            hours_spent: Number(
                                                                e.target.value,
                                                            ),
                                                        })
                                                    }
                                                />
                                            </div>
                                            <div>
                                                <Label className="text-xs">
                                                    計畫工時
                                                </Label>
                                                <Input
                                                    type="number"
                                                    step="0.5"
                                                    value={
                                                        item.planned_hours ?? ''
                                                    }
                                                    onChange={(e) =>
                                                        updateCurrent(index, {
                                                            planned_hours: e
                                                                .target.value
                                                                ? Number(
                                                                      e.target
                                                                          .value,
                                                                  )
                                                                : null,
                                                        })
                                                    }
                                                />
                                            </div>
                                        </div>
                                        <div className="grid gap-2 sm:grid-cols-2">
                                            <div>
                                                <Label className="text-xs">
                                                    開始日期
                                                </Label>
                                                <DatePicker
                                                    value={
                                                        item.started_at ?? null
                                                    }
                                                    onChange={(v) =>
                                                        updateCurrent(index, {
                                                            started_at: v,
                                                        })
                                                    }
                                                    minDate={
                                                        weekDateRange.startDate
                                                    }
                                                    maxDate={
                                                        item.ended_at ??
                                                        weekDateRange.endDate
                                                    }
                                                    weekRange={weekDateRange}
                                                    holidayDates={
                                                        currentWeekHolidayMarkers.holidayDates
                                                    }
                                                    workdayOverrideDates={
                                                        currentWeekHolidayMarkers.workdayOverrideDates
                                                    }
                                                    className={
                                                        isWeekend(
                                                            item.started_at,
                                                        )
                                                            ? 'border-yellow-400 bg-yellow-50 dark:border-yellow-600 dark:bg-yellow-950/20'
                                                            : undefined
                                                    }
                                                />
                                                {(() => {
                                                    const status =
                                                        getHolidayDateStatus(
                                                            item.started_at,
                                                            currentWeekHolidayMap,
                                                        );
                                                    if (status) {
                                                        return (
                                                            <p
                                                                className={`mt-1 text-xs font-medium ${
                                                                    status.tone ===
                                                                    'holiday'
                                                                        ? 'text-rose-600 dark:text-rose-400'
                                                                        : status.tone ===
                                                                            'makeup_workday'
                                                                          ? 'text-emerald-600 dark:text-emerald-400'
                                                                          : 'text-yellow-600 dark:text-yellow-400'
                                                                }`}
                                                            >
                                                                {status.tone ===
                                                                'makeup_workday'
                                                                    ? 'ℹ️'
                                                                    : '⚠️'}{' '}
                                                                {status.label}．
                                                                {status.detail}
                                                            </p>
                                                        );
                                                    }
                                                    if (
                                                        isWeekend(
                                                            item.started_at,
                                                        )
                                                    ) {
                                                        return (
                                                            <p className="mt-1 text-xs font-medium text-yellow-600 dark:text-yellow-400">
                                                                ⚠️ 週末
                                                            </p>
                                                        );
                                                    }
                                                    return null;
                                                })()}
                                            </div>
                                            <div>
                                                <Label className="text-xs">
                                                    結束日期
                                                </Label>
                                                <DatePicker
                                                    value={
                                                        item.ended_at ?? null
                                                    }
                                                    onChange={(v) =>
                                                        updateCurrent(index, {
                                                            ended_at: v,
                                                        })
                                                    }
                                                    minDate={
                                                        item.started_at ??
                                                        weekDateRange.startDate
                                                    }
                                                    maxDate={
                                                        weekDateRange.endDate
                                                    }
                                                    weekRange={weekDateRange}
                                                    holidayDates={
                                                        currentWeekHolidayMarkers.holidayDates
                                                    }
                                                    workdayOverrideDates={
                                                        currentWeekHolidayMarkers.workdayOverrideDates
                                                    }
                                                    className={
                                                        isWeekend(item.ended_at)
                                                            ? 'border-yellow-400 bg-yellow-50 dark:border-yellow-600 dark:bg-yellow-950/20'
                                                            : undefined
                                                    }
                                                />
                                                {(() => {
                                                    const status =
                                                        getHolidayDateStatus(
                                                            item.ended_at,
                                                            currentWeekHolidayMap,
                                                        );
                                                    if (status) {
                                                        return (
                                                            <p
                                                                className={`mt-1 text-xs font-medium ${
                                                                    status.tone ===
                                                                    'holiday'
                                                                        ? 'text-rose-600 dark:text-rose-400'
                                                                        : status.tone ===
                                                                            'makeup_workday'
                                                                          ? 'text-emerald-600 dark:text-emerald-400'
                                                                          : 'text-yellow-600 dark:text-yellow-400'
                                                                }`}
                                                            >
                                                                {status.tone ===
                                                                'makeup_workday'
                                                                    ? 'ℹ️'
                                                                    : '⚠️'}{' '}
                                                                {status.label}．
                                                                {status.detail}
                                                            </p>
                                                        );
                                                    }
                                                    if (
                                                        isWeekend(item.ended_at)
                                                    ) {
                                                        return (
                                                            <p className="mt-1 text-xs font-medium text-yellow-600 dark:text-yellow-400">
                                                                ⚠️ 週末
                                                            </p>
                                                        );
                                                    }
                                                    return null;
                                                })()}
                                            </div>
                                        </div>
                                        {(() => {
                                            const rangeStats =
                                                calculateWorkRangeStats(
                                                    item.started_at,
                                                    item.ended_at,
                                                    currentWeekHolidayMap,
                                                );
                                            if (!rangeStats) return null;
                                            const exceeds =
                                                (item.hours_spent ?? 0) >
                                                rangeStats.availableHours;
                                            return (
                                                <div className="rounded-md border border-border/60 bg-muted/30 p-3 text-xs text-muted-foreground">
                                                    <p className="font-semibold text-foreground">
                                                        期間可用工時{' '}
                                                        {rangeStats.availableHours.toFixed(
                                                            1,
                                                        )}{' '}
                                                        小時
                                                    </p>
                                                    <p className="mt-1">
                                                        工作日{' '}
                                                        {rangeStats.workingDays}{' '}
                                                        天，扣除假日{' '}
                                                        {rangeStats.holidayDays}{' '}
                                                        天
                                                        {rangeStats.makeupWorkdays >
                                                            0 &&
                                                            `，含補班日 ${rangeStats.makeupWorkdays} 天`}
                                                        {rangeStats.holidayNames
                                                            .length > 0 &&
                                                            `（${rangeStats.holidayNames.join('、')}）`}
                                                    </p>
                                                    {exceeds && (
                                                        <p className="mt-2 font-semibold text-amber-700 dark:text-amber-300">
                                                            目前填寫{' '}
                                                            {(
                                                                item.hours_spent ??
                                                                0
                                                            ).toFixed(1)}{' '}
                                                            小時，高於區間可用工時{' '}
                                                            {rangeStats.availableHours.toFixed(
                                                                1,
                                                            )}{' '}
                                                            小時。
                                                        </p>
                                                    )}
                                                </div>
                                            );
                                        })()}
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    )}
                </section>

                <section className="space-y-3">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold">下週計畫</h2>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={addNext}
                            className="gap-1"
                        >
                            <Plus className="size-4" />
                            新增計畫
                        </Button>
                    </div>
                    {data.next_week.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            可先預規劃下週工作，非必填。
                        </p>
                    ) : (
                        <div className="space-y-3">
                            {data.next_week.map((item, index) => (
                                <Card
                                    key={item.id ?? `n-${index}`}
                                    className="border-border/60"
                                >
                                    <CardContent className="flex flex-col gap-3 p-4">
                                        <div className="flex items-start gap-2">
                                            <Input
                                                value={item.title}
                                                placeholder="計畫標題"
                                                onChange={(e) =>
                                                    updateNext(index, {
                                                        title: e.target.value,
                                                    })
                                                }
                                            />
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                onClick={() =>
                                                    removeNext(index)
                                                }
                                                aria-label="刪除計畫"
                                            >
                                                <Trash2 className="size-4 text-red-600" />
                                            </Button>
                                        </div>
                                        <Textarea
                                            rows={2}
                                            value={item.content ?? ''}
                                            onChange={(e) =>
                                                updateNext(index, {
                                                    content: e.target.value,
                                                })
                                            }
                                            placeholder="計畫描述"
                                        />
                                        <div>
                                            <Label className="text-xs">
                                                預估工時
                                            </Label>
                                            <Input
                                                type="number"
                                                step="0.5"
                                                value={item.planned_hours ?? ''}
                                                onChange={(e) =>
                                                    updateNext(index, {
                                                        planned_hours: e.target
                                                            .value
                                                            ? Number(
                                                                  e.target
                                                                      .value,
                                                              )
                                                            : null,
                                                    })
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2 sm:grid-cols-2">
                                            <div>
                                                <Label className="text-xs">
                                                    開始日期
                                                </Label>
                                                <DatePicker
                                                    value={
                                                        item.started_at ?? null
                                                    }
                                                    onChange={(v) =>
                                                        updateNext(index, {
                                                            started_at: v,
                                                        })
                                                    }
                                                    minDate={
                                                        nextWeekDateRange.startDate
                                                    }
                                                    maxDate={
                                                        item.ended_at ??
                                                        nextWeekDateRange.endDate
                                                    }
                                                    weekRange={
                                                        nextWeekDateRange
                                                    }
                                                    holidayDates={
                                                        nextWeekHolidayMarkers.holidayDates
                                                    }
                                                    workdayOverrideDates={
                                                        nextWeekHolidayMarkers.workdayOverrideDates
                                                    }
                                                    className={
                                                        isWeekend(
                                                            item.started_at,
                                                        )
                                                            ? 'border-yellow-400 bg-yellow-50 dark:border-yellow-600 dark:bg-yellow-950/20'
                                                            : undefined
                                                    }
                                                />
                                                {(() => {
                                                    const status =
                                                        getHolidayDateStatus(
                                                            item.started_at,
                                                            nextWeekHolidayMap,
                                                        );
                                                    if (status) {
                                                        return (
                                                            <p
                                                                className={`mt-1 text-xs font-medium ${
                                                                    status.tone ===
                                                                    'holiday'
                                                                        ? 'text-rose-600 dark:text-rose-400'
                                                                        : status.tone ===
                                                                            'makeup_workday'
                                                                          ? 'text-emerald-600 dark:text-emerald-400'
                                                                          : 'text-yellow-600 dark:text-yellow-400'
                                                                }`}
                                                            >
                                                                {status.tone ===
                                                                'makeup_workday'
                                                                    ? 'ℹ️'
                                                                    : '⚠️'}{' '}
                                                                {status.label}．
                                                                {status.detail}
                                                            </p>
                                                        );
                                                    }
                                                    if (
                                                        isWeekend(
                                                            item.started_at,
                                                        )
                                                    ) {
                                                        return (
                                                            <p className="mt-1 text-xs font-medium text-yellow-600 dark:text-yellow-400">
                                                                ⚠️ 週末
                                                            </p>
                                                        );
                                                    }
                                                    return null;
                                                })()}
                                            </div>
                                            <div>
                                                <Label className="text-xs">
                                                    結束日期
                                                </Label>
                                                <DatePicker
                                                    value={
                                                        item.ended_at ?? null
                                                    }
                                                    onChange={(v) =>
                                                        updateNext(index, {
                                                            ended_at: v,
                                                        })
                                                    }
                                                    minDate={
                                                        item.started_at ??
                                                        nextWeekDateRange.startDate
                                                    }
                                                    maxDate={
                                                        nextWeekDateRange.endDate
                                                    }
                                                    weekRange={
                                                        nextWeekDateRange
                                                    }
                                                    holidayDates={
                                                        nextWeekHolidayMarkers.holidayDates
                                                    }
                                                    workdayOverrideDates={
                                                        nextWeekHolidayMarkers.workdayOverrideDates
                                                    }
                                                    className={
                                                        isWeekend(item.ended_at)
                                                            ? 'border-yellow-400 bg-yellow-50 dark:border-yellow-600 dark:bg-yellow-950/20'
                                                            : undefined
                                                    }
                                                />
                                                {(() => {
                                                    const status =
                                                        getHolidayDateStatus(
                                                            item.ended_at,
                                                            nextWeekHolidayMap,
                                                        );
                                                    if (status) {
                                                        return (
                                                            <p
                                                                className={`mt-1 text-xs font-medium ${
                                                                    status.tone ===
                                                                    'holiday'
                                                                        ? 'text-rose-600 dark:text-rose-400'
                                                                        : status.tone ===
                                                                            'makeup_workday'
                                                                          ? 'text-emerald-600 dark:text-emerald-400'
                                                                          : 'text-yellow-600 dark:text-yellow-400'
                                                                }`}
                                                            >
                                                                {status.tone ===
                                                                'makeup_workday'
                                                                    ? 'ℹ️'
                                                                    : '⚠️'}{' '}
                                                                {status.label}．
                                                                {status.detail}
                                                            </p>
                                                        );
                                                    }
                                                    if (
                                                        isWeekend(item.ended_at)
                                                    ) {
                                                        return (
                                                            <p className="mt-1 text-xs font-medium text-yellow-600 dark:text-yellow-400">
                                                                ⚠️ 週末
                                                            </p>
                                                        );
                                                    }
                                                    return null;
                                                })()}
                                            </div>
                                        </div>
                                        {(() => {
                                            const rangeStats =
                                                calculateWorkRangeStats(
                                                    item.started_at,
                                                    item.ended_at,
                                                    nextWeekHolidayMap,
                                                );
                                            if (!rangeStats) return null;
                                            const exceeds =
                                                (item.planned_hours ?? 0) >
                                                rangeStats.availableHours;
                                            return (
                                                <div className="rounded-md border border-border/60 bg-muted/30 p-3 text-xs text-muted-foreground">
                                                    <p className="font-semibold text-foreground">
                                                        期間可用工時{' '}
                                                        {rangeStats.availableHours.toFixed(
                                                            1,
                                                        )}{' '}
                                                        小時
                                                    </p>
                                                    <p className="mt-1">
                                                        工作日{' '}
                                                        {rangeStats.workingDays}{' '}
                                                        天，扣除假日{' '}
                                                        {rangeStats.holidayDays}{' '}
                                                        天
                                                        {rangeStats.makeupWorkdays >
                                                            0 &&
                                                            `，含補班日 ${rangeStats.makeupWorkdays} 天`}
                                                        {rangeStats.holidayNames
                                                            .length > 0 &&
                                                            `（${rangeStats.holidayNames.join('、')}）`}
                                                    </p>
                                                    {exceeds && (
                                                        <p className="mt-2 font-semibold text-amber-700 dark:text-amber-300">
                                                            目前填寫{' '}
                                                            {(
                                                                item.planned_hours ??
                                                                0
                                                            ).toFixed(1)}{' '}
                                                            小時，高於區間可用工時{' '}
                                                            {rangeStats.availableHours.toFixed(
                                                                1,
                                                            )}{' '}
                                                            小時。
                                                        </p>
                                                    )}
                                                </div>
                                            );
                                        })()}
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    )}
                </section>

                <HoursStatsCard
                    totalCurrentWeekHours={totalCurrentWeekHours}
                    totalNextWeekHours={totalNextWeekHours}
                    currentWeekCapacity={currentWeekCapacity}
                    nextWeekCapacity={nextWeekCapacity}
                    currentWeekItemCount={data.current_week.length}
                    nextWeekItemCount={data.next_week.length}
                />

                {mode === 'edit' && report && (
                    <section className="space-y-3">
                        <h2 className="flex items-center gap-2 text-lg font-semibold">
                            <Globe className="size-5" />
                            公開分享
                        </h2>

                        {!userHandle && (
                            <p className="text-sm text-muted-foreground">
                                請先到{' '}
                                <Link
                                    href="/settings/handle"
                                    className="text-primary underline"
                                >
                                    設定 → 我的代號
                                </Link>{' '}
                                建立你的代號才能公開分享。
                            </p>
                        )}

                        {userHandle && status !== 'submitted' && (
                            <p className="text-sm text-muted-foreground">
                                需先提交（submit）週報才能公開分享。
                            </p>
                        )}

                        {canShare && (
                            <div className="flex flex-col gap-2">
                                <div className="flex items-center gap-2">
                                    <Button
                                        type="button"
                                        variant={
                                            isPublic ? 'default' : 'outline'
                                        }
                                        onClick={() => togglePublic(!isPublic)}
                                        disabled={processing}
                                    >
                                        {isPublic ? '已公開' : '公開此週報'}
                                    </Button>
                                    {(errors as Record<string, string>)
                                        .is_public && (
                                        <span className="text-xs text-red-600">
                                            {
                                                (
                                                    errors as Record<
                                                        string,
                                                        string
                                                    >
                                                ).is_public
                                            }
                                        </span>
                                    )}
                                </div>

                                {isPublic && shareUrl && (
                                    <div className="flex flex-col gap-1 rounded-md border border-border/60 bg-muted/30 p-3 text-sm">
                                        <span className="text-xs text-muted-foreground">
                                            你的公開連結
                                        </span>
                                        <div className="flex items-center gap-2">
                                            <code className="flex-1 rounded bg-background px-2 py-1 text-xs">
                                                {shareUrl}
                                            </code>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                onClick={copyShareUrl}
                                                className="gap-1"
                                            >
                                                <Copy className="size-3" />
                                                {copied ? '已複製' : '複製'}
                                            </Button>
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}
                    </section>
                )}

                <div className="sticky bottom-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border-2 border-border/60 bg-background/95 p-4 shadow-lg backdrop-blur">
                    <div className="flex items-center gap-2 text-sm">
                        {status === 'submitted' ? (
                            <span className="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                                <CheckCircle2 className="size-3" />
                                已送出
                            </span>
                        ) : (
                            <span className="text-muted-foreground">
                                狀態：草稿
                            </span>
                        )}
                    </div>
                    <div className="flex items-center gap-2">
                        <Button
                            type="submit"
                            disabled={processing}
                            className="gap-2"
                        >
                            <Save className="size-4" />
                            {mode === 'create' ? '建立草稿' : '儲存'}
                        </Button>
                        {isSubmittable && (
                            <Button
                                type="button"
                                variant="default"
                                onClick={submitReport}
                                disabled={processing}
                                className="gap-2"
                            >
                                <SendHorizonal className="size-4" />
                                提交
                            </Button>
                        )}
                    </div>
                </div>
            </form>
        </AppLayout>
    );
}
