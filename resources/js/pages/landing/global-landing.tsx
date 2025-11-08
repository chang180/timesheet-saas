import { Button } from '@/components/ui/button';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Link } from '@inertiajs/react';
import {
    ArrowRight,
    Calendar,
    Clock,
    Shield,
    TrendingUp,
    Users,
} from 'lucide-react';

export default function GlobalLandingPage() {
    return (
        <div className="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800">
            {/* Hero Section */}
            <section className="relative px-6 py-24 lg:px-8">
                <div className="mx-auto max-w-4xl text-center">
                    <h1 className="text-4xl font-bold tracking-tight text-gray-900 sm:text-6xl dark:text-white">
                        週報通 Timesheet SaaS
                    </h1>
                    <p className="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-300">
                        簡化週報管理，提升團隊效率。支援多租戶、層級管理、Redmine/Jira
                        整合，讓週報填寫不再是負擔。
                    </p>
                    <div className="mt-10 flex items-center justify-center gap-x-6">
                        <Button asChild size="lg">
                            <Link href="/login">
                                立即登入
                                <ArrowRight className="ml-2 size-4" />
                            </Link>
                        </Button>
                        <Button asChild size="lg" variant="outline">
                            <Link href="/register">申請試用</Link>
                        </Button>
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
                                        建立租戶帳號
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

            {/* CTA Section */}
            <section className="px-6 py-24 lg:px-8">
                <div className="mx-auto max-w-4xl text-center">
                    <h2 className="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl dark:text-white">
                        準備好開始了嗎？
                    </h2>
                    <p className="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-300">
                        立即註冊，享受 14 天免費試用，無需信用卡
                    </p>
                    <div className="mt-10 flex items-center justify-center gap-x-6">
                        <Button asChild size="lg">
                            <Link href="/register">
                                開始免費試用
                                <ArrowRight className="ml-2 size-4" />
                            </Link>
                        </Button>
                    </div>
                </div>
            </section>
        </div>
    );
}
