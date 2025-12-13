import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import tenantRoutes from '@/routes/tenant';
import * as weeklyRoutes from '@/routes/tenant/weekly-reports';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, ClipboardList, PenSquare, SquarePen } from 'lucide-react';

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

const STATUS_TEXT: Record<string, string> = {
    draft: '草稿',
    submitted: '已送出',
    locked: '已鎖定',
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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="週報工作簿" />

            <div className="flex flex-col gap-6">
                <header className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold text-foreground">
                            {props.company?.name
                                ? `${props.company.name} 的週報工作簿`
                                : tenant?.company?.name
                                  ? `${tenant.company.name} 的週報工作簿`
                                : '週報工作簿'}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            記錄本週每一項任務、支援與會議重點，週會或主管提報時可以立即找到摘要。
                        </p>
                    </div>
                    {canCreate ? (
                        <Button asChild>
                            <Link href={createHref} data-testid="create-weekly-report">
                                <SquarePen className="mr-2 size-4" />
                                建立本週週報
                            </Link>
                        </Button>
                    ) : (
                        <Button disabled>
                            <SquarePen className="mr-2 size-4" />
                            建立本週週報
                        </Button>
                    )}
                </header>

                {(flash?.success || flash?.info || flash?.warning) && (
                    <div className="rounded-md border border-border/60 bg-muted/40 p-4 text-sm text-muted-foreground">
                        {flash.success && <p className="text-emerald-600">{flash.success}</p>}
                        {flash.info && <p className="text-sky-600">{flash.info}</p>}
                        {flash.warning && <p className="text-amber-600">{flash.warning}</p>}
                    </div>
                )}

                <section className="grid gap-4 sm:grid-cols-2">
                    <Card className="flex flex-col border-border/60 bg-card shadow-sm">
                        <CardContent className="flex flex-1 flex-col gap-4 p-6">
                            <div className="flex size-12 items-center justify-center rounded-full bg-indigo-100 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-200">
                                <PenSquare className="size-6" />
                            </div>
                            <div>
                                <h2 className="text-lg font-semibold text-foreground">填寫本週週報</h2>
                                <p className="mt-2 text-sm text-muted-foreground">
                                    快速記錄本週任務、臨時支援與會議重點。若已建立草稿，系統會自動帶你到該筆週報。
                                </p>
                            </div>
                            {canCreate ? (
                                <Button
                                    asChild
                                    className="mt-auto self-start"
                                >
                                    <Link href={createHref} data-testid="goto-weekly-report-create">
                                        前往填寫
                                        <ArrowRight className="ml-2 size-4" />
                                    </Link>
                                </Button>
                            ) : (
                                <Button disabled className="mt-auto self-start">
                                    前往填寫
                                </Button>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="flex flex-col border-border/60 bg-card shadow-sm">
                        <CardContent className="flex flex-1 flex-col gap-4 p-6">
                            <div className="flex size-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-200">
                                <ClipboardList className="size-6" />
                            </div>
                            <div>
                                <h2 className="text-lg font-semibold text-foreground">查看週報歷史</h2>
                                <p className="mt-2 text-sm text-muted-foreground">
                                    蒐集過往週報、下載摘要或準備主管提報，可依週數快速定位並進入編輯。
                                </p>
                            </div>
                            {latestReport ? (
                                <Button
                                    variant="outline"
                                    asChild
                                    className="mt-auto self-start"
                                >
                                    <Link
                                        href={weeklyRoutes.edit.url({
                                            company: companySlug,
                                            weeklyReport: latestReport.id,
                                        })}
                                        data-testid="view-latest-weekly-report"
                                    >
                                        檢視週報
                                        <ArrowRight className="ml-2 size-4" />
                                    </Link>
                                </Button>
                            ) : (
                                <Button
                                    variant="outline"
                                    disabled
                                    className="mt-auto self-start"
                                >
                                    尚無週報
                                </Button>
                            )}
                        </CardContent>
                    </Card>
                </section>

                <section id="report-list" className="space-y-4">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold text-foreground">
                            最近週報 · 第 {defaults.year} 年第 {defaults.week} 週
                            {props.weekDateRange && (
                                <span className="ml-2 text-sm font-normal text-muted-foreground">
                                    ({props.weekDateRange.startDate} ~ {props.weekDateRange.endDate})
                                </span>
                            )}
                        </h2>
                        <span className="text-xs uppercase text-muted-foreground">
                            共 {reports.length} 筆
                        </span>
                    </div>

                    {reports.length === 0 ? (
                        <div className="rounded-md border border-dashed border-border/60 bg-muted/40 p-6 text-sm text-muted-foreground">
                            尚未建立任何週報，點選「填寫本週週報」開始紀錄你的第一份週報。
                        </div>
                    ) : (
                        <div className="overflow-hidden rounded-xl border border-border/60 shadow-sm">
                            <table className="min-w-full divide-y divide-border/60 text-sm">
                                <thead className="bg-muted/40 text-muted-foreground">
                                    <tr>
                                        <th className="px-4 py-3 text-left font-medium">週次</th>
                                        <th className="px-4 py-3 text-left font-medium">狀態</th>
                                        <th className="px-4 py-3 text-left font-medium">總工時</th>
                                        <th className="px-4 py-3 text-left font-medium">摘要</th>
                                        <th className="px-4 py-3 text-left font-medium">更新時間</th>
                                        <th className="px-4 py-3 text-right font-medium">操作</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border/40 bg-card">
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

                                        return (
                                            <tr key={report.id}>
                                                <td className="px-4 py-3 text-foreground">
                                                    {report.workYear} 年第 {report.workWeek} 週{' '}
                                                    <span className="text-xs text-muted-foreground">
                                                        ({startDateStr} ~ {endDateStr})
                                                    </span>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <span className="inline-flex items-center rounded-full bg-muted px-2 py-1 text-xs font-medium uppercase text-muted-foreground">
                                                        {STATUS_TEXT[report.status] ?? report.status}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-3 text-foreground">
                                                    {report.totalHours.toFixed(1)} 小時
                                                </td>
                                                <td className="px-4 py-3 text-muted-foreground">
                                                    {report.summary ?? '—'}
                                                </td>
                                                <td className="px-4 py-3 text-muted-foreground">
                                                    {report.updatedAt
                                                        ? new Date(report.updatedAt).toLocaleString()
                                                        : '—'}
                                                </td>
                                                <td className="px-4 py-3 text-right">
                                                    <Button
                                                        asChild
                                                        variant="ghost"
                                                        size="sm"
                                                        data-testid={`edit-report-${report.id}`}
                                                    >
                                                        <Link
                                                            href={weeklyRoutes.edit.url({
                                                                company: companySlug,
                                                                weeklyReport: report.id,
                                                            })}
                                                        >
                                                            編輯
                                                            <ArrowRight className="ml-1 size-4" />
                                                        </Link>
                                                    </Button>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}

