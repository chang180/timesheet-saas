import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    AlertTriangle,
    CalendarCheck,
    CheckCircle2,
    ClipboardList,
    Timer,
} from 'lucide-react';

export function WelcomeShowcase() {
    return (
        <div className="rounded-3xl border border-gray-200/80 bg-white/80 p-8 shadow-xl backdrop-blur-md dark:border-gray-700/60 dark:bg-gray-900/70">
            <div className="flex flex-col gap-8 lg:flex-row">
                <Card className="w-full bg-gray-50/80 shadow-none dark:bg-gray-900/70">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-xl">
                            <CheckCircle2 className="size-5 text-emerald-500" />
                            本週週報概覽
                        </CardTitle>
                        <CardDescription>
                            拖曳排序、同步 Issue 資料、自動累積工時
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        <div className="space-y-3 rounded-xl bg-white/80 p-4 dark:bg-gray-950/70">
                            <div className="flex items-center justify-between">
                                <span className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                    Sprint API Gateway
                                </span>
                                <Badge className="bg-emerald-500/90 text-white">
                                    完成
                                </Badge>
                            </div>
                            <p className="text-sm text-gray-600 dark:text-gray-300">
                                串接用戶 slug 與驗證流程，完成 12.5 小時
                            </p>
                            <div className="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                <ClipboardList className="size-4" />
                                Redmine #3241 · 估時 14h
                            </div>
                        </div>

                        <div className="space-y-3 rounded-xl bg-white/80 p-4 dark:bg-gray-950/70">
                            <div className="flex items-center justify-between">
                                <span className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                    UX 工作坊
                                </span>
                                <Badge variant="outline" className="border-amber-500/60 text-amber-600">
                                    進行中
                                </Badge>
                            </div>
                            <p className="text-sm text-gray-600 dark:text-gray-300">
                                設計歡迎頁預覽動畫，收斂品牌語彙
                            </p>
                            <div className="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                <CalendarCheck className="size-4" />
                                6.0 小時 · 已邀 QA 參與
                            </div>
                        </div>

                        <div className="rounded-xl bg-emerald-500/10 p-4 text-sm text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-200">
                            <div className="flex items-center gap-2">
                                <Timer className="size-4" />
                                <span>本週累計：18.5 小時 · 剩餘 9.5 小時</span>
                            </div>
                            <p className="mt-2 text-xs text-emerald-700/80 dark:text-emerald-200/80">
                                超過上限時自動提示，主管可即時追蹤。
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <div className="flex w-full flex-col gap-6">
                    <Card className="bg-white/90 shadow-none dark:bg-gray-900/70">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-xl">
                                <ClipboardList className="size-5 text-sky-500" />
                                快速複製 · 自動填寫
                            </CardTitle>
                            <CardDescription>
                                一鍵帶入上一週，Issue 關聯自動補齊
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4 text-sm text-gray-600 dark:text-gray-300">
                            <div className="rounded-lg border border-dashed border-gray-200/80 p-3 dark:border-gray-700/40">
                                <span className="font-medium text-gray-900 dark:text-white">
                                    Copy Previous Week
                                </span>
                                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    選取任務後即可調整工時與備註，拖曳排序同步更新。
                                </p>
                            </div>
                            <div className="rounded-lg bg-sky-500/10 p-3 text-xs text-sky-700 dark:bg-sky-500/20 dark:text-sky-100">
                                Jira JIRA-1289 · 自動帶入預估工時 8h · 負責人：前端小組
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="bg-white/90 shadow-none dark:bg-gray-900/70">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-xl">
                                <AlertTriangle className="size-5 text-amber-500" />
                                假日警示 · 防止漏填
                            </CardTitle>
                            <CardDescription>
                                整合行事曆，遇到國定假日立即提醒
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3 text-sm text-gray-600 dark:text-gray-300">
                            <div className="rounded-lg bg-amber-500/10 p-3 text-amber-700 dark:bg-amber-500/20 dark:text-amber-100">
                                下週一（10/10）為國慶日，請確認工時安排或設定補班日。
                            </div>
                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                支援公司行事曆同步、跨國假期與客製提醒。
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    );
}

