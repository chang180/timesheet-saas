import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { weeklyReports } from '@/routes/tenant';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Calendar, Clock, FileText, User } from 'lucide-react';

type WeeklyReportItem = {
    id?: number;
    title: string;
    content?: string | null;
    hours_spent?: number;
    planned_hours?: number | null;
    issue_reference?: string | null;
    started_at?: string | null;
    ended_at?: string | null;
};

interface WeeklyReportPreviewProps {
    report: {
        id: number;
        workYear: number;
        workWeek: number;
        status: string;
        summary: string | null;
        user: {
            name: string;
            email: string;
        };
        currentWeek: WeeklyReportItem[];
        nextWeek: WeeklyReportItem[];
    };
    company?: {
        id: number;
        slug: string;
        name: string;
    };
    weekDateRange?: {
        startDate: string;
        endDate: string;
    };
    nextWeekDateRange?: {
        startDate: string;
        endDate: string;
    };
}

const STATUS_TEXT: Record<string, string> = {
    draft: '草稿',
    submitted: '已送出',
    locked: '已鎖定',
};

export default function WeeklyReportPreview({
    report,
    company,
    weekDateRange,
    nextWeekDateRange,
}: WeeklyReportPreviewProps) {
    const { tenant } = usePage<SharedData>().props;

    const sharedCompanySlug = (tenant?.company as { slug?: string } | undefined)?.slug;
    const companySlug = company?.slug ?? sharedCompanySlug ?? '';
    const canNavigate = companySlug.length > 0;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: '週報工作簿',
            href: canNavigate ? weeklyReports.url({ company: companySlug }) : '#',
        },
        {
            title: '預覽週報',
            href: '#',
        },
    ];

    const totalCurrentWeekHours = report.currentWeek.reduce(
        (sum, item) => sum + (item.hours_spent || 0),
        0,
    );

    const totalNextWeekHours = report.nextWeek.reduce(
        (sum, item) => sum + (item.planned_hours || 0),
        0,
    );

    const formatDate = (dateStr: string | null | undefined): string => {
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
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="預覽週報" />

            <div className="space-y-8 px-4 sm:px-6 lg:px-8">
                {/* 標題與操作 */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        {canNavigate ? (
                            <Button asChild variant="ghost" size="sm" className="gap-2">
                                <Link href={weeklyReports.url({ company: companySlug })}>
                                    <ArrowLeft className="size-4" />
                                    返回工作簿
                                </Link>
                            </Button>
                        ) : (
                            <Button variant="ghost" size="sm" disabled className="gap-2">
                                <ArrowLeft className="size-4" />
                                返回工作簿
                            </Button>
                        )}
                    </div>
                    {canNavigate && (
                        <Button asChild variant="outline" size="sm" className="gap-2">
                            <Link href={weeklyReports.edit.url({ company: companySlug, weeklyReport: report.id })}>
                                編輯週報
                            </Link>
                        </Button>
                    )}
                </div>

                {/* 週報標題區塊 */}
                <div className="rounded-2xl border-2 border-border/60 bg-gradient-to-br from-primary/5 via-muted/40 to-muted/20 p-8 shadow-lg">
                    <div className="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                        <div className="space-y-3">
                            <h1 className="text-3xl font-bold text-foreground sm:text-4xl lg:text-5xl">
                                {report.workYear} 年第 {report.workWeek} 週工作週報
                            </h1>
                            {weekDateRange && (
                                <p className="text-lg text-muted-foreground sm:text-xl">
                                    <Calendar className="mr-2 inline size-5" />
                                    {weekDateRange.startDate} ~ {weekDateRange.endDate}
                                </p>
                            )}
                        </div>
                        <div className="flex flex-col gap-3 sm:items-end">
                            <div className="flex items-center gap-2 text-base text-muted-foreground sm:text-lg">
                                <User className="size-5" />
                                <span className="font-medium">{report.user.name}</span>
                            </div>
                            <div className="text-sm text-muted-foreground sm:text-base">
                                {report.user.email}
                            </div>
                            <span className="inline-flex items-center rounded-full border-2 border-border/60 bg-muted/80 px-4 py-2 text-sm font-bold uppercase text-foreground shadow-sm">
                                {STATUS_TEXT[report.status] ?? report.status}
                            </span>
                        </div>
                    </div>
                </div>

                {/* 摘要 */}
                {report.summary && (
                    <Card className="border-2 border-border/60 shadow-lg">
                        <CardHeader className="border-b-2 border-border/60 bg-gradient-to-r from-muted/50 to-muted/30 pb-5">
                            <CardTitle className="flex items-center gap-3 text-xl font-bold text-foreground sm:text-2xl">
                                <FileText className="size-6" />
                                週報摘要
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="pt-6">
                            <p className="whitespace-pre-wrap text-lg leading-relaxed text-foreground sm:text-xl">{report.summary}</p>
                        </CardContent>
                    </Card>
                )}

                {/* 本週完成事項 */}
                <Card className="border-2 border-border/60 shadow-lg">
                    <CardHeader className="border-b-2 border-border/60 bg-gradient-to-r from-muted/50 to-muted/30 pb-5">
                        <CardTitle className="flex items-center justify-between text-xl font-bold text-foreground sm:text-2xl">
                            <span className="flex items-center gap-3">
                                <Clock className="size-6" />
                                本週完成事項
                            </span>
                            <span className="text-base font-bold text-foreground sm:text-lg">
                                總工時：<span className="text-blue-600 dark:text-blue-400">{totalCurrentWeekHours.toFixed(1)}</span> 小時
                            </span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="pt-6">
                        {report.currentWeek.length === 0 ? (
                            <p className="py-12 text-center text-base text-muted-foreground sm:text-lg">
                                本週尚無完成事項
                            </p>
                        ) : (
                            <div className="space-y-6">
                                {report.currentWeek.map((item, index) => (
                                    <div
                                        key={item.id ?? index}
                                        className="rounded-xl border-2 border-border/60 bg-card p-6 shadow-md transition-shadow hover:shadow-lg"
                                    >
                                        <div className="flex items-start justify-between gap-4">
                                            <div className="flex-1 space-y-3">
                                                <h3 className="text-lg font-bold text-foreground sm:text-xl">{item.title}</h3>
                                                {item.content && (
                                                    <p className="whitespace-pre-wrap text-base leading-relaxed text-muted-foreground">
                                                        {item.content}
                                                    </p>
                                                )}
                                                <div className="mt-4 flex flex-wrap items-center gap-4 text-sm text-muted-foreground">
                                                    {item.started_at && item.ended_at && (
                                                        <span className="flex items-center gap-2 rounded-lg border border-border/60 bg-muted/30 px-3 py-1.5">
                                                            <Calendar className="size-4" />
                                                            <span className="font-medium">{formatDate(item.started_at)} ~ {formatDate(item.ended_at)}</span>
                                                        </span>
                                                    )}
                                                    {item.planned_hours !== null && item.planned_hours !== undefined && (
                                                        <span className="rounded-lg border border-border/60 bg-muted/30 px-3 py-1.5">
                                                            預計工時：<span className="font-semibold">{item.planned_hours}</span> 小時
                                                        </span>
                                                    )}
                                                    {item.hours_spent !== null && item.hours_spent !== undefined && (
                                                        <span className="rounded-lg border-2 border-blue-300 bg-blue-50 px-3 py-1.5 font-bold text-blue-700 dark:border-blue-600 dark:bg-blue-950/30 dark:text-blue-400">
                                                            實際工時：{item.hours_spent} 小時
                                                        </span>
                                                    )}
                                                    {item.issue_reference && (
                                                        <span className="rounded-lg border border-border/60 bg-primary/10 px-3 py-1.5 font-semibold text-primary">
                                                            {item.issue_reference}
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* 下週預計事項 */}
                <Card className="border-2 border-border/60 shadow-lg">
                    <CardHeader className="border-b-2 border-border/60 bg-gradient-to-r from-muted/50 to-muted/30 pb-5">
                        <CardTitle className="flex items-center justify-between text-xl font-bold text-foreground sm:text-2xl">
                            <span className="flex items-center gap-3">
                                <Calendar className="size-6" />
                                下週預計事項
                                {nextWeekDateRange && (
                                    <span className="ml-2 text-sm font-normal text-muted-foreground sm:text-base">
                                        ({nextWeekDateRange.startDate} ~ {nextWeekDateRange.endDate})
                                    </span>
                                )}
                            </span>
                            <span className="text-base font-bold text-foreground sm:text-lg">
                                總工時：<span className="text-emerald-600 dark:text-emerald-400">{totalNextWeekHours.toFixed(1)}</span> 小時
                            </span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="pt-6">
                        {report.nextWeek.length === 0 ? (
                            <p className="py-12 text-center text-base text-muted-foreground sm:text-lg">
                                下週尚無預計事項
                            </p>
                        ) : (
                            <div className="space-y-6">
                                {report.nextWeek.map((item, index) => (
                                    <div
                                        key={item.id ?? index}
                                        className="rounded-xl border-2 border-border/60 bg-card p-6 shadow-md transition-shadow hover:shadow-lg"
                                    >
                                        <div className="flex items-start justify-between gap-4">
                                            <div className="flex-1 space-y-3">
                                                <h3 className="text-lg font-bold text-foreground sm:text-xl">{item.title}</h3>
                                                {item.content && (
                                                    <p className="whitespace-pre-wrap text-base leading-relaxed text-muted-foreground">
                                                        {item.content}
                                                    </p>
                                                )}
                                                <div className="mt-4 flex flex-wrap items-center gap-4 text-sm text-muted-foreground">
                                                    {item.started_at && item.ended_at && (
                                                        <span className="flex items-center gap-2 rounded-lg border border-border/60 bg-muted/30 px-3 py-1.5">
                                                            <Calendar className="size-4" />
                                                            <span className="font-medium">{formatDate(item.started_at)} ~ {formatDate(item.ended_at)}</span>
                                                        </span>
                                                    )}
                                                    {item.planned_hours !== null && item.planned_hours !== undefined && (
                                                        <span className="rounded-lg border-2 border-emerald-300 bg-emerald-50 px-3 py-1.5 font-bold text-emerald-700 dark:border-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-400">
                                                            預計工時：{item.planned_hours} 小時
                                                        </span>
                                                    )}
                                                    {item.issue_reference && (
                                                        <span className="rounded-lg border border-border/60 bg-primary/10 px-3 py-1.5 font-semibold text-primary">
                                                            {item.issue_reference}
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* 工時統計 */}
                <Card className="border-2 border-border/60 shadow-xl">
                    <CardHeader className="border-b-2 border-border/60 bg-gradient-to-r from-muted/50 to-muted/30 pb-5">
                        <CardTitle className="text-xl font-bold text-foreground sm:text-2xl">工時統計</CardTitle>
                    </CardHeader>
                    <CardContent className="pt-8">
                        <div className="grid gap-6 sm:grid-cols-2">
                            <div className="relative rounded-xl border-2 border-blue-300 bg-gradient-to-br from-blue-50 via-blue-100/50 to-blue-50 p-8 shadow-lg dark:border-blue-700 dark:from-blue-950/30 dark:via-blue-900/20 dark:to-blue-950/30">
                                <div className="absolute right-6 top-6">
                                    <Clock className="size-8 text-blue-400/40 dark:text-blue-500/30" />
                                </div>
                                <div className="text-base font-semibold text-muted-foreground sm:text-lg">本週完成總工時</div>
                                <div className="mt-4 text-5xl font-bold text-blue-600 dark:text-blue-400 sm:text-6xl">
                                    {totalCurrentWeekHours.toFixed(1)}
                                </div>
                                <div className="mt-2 text-base font-medium text-muted-foreground">小時</div>
                                <div className="mt-4 text-sm font-medium text-muted-foreground sm:text-base">
                                    {report.currentWeek.length} 個項目
                                </div>
                            </div>
                            <div className="relative rounded-xl border-2 border-emerald-300 bg-gradient-to-br from-emerald-50 via-emerald-100/50 to-emerald-50 p-8 shadow-lg dark:border-emerald-700 dark:from-emerald-950/30 dark:via-emerald-900/20 dark:to-emerald-950/30">
                                <div className="absolute right-6 top-6">
                                    <Clock className="size-8 text-emerald-400/40 dark:text-emerald-500/30" />
                                </div>
                                <div className="text-base font-semibold text-muted-foreground sm:text-lg">下週預計總工時</div>
                                <div className="mt-4 text-5xl font-bold text-emerald-600 dark:text-emerald-400 sm:text-6xl">
                                    {totalNextWeekHours.toFixed(1)}
                                </div>
                                <div className="mt-2 text-base font-medium text-muted-foreground">小時</div>
                                <div className="mt-4 text-sm font-medium text-muted-foreground sm:text-base">
                                    {report.nextWeek.length} 個項目
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
