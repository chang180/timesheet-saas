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
            <div className="flex flex-col gap-8">
                <header className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div className="space-y-2">
                        <h1 className="text-3xl font-bold text-foreground sm:text-4xl">
                            {tenant ? `${tenant.name} 的週報工作簿` : '週報工作簿'}
                        </h1>
                        <p className="text-sm text-muted-foreground sm:text-base">
                            記錄本週每一項任務、支援與會議，週會或主管提報時可快速整理重點。
                        </p>
                    </div>
                </header>

                {needsOnboarding && (
                    <div className="rounded-2xl border-2 border-amber-300 bg-gradient-to-r from-amber-50/90 to-amber-100/60 p-6 shadow-md dark:border-amber-600 dark:from-amber-950/30 dark:to-amber-900/20">
                        <p className="text-base font-bold text-amber-900 dark:text-amber-200 sm:text-lg">
                            第一次使用週報通？
                        </p>
                        <p className="mt-2 text-sm text-amber-800 dark:text-amber-300 sm:text-base">
                            請先完成用戶設定（品牌、週報欄位與通知），再邀請團隊成員開始填寫。
                        </p>
                    </div>
                )}

                <section className="grid gap-6 sm:grid-cols-2">
                    <div className="group flex flex-col rounded-2xl border-2 border-border/60 bg-card p-6 shadow-sm transition-all duration-300 hover:shadow-lg hover:border-primary/40 hover:-translate-y-1">
                        <div className="flex size-16 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-100 via-indigo-50 to-indigo-100/50 text-indigo-600 shadow-md transition-all duration-300 group-hover:scale-110 group-hover:shadow-lg dark:from-indigo-500/20 dark:via-indigo-500/10 dark:to-indigo-500/5 dark:text-indigo-400">
                            <PenSquare className="size-8" />
                        </div>
                        <h2 className="mt-5 text-xl font-bold text-foreground sm:text-2xl">填寫本週週報</h2>
                        <p className="mt-3 text-sm leading-relaxed text-muted-foreground sm:text-base">
                            快速記錄本週要提報的任務、臨時支援與會議重點。週會前回顧即可。
                        </p>
                        <Link
                            href="#"
                            className="mt-auto inline-flex items-center gap-2 self-start rounded-full bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition-all hover:bg-indigo-500 hover:shadow-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                        >
                            前往填寫
                            <ArrowRight className="size-4" />
                        </Link>
                    </div>

                    <div className="group flex flex-col rounded-2xl border-2 border-border/60 bg-card p-6 shadow-sm transition-all duration-300 hover:shadow-lg hover:border-primary/40 hover:-translate-y-1">
                        <div className="flex size-16 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-100 via-emerald-50 to-emerald-100/50 text-emerald-600 shadow-md transition-all duration-300 group-hover:scale-110 group-hover:shadow-lg dark:from-emerald-500/20 dark:via-emerald-500/10 dark:to-emerald-500/5 dark:text-emerald-400">
                            <ClipboardList className="size-8" />
                        </div>
                        <h2 className="mt-5 text-xl font-bold text-foreground sm:text-2xl">查看週報歷史</h2>
                        <p className="mt-3 text-sm leading-relaxed text-muted-foreground sm:text-base">
                            蒐集過往週報、下載摘要或準備主管提報，快速定位需要的週數。
                        </p>
                        <Link
                            href="#"
                            className="mt-auto inline-flex items-center gap-2 self-start rounded-full border-2 border-border/60 bg-background px-5 py-2.5 text-sm font-semibold text-foreground shadow-sm transition-all hover:bg-muted hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-muted-foreground/40"
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
