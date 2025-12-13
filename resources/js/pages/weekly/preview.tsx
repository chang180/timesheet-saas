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

            <div className="space-y-6">
                {/* 標題與操作 */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        {canNavigate ? (
                            <Button asChild variant="ghost" size="sm">
                                <Link href={weeklyReports.edit.url({ company: companySlug, weeklyReport: report.id })}>
                                    <ArrowLeft className="mr-2 size-4" />
                                    返回編輯
                                </Link>
                            </Button>
                        ) : (
                            <Button variant="ghost" size="sm" disabled>
                                <ArrowLeft className="mr-2 size-4" />
                                返回編輯
                            </Button>
                        )}
                    </div>
                </div>

                {/* 週報標題區塊 */}
                <div className="rounded-lg border border-border/60 bg-gradient-to-br from-muted/40 to-muted/20 p-6 shadow-sm">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 className="text-2xl font-bold text-foreground">
                                {report.workYear} 年第 {report.workWeek} 週工作週報
                            </h1>
                            {weekDateRange && (
                                <p className="mt-2 text-sm text-muted-foreground">
                                    <Calendar className="mr-1 inline size-4" />
                                    {weekDateRange.startDate} ~ {weekDateRange.endDate}
                                </p>
                            )}
                        </div>
                        <div className="flex flex-col gap-2 sm:items-end">
                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                <User className="size-4" />
                                <span>{report.user.name}</span>
                            </div>
                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                <span>{report.user.email}</span>
                            </div>
                            <span className="inline-flex items-center rounded-full bg-muted px-3 py-1 text-xs font-medium uppercase text-muted-foreground">
                                {STATUS_TEXT[report.status] ?? report.status}
                            </span>
                        </div>
                    </div>
                </div>

                {/* 摘要 */}
                {report.summary && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-lg">
                                <FileText className="size-5" />
                                週報摘要
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="whitespace-pre-wrap text-foreground">{report.summary}</p>
                        </CardContent>
                    </Card>
                )}

                {/* 本週完成事項 */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center justify-between text-lg">
                            <span className="flex items-center gap-2">
                                <Clock className="size-5" />
                                本週完成事項
                            </span>
                            <span className="text-sm font-normal text-muted-foreground">
                                總工時：{totalCurrentWeekHours.toFixed(1)} 小時
                            </span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {report.currentWeek.length === 0 ? (
                            <p className="py-8 text-center text-sm text-muted-foreground">
                                本週尚無完成事項
                            </p>
                        ) : (
                            <div className="space-y-4">
                                {report.currentWeek.map((item, index) => (
                                    <div
                                        key={item.id ?? index}
                                        className="rounded-lg border border-border/60 bg-card p-4 shadow-sm"
                                    >
                                        <div className="flex items-start justify-between gap-4">
                                            <div className="flex-1">
                                                <h3 className="font-semibold text-foreground">{item.title}</h3>
                                                {item.content && (
                                                    <p className="mt-2 whitespace-pre-wrap text-sm text-muted-foreground">
                                                        {item.content}
                                                    </p>
                                                )}
                                                <div className="mt-3 flex flex-wrap items-center gap-4 text-xs text-muted-foreground">
                                                    {item.started_at && item.ended_at && (
                                                        <span className="flex items-center gap-1">
                                                            <Calendar className="size-3" />
                                                            {formatDate(item.started_at)} ~ {formatDate(item.ended_at)}
                                                        </span>
                                                    )}
                                                    {item.planned_hours !== null && item.planned_hours !== undefined && (
                                                        <span>預計工時：{item.planned_hours} 小時</span>
                                                    )}
                                                    {item.hours_spent !== null && item.hours_spent !== undefined && (
                                                        <span className="font-medium text-foreground">
                                                            實際工時：{item.hours_spent} 小時
                                                        </span>
                                                    )}
                                                    {item.issue_reference && (
                                                        <span className="rounded bg-muted px-2 py-0.5">
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
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center justify-between text-lg">
                            <span className="flex items-center gap-2">
                                <Calendar className="size-5" />
                                下週預計事項
                                {nextWeekDateRange && (
                                    <span className="ml-2 text-sm font-normal text-muted-foreground">
                                        ({nextWeekDateRange.startDate} ~ {nextWeekDateRange.endDate})
                                    </span>
                                )}
                            </span>
                            <span className="text-sm font-normal text-muted-foreground">
                                總工時：{totalNextWeekHours.toFixed(1)} 小時
                            </span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {report.nextWeek.length === 0 ? (
                            <p className="py-8 text-center text-sm text-muted-foreground">
                                下週尚無預計事項
                            </p>
                        ) : (
                            <div className="space-y-4">
                                {report.nextWeek.map((item, index) => (
                                    <div
                                        key={item.id ?? index}
                                        className="rounded-lg border border-border/60 bg-card p-4 shadow-sm"
                                    >
                                        <div className="flex items-start justify-between gap-4">
                                            <div className="flex-1">
                                                <h3 className="font-semibold text-foreground">{item.title}</h3>
                                                {item.content && (
                                                    <p className="mt-2 whitespace-pre-wrap text-sm text-muted-foreground">
                                                        {item.content}
                                                    </p>
                                                )}
                                                <div className="mt-3 flex flex-wrap items-center gap-4 text-xs text-muted-foreground">
                                                    {item.started_at && item.ended_at && (
                                                        <span className="flex items-center gap-1">
                                                            <Calendar className="size-3" />
                                                            {formatDate(item.started_at)} ~ {formatDate(item.ended_at)}
                                                        </span>
                                                    )}
                                                    {item.planned_hours !== null && item.planned_hours !== undefined && (
                                                        <span className="font-medium text-foreground">
                                                            預計工時：{item.planned_hours} 小時
                                                        </span>
                                                    )}
                                                    {item.issue_reference && (
                                                        <span className="rounded bg-muted px-2 py-0.5">
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
                <Card>
                    <CardHeader>
                        <CardTitle className="text-lg">工時統計</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="rounded-lg border border-border/60 bg-gradient-to-br from-blue-50 to-blue-100/50 p-6 dark:from-blue-950/20 dark:to-blue-900/10">
                                <div className="text-sm font-medium text-muted-foreground">本週完成總工時</div>
                                <div className="mt-2 text-3xl font-bold text-blue-600 dark:text-blue-400">
                                    {totalCurrentWeekHours.toFixed(1)}
                                </div>
                                <div className="mt-1 text-xs text-muted-foreground">小時</div>
                                <div className="mt-2 text-xs text-muted-foreground">
                                    {report.currentWeek.length} 個項目
                                </div>
                            </div>
                            <div className="rounded-lg border border-border/60 bg-gradient-to-br from-emerald-50 to-emerald-100/50 p-6 dark:from-emerald-950/20 dark:to-emerald-900/10">
                                <div className="text-sm font-medium text-muted-foreground">下週預計總工時</div>
                                <div className="mt-2 text-3xl font-bold text-emerald-600 dark:text-emerald-400">
                                    {totalNextWeekHours.toFixed(1)}
                                </div>
                                <div className="mt-1 text-xs text-muted-foreground">小時</div>
                                <div className="mt-2 text-xs text-muted-foreground">
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
