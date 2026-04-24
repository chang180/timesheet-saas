import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import personal from '@/routes/personal';
import * as weeklyReportRoutes from '@/routes/personal/weekly-reports';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    CheckCircle2,
    ClipboardList,
    Clock,
    Eye,
    Lock,
    PenSquare,
    SquarePen,
    type LucideIcon,
} from 'lucide-react';

type WeeklyReportSummary = {
    id: number;
    workYear: number;
    workWeek: number;
    status: string;
    summary: string | null;
    totalHours: number;
    updatedAt?: string | null;
};

interface PersonalWeeklyReportListProps {
    reports: WeeklyReportSummary[];
    defaults: {
        year: number;
        week: number;
    };
    weekDateRange?: {
        startDate: string;
        endDate: string;
    };
    filters?: {
        year: string;
        status: string;
    };
    availableYears?: number[];
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

export default function PersonalWeeklyReportList({
    reports,
    defaults,
}: PersonalWeeklyReportListProps) {
    const { flash } = usePage<{
        flash: { success?: string; info?: string; warning?: string };
    }>().props;

    const currentWeekReport = reports.find(
        (report) =>
            report.workYear === defaults.year &&
            report.workWeek === defaults.week,
    );

    const createHref = personal.weeklyReports.create.url();
    const editCurrentWeekHref = currentWeekReport
        ? personal.weeklyReports.edit.url({
              weeklyReport: currentWeekReport.id,
          })
        : createHref;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: '我的週報',
            href: personal.weeklyReports.url(),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="我的週報" />

            <div className="flex flex-col gap-8 px-4 sm:px-6 lg:px-8">
                <header className="space-y-3">
                    <h1 className="text-3xl font-bold tracking-tight text-foreground sm:text-4xl">
                        我的週報
                    </h1>
                    <p className="text-sm leading-relaxed text-muted-foreground sm:text-base">
                        記錄每週的工作重點，建立你個人的工作履歷。
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

                <Card className="border-2 border-border/60 bg-card shadow-sm">
                    <CardContent className="flex flex-col gap-5 p-6 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-start gap-4">
                            <div className="flex size-12 items-center justify-center rounded-xl bg-linear-to-br from-indigo-100 via-indigo-50 to-indigo-100/50 text-indigo-600 dark:from-indigo-500/20 dark:via-indigo-500/10 dark:to-indigo-500/5 dark:text-indigo-400">
                                <PenSquare className="size-6" />
                            </div>
                            <div className="space-y-1">
                                <h2 className="text-xl font-semibold text-foreground">
                                    本週週報（{defaults.year} 年第{' '}
                                    {defaults.week} 週）
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    {currentWeekReport
                                        ? '你已開始本週週報，可繼續編輯並提交。'
                                        : '尚未建立本週週報，開始記錄這週的工作。'}
                                </p>
                            </div>
                        </div>
                        <Button asChild className="gap-2">
                            <Link
                                href={
                                    currentWeekReport
                                        ? editCurrentWeekHref
                                        : createHref
                                }
                                data-testid={
                                    currentWeekReport
                                        ? 'goto-personal-report-edit'
                                        : 'goto-personal-report-create'
                                }
                            >
                                {currentWeekReport ? '繼續編輯' : '立即填寫'}
                                <ArrowRight className="size-4" />
                            </Link>
                        </Button>
                    </CardContent>
                </Card>

                <section className="space-y-3">
                    <h2 className="text-lg font-semibold text-foreground">
                        歷史週報
                    </h2>

                    {reports.length === 0 ? (
                        <Card className="border-2 border-dashed border-border/60">
                            <CardContent className="flex flex-col items-center gap-3 p-10 text-center">
                                <ClipboardList className="size-10 text-muted-foreground" />
                                <p className="text-sm text-muted-foreground">
                                    你還沒有任何週報紀錄，從上面的「本週週報」開始吧。
                                </p>
                            </CardContent>
                        </Card>
                    ) : (
                        <div className="grid gap-3">
                            {reports.map((report) => {
                                const statusConfig =
                                    STATUS_CONFIG[report.status] ??
                                    STATUS_CONFIG.draft;
                                const StatusIcon = statusConfig.icon;

                                return (
                                    <Card
                                        key={report.id}
                                        className="border-border/60 transition-colors hover:border-primary/40"
                                    >
                                        <CardContent className="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                                            <div className="space-y-1">
                                                <div className="flex items-center gap-2">
                                                    <span className="text-base font-semibold text-foreground">
                                                        {report.workYear} 年 第{' '}
                                                        {report.workWeek} 週
                                                    </span>
                                                    <span
                                                        className={`inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs font-medium ${statusConfig.className}`}
                                                    >
                                                        <StatusIcon className="size-3" />
                                                        {statusConfig.text}
                                                    </span>
                                                </div>
                                                {report.summary && (
                                                    <p className="line-clamp-2 text-sm text-muted-foreground">
                                                        {report.summary}
                                                    </p>
                                                )}
                                                <p className="text-xs text-muted-foreground">
                                                    本週工時：
                                                    {report.totalHours.toFixed(
                                                        1,
                                                    )}{' '}
                                                    小時
                                                </p>
                                            </div>
                                            <div className="flex gap-2">
                                                <Button
                                                    asChild
                                                    variant="ghost"
                                                    size="sm"
                                                    className="gap-2"
                                                >
                                                    <Link
                                                        href={weeklyReportRoutes.show.url(
                                                            {
                                                                weeklyReport:
                                                                    report.id,
                                                            },
                                                        )}
                                                    >
                                                        <Eye className="size-4" />
                                                        檢視
                                                    </Link>
                                                </Button>
                                                <Button
                                                    asChild
                                                    variant="outline"
                                                    className="gap-2"
                                                >
                                                    <Link
                                                        href={personal.weeklyReports.edit.url(
                                                            {
                                                                weeklyReport:
                                                                    report.id,
                                                            },
                                                        )}
                                                    >
                                                        <SquarePen className="size-4" />
                                                        編輯
                                                    </Link>
                                                </Button>
                                            </div>
                                        </CardContent>
                                    </Card>
                                );
                            })}
                        </div>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
