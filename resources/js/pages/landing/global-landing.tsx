import { WelcomeShowcase } from '@/components/landing/welcome-showcase';
import AppearanceToggleDropdown from '@/components/appearance-dropdown';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Calendar,
    Clock,
    Shield,
    Sparkles,
    TrendingUp,
    Users,
} from 'lucide-react';
import { login, register } from '@/routes';

interface PageProps {
    demoTenant: {
        enabled: boolean;
        name?: string | null;
        url?: string | null;
        description?: string | null;
    };
}

export default function GlobalLandingPage() {
    const { demoTenant } = usePage<PageProps>().props;
    const showDemoTenant = Boolean(demoTenant?.enabled && demoTenant?.url);

    return (
        <div className="min-h-screen bg-gradient-to-b from-gray-50 via-white to-gray-100 dark:from-gray-950 dark:via-gray-900 dark:to-gray-800">
            <Head title="週報通 Timesheet SaaS" />
            {/* Hero Section */}
            <section className="relative overflow-hidden pb-20 pt-24">
                <div className="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top,_#c7d2fe_0%,_transparent_55%),radial-gradient(circle_at_bottom,_#fce7f3_0%,_transparent_60%)] opacity-80 dark:opacity-20" />
                <div className="absolute right-4 top-4 flex items-center gap-3 lg:right-8 lg:top-6">
                    <AppearanceToggleDropdown />
                </div>
                <div className="relative mx-auto flex max-w-6xl flex-col gap-16 px-6 lg:flex-row lg:items-center lg:px-8">
                    <div className="relative max-w-xl space-y-8 text-center lg:max-w-lg lg:text-left">
                        <span className="inline-flex items-center justify-center gap-2 rounded-full border border-indigo-200/70 bg-indigo-50/70 px-4 py-1 text-sm font-medium text-indigo-600 shadow-sm dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-200">
                            <Sparkles className="size-4" />
                            多用戶週報最佳實務
                        </span>
                        <h1 className="text-4xl font-semibold leading-tight tracking-tight text-gray-900 sm:text-5xl dark:text-white">
                            <span className="block text-balance">
                                週報通 Timesheet SaaS
                            </span>
                            <span className="mt-2 block text-3xl font-light text-gray-600 sm:text-4xl dark:text-gray-300">
                                一個平台，統一掌握工時、提醒與審核流程
                            </span>
                        </h1>
                        <p className="text-lg leading-8 text-gray-600 dark:text-gray-300">
                            針對多用戶與跨層級團隊設計。整合 Redmine / Jira 任務、假日行事曆與自動提醒，
                            幫助主管即時掌握團隊狀態，成員也能 5 分鐘完成週報。
                        </p>
                        <div className="flex flex-wrap items-center justify-center gap-4 lg:justify-start">
                            <Button asChild size="lg" className="px-6">
                                <Link href={register.url()}>
                                    立即建立週報帳號
                                    <ArrowRight className="ml-2 size-4" />
                                </Link>
                            </Button>
                            <Button
                                asChild
                                size="lg"
                                variant="outline"
                                className="border-indigo-200 bg-white/80 text-indigo-600 backdrop-blur hover:bg-indigo-50 dark:border-indigo-500/40 dark:bg-indigo-500/10 dark:text-indigo-200 dark:hover:bg-indigo-500/20"
                            >
                                <Link href={login.url()}>已有帳號？直接登入</Link>
                            </Button>
                        </div>
                        <dl className="grid gap-6 pt-4 text-left sm:grid-cols-3">
                            <div className="rounded-2xl border border-indigo-100/70 bg-white/80 p-4 shadow-sm dark:border-indigo-500/30 dark:bg-indigo-500/10">
                                <dt className="text-xs uppercase tracking-wide text-indigo-500 dark:text-indigo-200">
                                    週報提交成功率
                                </dt>
                                <dd className="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">
                                    98%
                                </dd>
                            </div>
                            <div className="rounded-2xl border border-emerald-100/70 bg-white/80 p-4 shadow-sm dark:border-emerald-500/30 dark:bg-emerald-500/10">
                                <dt className="text-xs uppercase tracking-wide text-emerald-500 dark:text-emerald-200">
                                    工時校對時間
                                </dt>
                                <dd className="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">
                                    -65%
                                </dd>
                            </div>
                            <div className="rounded-2xl border border-sky-100/70 bg-white/80 p-4 shadow-sm dark:border-sky-500/30 dark:bg-sky-500/10">
                                <dt className="text-xs uppercase tracking-wide text-sky-500 dark:text-sky-200">
                                    主管審核效率
                                </dt>
                                <dd className="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">
                                    3× faster
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div className="relative flex-1">
                        <div className="absolute -inset-6 -z-10 rounded-3xl bg-white/60 blur-3xl dark:bg-indigo-500/10" />
                        <WelcomeShowcase />
                    </div>
                </div>
            </section>

            {/* Features Section */}
            <section className="px-6 py-24 lg:px-8">
                <div className="mx-auto max-w-7xl">
                    <div className="mx-auto max-w-2xl text-center">
                        <h2 className="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl dark:text-white">
                            核心功能
                        </h2>
                        <p className="mt-4 text-lg text-gray-600 dark:text-gray-300">
                            完整的週報管理解決方案，滿足各種規模團隊的需求
                        </p>
                    </div>

                    <div className="mx-auto mt-16 grid max-w-7xl grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <Card>
                            <CardHeader>
                                <div className="mb-4 flex size-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                                    <Calendar className="size-6 text-blue-600 dark:text-blue-400" />
                                </div>
                                <CardTitle>週報管理</CardTitle>
                                <CardDescription>
                                    輕鬆記錄本週完成任務與下週計畫，支援拖曳排序與工時統計
                                </CardDescription>
                            </CardHeader>
                        </Card>

                        <Card>
                            <CardHeader>
                                <div className="mb-4 flex size-12 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900">
                                    <Clock className="size-6 text-green-600 dark:text-green-400" />
                                </div>
                                <CardTitle>Redmine/Jira 整合</CardTitle>
                                <CardDescription>
                                    自動帶入 Issue 資訊與預估工時，減少重複輸入
                                </CardDescription>
                            </CardHeader>
                        </Card>

                        <Card>
                            <CardHeader>
                                <div className="mb-4 flex size-12 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900">
                                    <Users className="size-6 text-purple-600 dark:text-purple-400" />
                                </div>
                                <CardTitle>層級管理</CardTitle>
                                <CardDescription>
                                    支援公司、部門、小組多層級結構，靈活的權限控制
                                </CardDescription>
                            </CardHeader>
                        </Card>

                        <Card>
                            <CardHeader>
                                <div className="mb-4 flex size-12 items-center justify-center rounded-lg bg-orange-100 dark:bg-orange-900">
                                    <Shield className="size-6 text-orange-600 dark:text-orange-400" />
                                </div>
                                <CardTitle>IP 白名單</CardTitle>
                                <CardDescription>
                                    企業級安全控制，限制特定 IP 位址訪問
                                </CardDescription>
                            </CardHeader>
                        </Card>

                        <Card>
                            <CardHeader>
                                <div className="mb-4 flex size-12 items-center justify-center rounded-lg bg-red-100 dark:bg-red-900">
                                    <TrendingUp className="size-6 text-red-600 dark:text-red-400" />
                                </div>
                                <CardTitle>統計報表</CardTitle>
                                <CardDescription>
                                    多維度報表匯出，支援 CSV/XLSX 格式
                                </CardDescription>
                            </CardHeader>
                        </Card>

                        <Card>
                            <CardHeader>
                                <div className="mb-4 flex size-12 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900">
                                    <Calendar className="size-6 text-indigo-600 dark:text-indigo-400" />
                                </div>
                                <CardTitle>假日提醒</CardTitle>
                                <CardDescription>
                                    自動警示國定假日工時，避免異常填報
                                </CardDescription>
                            </CardHeader>
                        </Card>
                    </div>
                </div>
            </section>

            {/* Quick Start Steps */}
            <section className="bg-white px-6 py-24 lg:px-8 dark:bg-gray-800">
                <div className="mx-auto max-w-7xl">
                    <div className="mx-auto max-w-2xl text-center">
                        <h2 className="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl dark:text-white">
                            快速上手
                        </h2>
                        <p className="mt-4 text-lg text-gray-600 dark:text-gray-300">
                            只需三個步驟，即可開始使用週報通
                        </p>
                    </div>

                    <div className="mx-auto mt-16 max-w-4xl">
                        <ol className="space-y-8">
                            <li className="flex gap-6">
                                <div className="flex size-12 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xl font-bold text-blue-600 dark:bg-blue-900 dark:text-blue-400">
                                    1
                                </div>
                                <div>
                                    <h3 className="text-xl font-semibold text-gray-900 dark:text-white">
                                        建立用戶帳號
                                    </h3>
                                    <p className="mt-2 text-gray-600 dark:text-gray-300">
                                        註冊公司帳號，設定品牌色彩與
                                        Logo，打造專屬的週報系統
                                    </p>
                                </div>
                            </li>
                            <li className="flex gap-6">
                                <div className="flex size-12 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xl font-bold text-blue-600 dark:bg-blue-900 dark:text-blue-400">
                                    2
                                </div>
                                <div>
                                    <h3 className="text-xl font-semibold text-gray-900 dark:text-white">
                                        設定組織層級
                                    </h3>
                                    <p className="mt-2 text-gray-600 dark:text-gray-300">
                                        建立部門與小組結構，設定成員權限與主管階層
                                    </p>
                                </div>
                            </li>
                            <li className="flex gap-6">
                                <div className="flex size-12 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xl font-bold text-blue-600 dark:bg-blue-900 dark:text-blue-400">
                                    3
                                </div>
                                <div>
                                    <h3 className="text-xl font-semibold text-gray-900 dark:text-white">
                                        開始填寫週報
                                    </h3>
                                    <p className="mt-2 text-gray-600 dark:text-gray-300">
                                        邀請成員加入，開始記錄每週工作成果與計畫
                                    </p>
                                </div>
                            </li>
                        </ol>
                    </div>
                </div>
            </section>

            {showDemoTenant && (
                <section className="px-6 py-16 lg:px-8">
                    <div className="mx-auto max-w-4xl rounded-3xl border border-emerald-200/70 bg-white/90 p-10 text-center shadow-lg dark:border-emerald-500/40 dark:bg-gray-900/70">
                        <h2 className="text-2xl font-semibold text-gray-900 dark:text-white">
                            體驗 {demoTenant?.name ?? 'Demo 用戶'}
                        </h2>
                        <p className="mt-4 text-base text-gray-600 dark:text-gray-300">
                            {demoTenant?.description ??
                                '即刻進入示範用戶，了解週報通的完整體驗與設定流程。'}
                        </p>
                        <div className="mt-8 flex justify-center">
                            <Button asChild size="lg" className="shadow-md">
                                <a
                                    href={demoTenant?.url ?? '#'}
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    進入 Demo 用戶
                                    <ArrowRight className="ml-2 size-4" />
                                </a>
                            </Button>
                        </div>
                    </div>
                </section>
            )}

            {/* CTA Section */}
            <section className="px-6 py-24 lg:px-8">
                <div className="mx-auto max-w-4xl text-center">
                    <h2 className="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl dark:text-white">
                        準備好用週報掌握本週重點了嗎？
                    </h2>
                    <p className="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-300">
                        立即註冊，一本工作簿收好本週所有任務、支援與會議重點。
                    </p>
                    <div className="mt-10 flex items-center justify-center gap-x-6">
                        <Button asChild size="lg">
                            <Link href={register.url()}>
                                建立週報帳號
                                <ArrowRight className="ml-2 size-4" />
                            </Link>
                        </Button>
                    </div>
                </div>
            </section>
        </div>
    );
}
