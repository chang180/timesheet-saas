import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { HoursStatsCard } from '@/components/weekly-report/hours-stats-card';
import AppLayout from '@/layouts/app-layout';
import { buildHolidayMap, calculateWorkRangeStats } from '@/lib/holiday-utils';
import type { HolidayEntry } from '@/pages/weekly/components/types';
import personal from '@/routes/personal';
import * as weeklyReportRoutes from '@/routes/personal/weekly-reports';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarDays,
    CheckCircle2,
    Clock,
    Globe,
    Lock,
    Pencil,
    Target,
    type LucideIcon,
} from 'lucide-react';
import { useMemo } from 'react';

interface ReportItem {
    title: string;
    content: string | null;
    hoursSpent?: number | null;
    plannedHours?: number | null;
    tags: string[];
    startedAt?: string | null;
    endedAt?: string | null;
}

interface DateRange {
    startDate: string;
    endDate: string;
}

interface HolidayCalendar {
    currentWeek: { year: number; week: number; holidays: HolidayEntry[] };
    nextWeek: { year: number; week: number; holidays: HolidayEntry[] };
    source: {
        name: string;
        dataset_url: string;
        api_url: string;
        provider: string;
    };
}

interface PersonalWeeklyReportShowProps {
    report: {
        id: number;
        workYear: number;
        workWeek: number;
        status: string;
        isPublic: boolean;
        publishedAt: string | null;
        summary: string | null;
        currentWeek: ReportItem[];
        nextWeek: ReportItem[];
    };
    user: {
        name: string;
        handle: string | null;
    };
    weekDateRange?: DateRange | null;
    nextWeekDateRange?: DateRange | null;
    holidayCalendar?: HolidayCalendar | null;
}

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

function formatDate(dateStr: string | null | undefined): string {
    if (!dateStr) {
        return '';
    }

    const date = new Date(dateStr);

    return date.toLocaleDateString('zh-TW', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        weekday: 'short',
    });
}

export default function PersonalWeeklyReportShow({
    report,
    user,
    weekDateRange,
    nextWeekDateRange,
    holidayCalendar,
}: PersonalWeeklyReportShowProps) {
    const statusConfig = STATUS_CONFIG[report.status] ?? STATUS_CONFIG.draft;
    const StatusIcon = statusConfig.icon;

    const holidayMap = useMemo(() => {
        const entries = [
            ...(holidayCalendar?.currentWeek.holidays ?? []),
            ...(holidayCalendar?.nextWeek.holidays ?? []),
        ];
        return buildHolidayMap(entries);
    }, [holidayCalendar]);

    const currentWeekCapacity = useMemo(
        () =>
            weekDateRange
                ? calculateWorkRangeStats(
                      weekDateRange.startDate,
                      weekDateRange.endDate,
                      holidayMap,
                  )
                : null,
        [weekDateRange, holidayMap],
    );

    const nextWeekCapacity = useMemo(
        () =>
            nextWeekDateRange
                ? calculateWorkRangeStats(
                      nextWeekDateRange.startDate,
                      nextWeekDateRange.endDate,
                      holidayMap,
                  )
                : null,
        [nextWeekDateRange, holidayMap],
    );

    const totalCurrentWeekHours = useMemo(
        () =>
            report.currentWeek.reduce(
                (sum, item) => sum + (item.hoursSpent ?? 0),
                0,
            ),
        [report.currentWeek],
    );

    const totalNextWeekHours = useMemo(
        () =>
            report.nextWeek.reduce(
                (sum, item) => sum + (item.plannedHours ?? 0),
                0,
            ),
        [report.nextWeek],
    );

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: '我的週報',
            href: personal.weeklyReports.url(),
        },
        {
            title: `${report.workYear} 年 第 ${report.workWeek} 週`,
            href: weeklyReportRoutes.show.url({ weeklyReport: report.id }),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head
                title={`${report.workYear} 年 第 ${report.workWeek} 週 · 週報檢視`}
            />

            <div className="mx-auto flex max-w-3xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
                <div className="flex items-center justify-between gap-4">
                    <Link
                        href={personal.weeklyReports.url()}
                        className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        回到我的週報
                    </Link>
                    <Button
                        asChild
                        variant="outline"
                        size="sm"
                        className="gap-2"
                    >
                        <Link
                            href={weeklyReportRoutes.edit.url({
                                weeklyReport: report.id,
                            })}
                        >
                            <Pencil className="size-4" />
                            編輯
                        </Link>
                    </Button>
                </div>

                <header className="space-y-2">
                    <div className="flex flex-wrap items-center gap-3">
                        <h1 className="text-3xl font-bold tracking-tight text-foreground sm:text-4xl">
                            {report.workYear} 年 第 {report.workWeek} 週
                        </h1>
                        <span
                            className={`inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs font-medium ${statusConfig.className}`}
                        >
                            <StatusIcon className="size-3" />
                            {statusConfig.text}
                        </span>
                        {report.isPublic && (
                            <span className="inline-flex items-center gap-1 rounded-full border border-sky-200 bg-sky-50 px-2.5 py-0.5 text-xs font-medium text-sky-700 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-400">
                                <Globe className="size-3" />
                                公開中
                            </span>
                        )}
                    </div>
                    <p className="text-sm text-muted-foreground">
                        {user.name}
                        {report.publishedAt &&
                            ` · 於 ${new Date(report.publishedAt).toLocaleDateString('zh-TW')} 公開發布`}
                    </p>
                    {weekDateRange && (
                        <p className="flex items-center gap-1.5 text-sm text-muted-foreground">
                            <CalendarDays className="size-4" />
                            {formatDate(weekDateRange.startDate)} ～{' '}
                            {formatDate(weekDateRange.endDate)}
                        </p>
                    )}
                </header>

                {report.summary && (
                    <Card>
                        <CardContent className="p-5 text-sm leading-relaxed text-foreground">
                            {report.summary}
                        </CardContent>
                    </Card>
                )}

                <section className="space-y-3">
                    <h2 className="flex items-center gap-2 text-lg font-semibold">
                        <CheckCircle2 className="size-5 text-emerald-600" />
                        本週工作
                    </h2>
                    {report.currentWeek.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            本週無工作項目紀錄。
                        </p>
                    ) : (
                        <div className="space-y-3">
                            {report.currentWeek.map((item, i) => {
                                const rangeStats =
                                    item.startedAt && item.endedAt
                                        ? calculateWorkRangeStats(
                                              item.startedAt,
                                              item.endedAt,
                                              holidayMap,
                                          )
                                        : null;

                                return (
                                    <Card key={i}>
                                        <CardContent className="space-y-2 p-4">
                                            <div className="flex flex-wrap items-start justify-between gap-2">
                                                <h3 className="font-semibold text-foreground">
                                                    {item.title}
                                                </h3>
                                                {item.startedAt &&
                                                    item.endedAt && (
                                                        <Badge
                                                            variant="outline"
                                                            className="shrink-0 gap-1 text-xs text-muted-foreground"
                                                        >
                                                            <CalendarDays className="size-3" />
                                                            {formatDate(
                                                                item.startedAt,
                                                            )}{' '}
                                                            ～{' '}
                                                            {formatDate(
                                                                item.endedAt,
                                                            )}
                                                        </Badge>
                                                    )}
                                            </div>
                                            {item.content && (
                                                <p className="text-sm whitespace-pre-wrap text-muted-foreground">
                                                    {item.content}
                                                </p>
                                            )}
                                            {typeof item.hoursSpent ===
                                                'number' &&
                                                item.hoursSpent > 0 && (
                                                    <p className="text-xs text-muted-foreground">
                                                        工時：
                                                        {item.hoursSpent.toFixed(
                                                            1,
                                                        )}{' '}
                                                        小時
                                                    </p>
                                                )}
                                            {rangeStats && (
                                                <div className="rounded-md border border-border/60 bg-muted/30 px-3 py-2 text-xs text-muted-foreground">
                                                    工作日{' '}
                                                    {rangeStats.workingDays} 天
                                                    {rangeStats.makeupWorkdays >
                                                        0 &&
                                                        `（含補班 ${rangeStats.makeupWorkdays} 天）`}
                                                    ，可用工時{' '}
                                                    {rangeStats.availableHours}{' '}
                                                    小時
                                                    {rangeStats.holidayDays >
                                                        0 &&
                                                        `，假日/休息 ${rangeStats.holidayDays} 天`}
                                                </div>
                                            )}
                                        </CardContent>
                                    </Card>
                                );
                            })}
                        </div>
                    )}
                </section>

                {report.nextWeek.length > 0 && (
                    <section className="space-y-3">
                        <h2 className="flex items-center gap-2 text-lg font-semibold">
                            <Target className="size-5 text-sky-600" />
                            下週計畫
                        </h2>
                        <div className="space-y-3">
                            {report.nextWeek.map((item, i) => {
                                const rangeStats =
                                    item.startedAt && item.endedAt
                                        ? calculateWorkRangeStats(
                                              item.startedAt,
                                              item.endedAt,
                                              holidayMap,
                                          )
                                        : null;

                                return (
                                    <Card key={i}>
                                        <CardContent className="space-y-2 p-4">
                                            <div className="flex flex-wrap items-start justify-between gap-2">
                                                <h3 className="font-semibold text-foreground">
                                                    {item.title}
                                                </h3>
                                                {item.startedAt &&
                                                    item.endedAt && (
                                                        <Badge
                                                            variant="outline"
                                                            className="shrink-0 gap-1 text-xs text-muted-foreground"
                                                        >
                                                            <CalendarDays className="size-3" />
                                                            {formatDate(
                                                                item.startedAt,
                                                            )}{' '}
                                                            ～{' '}
                                                            {formatDate(
                                                                item.endedAt,
                                                            )}
                                                        </Badge>
                                                    )}
                                            </div>
                                            {item.content && (
                                                <p className="text-sm whitespace-pre-wrap text-muted-foreground">
                                                    {item.content}
                                                </p>
                                            )}
                                            {item.plannedHours && (
                                                <p className="text-xs text-muted-foreground">
                                                    預估：
                                                    {item.plannedHours.toFixed(
                                                        1,
                                                    )}{' '}
                                                    小時
                                                </p>
                                            )}
                                            {rangeStats && (
                                                <div className="rounded-md border border-border/60 bg-muted/30 px-3 py-2 text-xs text-muted-foreground">
                                                    工作日{' '}
                                                    {rangeStats.workingDays} 天
                                                    {rangeStats.makeupWorkdays >
                                                        0 &&
                                                        `（含補班 ${rangeStats.makeupWorkdays} 天）`}
                                                    ，可用工時{' '}
                                                    {rangeStats.availableHours}{' '}
                                                    小時
                                                    {rangeStats.holidayDays >
                                                        0 &&
                                                        `，假日/休息 ${rangeStats.holidayDays} 天`}
                                                </div>
                                            )}
                                        </CardContent>
                                    </Card>
                                );
                            })}
                        </div>
                    </section>
                )}

                {(currentWeekCapacity || nextWeekCapacity) && (
                    <HoursStatsCard
                        totalCurrentWeekHours={totalCurrentWeekHours}
                        totalNextWeekHours={totalNextWeekHours}
                        currentWeekCapacity={currentWeekCapacity}
                        nextWeekCapacity={nextWeekCapacity}
                        currentWeekItemCount={report.currentWeek.length}
                        nextWeekItemCount={report.nextWeek.length}
                    />
                )}
            </div>
        </AppLayout>
    );
}
