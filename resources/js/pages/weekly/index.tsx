import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowRight, ClipboardList, PenSquare } from 'lucide-react';

interface WeeklyProps {
    isManager: boolean;
    tenant: {
        name: string;
        slug: string;
        onboarded: boolean;
    } | null;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: '週報工作簿',
        href: '#',
    },
];

export default function WeeklyReports({ isManager, tenant }: WeeklyProps) {
    const needsOnboarding = Boolean(isManager && tenant && tenant.onboarded === false);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="週報工作簿" />
            <div className="flex flex-col gap-6">
                <header className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold text-foreground">
                            {tenant ? `${tenant.name} 的週報工作簿` : '週報工作簿'}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            記錄本週每一項任務、支援與會議，週會或主管提報時可快速整理重點。
                        </p>
                    </div>
                </header>

                {needsOnboarding && (
                    <div className="rounded-2xl border border-amber-200 bg-amber-50/80 p-4 text-sm text-amber-900 shadow-sm dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-100">
                        <p className="font-medium">
                            第一次使用週報通？
                        </p>
                        <p className="mt-1">
                            請先完成用戶設定（品牌、週報欄位與通知），再邀請團隊成員開始填寫。
                        </p>
                    </div>
                )}

                <section className="grid gap-4 sm:grid-cols-2">
                    <div className="flex flex-col rounded-2xl border border-border/60 bg-card p-6 shadow-sm">
                        <div className="flex size-12 items-center justify-center rounded-full bg-indigo-100 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-200">
                            <PenSquare className="size-6" />
                        </div>
                        <h2 className="mt-4 text-lg font-semibold text-foreground">填寫本週週報</h2>
                        <p className="mt-2 text-sm text-muted-foreground">
                            快速記錄本週要提報的任務、臨時支援與會議重點。週會前回顧即可。
                        </p>
                        <Link
                            href="#"
                            className="mt-auto inline-flex items-center gap-2 self-start rounded-full bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-indigo-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                        >
                            前往填寫
                            <ArrowRight className="size-4" />
                        </Link>
                    </div>

                    <div className="flex flex-col rounded-2xl border border-border/60 bg-card p-6 shadow-sm">
                        <div className="flex size-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-200">
                            <ClipboardList className="size-6" />
                        </div>
                        <h2 className="mt-4 text-lg font-semibold text-foreground">查看週報歷史</h2>
                        <p className="mt-2 text-sm text-muted-foreground">
                            蒐集過往週報、下載摘要或準備主管提報，快速定位需要的週數。
                        </p>
                        <Link
                            href="#"
                            className="mt-auto inline-flex items-center gap-2 self-start rounded-full border border-border px-4 py-2 text-sm font-medium text-foreground shadow-sm hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-muted-foreground/40"
                        >
                            檢視週報
                            <ArrowRight className="size-4" />
                        </Link>
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
