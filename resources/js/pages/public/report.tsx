import { PromotionBanner } from '@/components/public/promotion-banner';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { buildHolidayMap, calculateWorkRangeStats } from '@/lib/holiday-utils';
import type { HolidayEntry } from '@/pages/weekly/components/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, CalendarDays, CheckCircle2, Target } from 'lucide-react';
import { useMemo } from 'react';

interface PublicItem {
    title: string;
    content: string | null;
    hoursSpent?: number;
    plannedHours?: number | null;
    tags: string[];
    startedAt?: string | null;
    endedAt?: string | null;
}

interface PublicReport {
    workYear: number;
    workWeek: number;
    summary: string | null;
    publishedAt: string | null;
    currentWeek: PublicItem[];
    nextWeek: PublicItem[];
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

interface PublicReportPageProps {
    profile: { handle: string; name: string };
    report: PublicReport;
    weekDateRange?: { startDate: string; endDate: string } | null;
    holidayCalendar?: HolidayCalendar | null;
}

function formatDate(dateStr: string | null | undefined): string {
    if (!dateStr) {
        return '';
    }

    return new Date(dateStr).toLocaleDateString('zh-TW', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        weekday: 'short',
    });
}

export default function PublicReportPage({
    profile,
    report,
    weekDateRange,
    holidayCalendar,
}: PublicReportPageProps) {
    const holidayMap = useMemo(() => {
        const entries = [
            ...(holidayCalendar?.currentWeek.holidays ?? []),
            ...(holidayCalendar?.nextWeek.holidays ?? []),
        ];
        return buildHolidayMap(entries);
    }, [holidayCalendar]);

    return (
        <div className="flex min-h-screen flex-col bg-muted/30">
            <Head
                title={`${profile.name} · ${report.workYear}/${report.workWeek} · 週報通`}
            >
                <meta
                    name="description"
                    content={`${profile.name} 於 ${report.workYear} 年第 ${report.workWeek} 週的公開週報`}
                />
                <meta
                    property="og:title"
                    content={`${profile.name} · ${report.workYear}/${report.workWeek}`}
                />
                <meta
                    property="og:description"
                    content={`查看 @${profile.handle} 的公開週報`}
                />
                <meta property="og:type" content="article" />
            </Head>

            <main className="flex-1">
                <div className="mx-auto flex max-w-3xl flex-col gap-6 px-4 py-10 sm:px-6 lg:px-8">
                    <Link
                        href={`/u/${profile.handle}`}
                        className="inline-flex w-fit items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        回到 {profile.name} 的所有週報
                    </Link>

                    <header className="space-y-2">
                        <h1 className="text-3xl font-bold tracking-tight text-foreground sm:text-4xl">
                            {report.workYear} 年 第 {report.workWeek} 週
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            由 {profile.name}
                            {report.publishedAt &&
                                ` 於 ${new Date(report.publishedAt).toLocaleDateString('zh-TW')} 發布`}
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
                                                        {rangeStats.workingDays}{' '}
                                                        天
                                                        {rangeStats.makeupWorkdays >
                                                            0 &&
                                                            `（含補班 ${rangeStats.makeupWorkdays} 天）`}
                                                        ，可用工時{' '}
                                                        {
                                                            rangeStats.availableHours
                                                        }{' '}
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
                                                        {rangeStats.workingDays}{' '}
                                                        天
                                                        {rangeStats.makeupWorkdays >
                                                            0 &&
                                                            `（含補班 ${rangeStats.makeupWorkdays} 天）`}
                                                        ，可用工時{' '}
                                                        {
                                                            rangeStats.availableHours
                                                        }{' '}
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
                </div>
            </main>

            <PromotionBanner />
        </div>
    );
}
