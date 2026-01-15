import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import tenantRoutes from '@/routes/tenant';
import * as weeklyRoutes from '@/routes/tenant/weekly-reports';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, ClipboardList, PenSquare, SquarePen, CheckCircle2, Clock, Lock, Eye, type LucideIcon } from 'lucide-react';

type WeeklyReportSummary = {
    id: number;
    workYear: number;
    workWeek: number;
    status: string;
    summary: string | null;
    totalHours: number;
    updatedAt?: string | null;
};

interface WeeklyReportListProps {
    reports: WeeklyReportSummary[];
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
}

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

export default function WeeklyReportList(props: WeeklyReportListProps) {
    const {
        tenant,
        flash,
    } = usePage<SharedData & { flash: { success?: string; info?: string; warning?: string } }>().props;

    const sharedCompanySlug = (tenant?.company as { slug?: string } | undefined)?.slug;
    const companySlug = props.company?.slug ?? sharedCompanySlug ?? '';
    const reports = props.reports ?? [];
    const defaults = props.defaults;
    const latestReport = reports[0];

    // 檢查是否有本週週報
    const currentWeekReport = reports.find(
        (report) => report.workYear === defaults.year && report.workWeek === defaults.week,
    );

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: '週報工作簿',
            href: tenantRoutes.weeklyReports.url({ company: companySlug }),
        },
    ];

    const canCreate = companySlug.length > 0;
    const createHref = canCreate
        ? weeklyRoutes.create.url({ company: companySlug })
        : '#';
    const editCurrentWeekHref = currentWeekReport && canCreate
        ? weeklyRoutes.edit.url({ company: companySlug, weeklyReport: currentWeekReport.id })
        : '#';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="週報工作簿" />

            <div className="flex flex-col gap-8 px-4 sm:px-6 lg:px-8">
                <header className="space-y-3">
                    <h1 className="text-3xl font-bold tracking-tight text-foreground sm:text-4xl">
                        {props.company?.name
                            ? `${props.company.name} 的週報工作簿`
                            : tenant?.company?.name
                              ? `${tenant.company.name} 的週報工作簿`
                            : '週報工作簿'}
                    </h1>
                    <p className="text-sm leading-relaxed text-muted-foreground sm:text-base">
                        記錄本週每一項任務、支援與會議重點，週會或主管提報時可以立即找到摘要。
                    </p>
                </header>

                {(flash?.success || flash?.info || flash?.warning) && (
                    <div className="rounded-xl border-2 border-border/60 bg-muted/50 p-4 shadow-md backdrop-blur-sm">
                        {flash.success && (
                            <div className="flex items-center gap-2">
                                <CheckCircle2 className="size-5 text-emerald-600 dark:text-emerald-400" />
                                <p className="text-sm font-semibold text-emerald-700 dark:text-emerald-400">
                                    {flash.success}
                                </p>
                            </div>
                        )}
                        {flash.info && (
                            <div className="flex items-center gap-2">
                                <ClipboardList className="size-5 text-sky-600 dark:text-sky-400" />
                                <p className="text-sm font-semibold text-sky-700 dark:text-sky-400">
                                    {flash.info}
                                </p>
                            </div>
                        )}
                        {flash.warning && (
                            <div className="flex items-center gap-2">
                                <Clock className="size-5 text-amber-600 dark:text-amber-400" />
                                <p className="text-sm font-semibold text-amber-700 dark:text-amber-400">
                                    {flash.warning}
                                </p>
                            </div>
                        )}
                    </div>
                )}

                <section className="grid gap-6 sm:grid-cols-2">
                    <Card className="group flex flex-col border-2 border-border/60 bg-card shadow-sm transition-all duration-300 hover:shadow-lg hover:border-primary/40 hover:-translate-y-1">
                        <CardContent className="flex flex-1 flex-col gap-5 p-6">
                            <div className="flex size-16 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-100 via-indigo-50 to-indigo-100/50 text-indigo-600 shadow-md transition-all duration-300 group-hover:scale-110 group-hover:shadow-lg dark:from-indigo-500/20 dark:via-indigo-500/10 dark:to-indigo-500/5 dark:text-indigo-400">
                                <PenSquare className="size-8" />
                            </div>
                            <div className="space-y-2">
                                <h2 className="text-xl font-semibold text-foreground">填寫本週週報</h2>
                                <p className="text-sm leading-relaxed text-muted-foreground">
                                    快速記錄本週任務、臨時支援與會議重點。若已建立草稿，系統會自動帶你到該筆週報。
                                </p>
                            </div>
                            {canCreate ? (
                                <Button
                                    asChild
                                    className="mt-auto w-full gap-2 sm:w-auto"
                                >
                                    <Link
                                        href={currentWeekReport ? editCurrentWeekHref : createHref}
                                        data-testid={currentWeekReport ? 'goto-weekly-report-edit' : 'goto-weekly-report-create'}
                                    >
                                        {currentWeekReport ? '前往編輯' : '前往填寫'}
                                        <ArrowRight className="size-4" />
                                    </Link>
                                </Button>
                            ) : (
                                <Button disabled className="mt-auto w-full gap-2 sm:w-auto">
                                    前往填寫
                                </Button>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="group flex flex-col border-2 border-border/60 bg-card shadow-sm transition-all duration-300 hover:shadow-lg hover:border-primary/40 hover:-translate-y-1">
                        <CardContent className="flex flex-1 flex-col gap-5 p-6">
                            <div className="flex size-16 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-100 via-emerald-50 to-emerald-100/50 text-emerald-600 shadow-md transition-all duration-300 group-hover:scale-110 group-hover:shadow-lg dark:from-emerald-500/20 dark:via-emerald-500/10 dark:to-emerald-500/5 dark:text-emerald-400">
                                <ClipboardList className="size-8" />
                            </div>
                            <div className="space-y-2">
                                <h2 className="text-xl font-semibold text-foreground">查看週報歷史</h2>
                                <p className="text-sm leading-relaxed text-muted-foreground">
                                    蒐集過往週報、下載摘要或準備主管提報，可依週數快速定位並進入編輯。
                                </p>
                            </div>
                            {latestReport ? (
                                <Button
                                    variant="outline"
                                    asChild
                                    className="mt-auto w-full gap-2 sm:w-auto"
                                >
                                    <Link
                                        href={weeklyRoutes.edit.url({
                                            company: companySlug,
                                            weeklyReport: latestReport.id,
                                        })}
                                        data-testid="view-latest-weekly-report"
                                    >
                                        檢視週報
                                        <ArrowRight className="size-4" />
                                    </Link>
                                </Button>
                            ) : (
                                <Button
                                    variant="outline"
                                    disabled
                                    className="mt-auto w-full gap-2 sm:w-auto"
                                >
                                    尚無週報
                                </Button>
                            )}
                        </CardContent>
                    </Card>
                </section>

                <section id="report-list" className="space-y-6">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 className="text-xl font-bold text-foreground sm:text-2xl">
                                最近週報 · 第 {defaults.year} 年第 {defaults.week} 週
                            </h2>
                            {props.weekDateRange && (
                                <p className="mt-2 text-sm text-muted-foreground sm:text-base">
                                    {props.weekDateRange.startDate} ~ {props.weekDateRange.endDate}
                                </p>
                            )}
                        </div>
                        <div className="flex items-center gap-2">
                            <span className="rounded-full border-2 border-border/60 bg-muted/50 px-4 py-1.5 text-xs font-semibold text-foreground shadow-sm">
                                共 {reports.length} 筆
                            </span>
                        </div>
                    </div>

                    {reports.length === 0 ? (
                        <Card className="border-2 border-dashed border-border/50 bg-gradient-to-br from-muted/30 to-muted/10">
                            <CardContent className="flex flex-col items-center justify-center p-16 text-center">
                                <div className="mb-6 flex size-20 items-center justify-center rounded-2xl bg-gradient-to-br from-muted to-muted/50 shadow-sm">
                                    <ClipboardList className="size-10 text-muted-foreground" />
                                </div>
                                <h3 className="mb-3 text-xl font-semibold text-foreground">尚未建立任何週報</h3>
                                <p className="mb-8 max-w-sm text-sm leading-relaxed text-muted-foreground">
                                    點選「填寫本週週報」開始紀錄你的第一份週報，讓工作更有條理。
                                </p>
                                {canCreate && (
                                    <Button asChild size="lg" className="gap-2">
                                        <Link href={createHref}>
                                            <SquarePen className="size-5" />
                                            建立第一份週報
                                        </Link>
                                    </Button>
                                )}
                            </CardContent>
                        </Card>
                    ) : (
                        <Card className="overflow-hidden border-2 border-border/60 shadow-md">
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-border/50">
                                    <thead className="bg-gradient-to-r from-muted/60 to-muted/40">
                                        <tr>
                                            <th className="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-foreground first:pl-6 last:pr-6">
                                                週次
                                            </th>
                                            <th className="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-foreground">
                                                狀態
                                            </th>
                                            <th className="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-foreground">
                                                總工時
                                            </th>
                                            <th className="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-foreground">
                                                摘要
                                            </th>
                                            <th className="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-foreground">
                                                更新時間
                                            </th>
                                            <th className="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider text-foreground last:pr-6">
                                                操作
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-border/50 bg-card">
                                        {reports.map((report) => {
                                            // 計算 ISO 週的日期範圍
                                            // ISO 週從週一開始，週日結束
                                            const getISOWeeks = (year: number): Date[] => {
                                                const jan4 = new Date(year, 0, 4);
                                                const jan4Day = jan4.getDay() || 7;
                                                const week1Start = new Date(jan4);
                                                week1Start.setDate(jan4.getDate() - jan4Day + 1);
                                                return [week1Start];
                                            };

                                            const week1Start = getISOWeeks(report.workYear)[0];
                                            const weekStart = new Date(week1Start);
                                            weekStart.setDate(week1Start.getDate() + (report.workWeek - 1) * 7);
                                            const weekEnd = new Date(weekStart);
                                            weekEnd.setDate(weekStart.getDate() + 6);
                                            const startDateStr = weekStart.toISOString().split('T')[0];
                                            const endDateStr = weekEnd.toISOString().split('T')[0];

                                            const statusConfig = STATUS_CONFIG[report.status] ?? {
                                                text: report.status,
                                                icon: Clock,
                                                className: 'bg-muted text-muted-foreground border-border',
                                            };
                                            const StatusIcon = statusConfig.icon;

                                            return (
                                                <tr
                                                    key={report.id}
                                                    className="transition-all duration-200 hover:bg-muted/40 hover:shadow-sm"
                                                >
                                                    <td className="whitespace-nowrap px-6 py-5 first:pl-6">
                                                        <div className="flex flex-col gap-1">
                                                            <span className="font-semibold text-foreground">
                                                                {report.workYear} 年第 {report.workWeek} 週
                                                            </span>
                                                            <span className="text-xs text-muted-foreground">
                                                                {startDateStr} ~ {endDateStr}
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td className="whitespace-nowrap px-6 py-5">
                                                        <span
                                                            className={`inline-flex items-center gap-1.5 rounded-full border-2 px-3.5 py-1.5 text-xs font-semibold shadow-sm ${statusConfig.className}`}
                                                        >
                                                            <StatusIcon className="size-3.5" />
                                                            {statusConfig.text}
                                                        </span>
                                                    </td>
                                                    <td className="whitespace-nowrap px-6 py-5">
                                                        <span className="font-semibold text-foreground">
                                                            {report.totalHours.toFixed(1)} <span className="text-sm font-normal text-muted-foreground">小時</span>
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-5">
                                                        <p className="max-w-xs truncate text-sm leading-relaxed text-muted-foreground">
                                                            {report.summary ?? (
                                                                <span className="italic text-muted-foreground/60">—</span>
                                                            )}
                                                        </p>
                                                    </td>
                                                    <td className="whitespace-nowrap px-6 py-5 text-sm leading-relaxed text-muted-foreground">
                                                        {report.updatedAt
                                                            ? new Date(report.updatedAt).toLocaleString('zh-TW', {
                                                                  year: 'numeric',
                                                                  month: '2-digit',
                                                                  day: '2-digit',
                                                                  hour: '2-digit',
                                                                  minute: '2-digit',
                                                              })
                                                            : '—'}
                                                    </td>
                                                    <td className="whitespace-nowrap px-6 py-5 text-right last:pr-6">
                                                        <div className="flex items-center justify-end gap-2">
                                                            <Button
                                                                asChild
                                                                variant="ghost"
                                                                size="sm"
                                                                className="gap-1.5 hover:bg-primary/10"
                                                                data-testid={`view-report-${report.id}`}
                                                            >
                                                                <Link
                                                                    href={weeklyRoutes.preview.url({
                                                                        company: companySlug,
                                                                        weeklyReport: report.id,
                                                                    })}
                                                                >
                                                                    <Eye className="size-4" />
                                                                    查看
                                                                </Link>
                                                            </Button>
                                                            <Button
                                                                asChild
                                                                variant="ghost"
                                                                size="sm"
                                                                className="gap-1.5 hover:bg-primary/10"
                                                                data-testid={`edit-report-${report.id}`}
                                                            >
                                                                <Link
                                                                    href={weeklyRoutes.edit.url({
                                                                        company: companySlug,
                                                                        weeklyReport: report.id,
                                                                    })}
                                                                >
                                                                    編輯
                                                                    <ArrowRight className="size-4" />
                                                                </Link>
                                                            </Button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        </Card>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}

