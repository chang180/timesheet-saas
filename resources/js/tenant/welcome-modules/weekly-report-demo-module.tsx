import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import {
    BarChart3,
    CalendarClock,
    CheckCircle2,
    GitBranch,
    Sparkles,
} from 'lucide-react';

interface WeeklyReportDemoModuleProps {
    highlights?: string[];
    brandColor?: string;
}

const defaultTasks = [
    {
        title: 'API Gateway 串接',
        issue: 'JIRA-4567',
        hours: 12.5,
        status: 'submitted',
    },
    {
        title: '週報預覽優化',
        issue: 'JIRA-4621',
        hours: 6,
        status: 'reviewing',
    },
    {
        title: '假日提醒自動化',
        issue: 'BACKLOG-92',
        hours: 4.5,
        status: 'draft',
    },
];

const statusStyles: Record<
    typeof defaultTasks[number]['status'],
    { label: string; className: string }
> = {
    submitted: { label: '已提交', className: 'bg-emerald-500/15 text-emerald-600' },
    reviewing: { label: '審核中', className: 'bg-amber-500/15 text-amber-600' },
    draft: { label: '草稿', className: 'bg-slate-500/15 text-slate-600' },
};

export function WeeklyReportDemoModule({
    highlights,
    brandColor,
}: WeeklyReportDemoModuleProps) {
    const accentColor = brandColor ?? '#2563eb';
    const bulletPoints =
        highlights && highlights.length > 0
            ? highlights.slice(0, 4)
            : [
                  '拖曳排序同步更新主管檢視順序',
                  'Redmine/Jira 連動，自動帶入工時與標題',
                  '假日工時超額即時提醒',
                  '提交後自動推播 Slack / Email 通知',
              ];

    return (
        <section className="bg-white px-6 py-16 dark:bg-gray-900">
            <div className="mx-auto max-w-6xl rounded-3xl border border-gray-200/70 bg-white/70 p-10 shadow-xl backdrop-blur-sm dark:border-gray-700/50 dark:bg-gray-900/70">
                <div className="grid gap-12 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1fr)] lg:items-center">
                    <div className="space-y-6">
                        <Badge
                            className="bg-[var(--tenant-brand-color, #2563eb)] text-white"
                            style={{ backgroundColor: accentColor }}
                        >
                            Weekly Report Demo
                        </Badge>
                        <h2 className="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                            週報填寫，不再只是代辦事項
                        </h2>
                        <p className="text-base leading-7 text-gray-600 dark:text-gray-300">
                            透過視覺化提示與即時驗證，讓成員在 5 分鐘內完成週報，主管也能快速掌握風險與人力配置。
                        </p>
                        <ul className="space-y-3">
                            {bulletPoints.map((item, index) => (
                                <li
                                    key={`highlight-${index}`}
                                    className="flex items-start gap-3 text-sm text-gray-600 dark:text-gray-300"
                                >
                                    <CheckCircle2
                                        className="mt-0.5 size-4 shrink-0 text-[var(--tenant-brand-color,#2563eb)]"
                                        style={{ color: accentColor }}
                                    />
                                    <span>{item}</span>
                                </li>
                            ))}
                        </ul>
                    </div>

                    <div className="space-y-4">
                        <Card className="border-0 bg-white/90 shadow-lg ring-1 ring-gray-200/60 dark:bg-gray-950/80 dark:ring-gray-700/60">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0">
                                <CardTitle className="text-lg font-semibold text-gray-900 dark:text-white">
                                    本週任務一覽
                                </CardTitle>
                                <span
                                    className="rounded-full px-3 py-1 text-xs font-medium text-white"
                                    style={{ backgroundColor: accentColor }}
                                >
                                    自動計算時數
                                </span>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {defaultTasks.map((task, index) => {
                                    const status = statusStyles[task.status];

                                    return (
                                        <div
                                            key={`task-${index}`}
                                            className="flex flex-col gap-2 rounded-2xl border border-gray-200/70 p-4 dark:border-gray-800/80"
                                        >
                                            <div className="flex flex-wrap items-center justify-between gap-2">
                                                <p className="text-sm font-semibold text-gray-900 dark:text-white">
                                                    {task.title}
                                                </p>
                                                <span
                                                    className={cn(
                                                        'rounded-full px-3 py-1 text-xs font-medium',
                                                        status.className
                                                    )}
                                                >
                                                    {status.label}
                                                </span>
                                            </div>
                                            <div className="flex flex-wrap items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                                <GitBranch className="size-4" />
                                                <span>{task.issue}</span>
                                                <span>·</span>
                                                <CalendarClock className="size-4" />
                                                <span>{task.hours} 小時</span>
                                            </div>
                                        </div>
                                    );
                                })}

                                <div className="flex items-center justify-between rounded-2xl bg-[var(--tenant-brand-color,#2563eb)]/10 p-4 text-sm text-[var(--tenant-brand-color,#2563eb)] dark:bg-[var(--tenant-brand-color,#2563eb)]/20 dark:text-white">
                                    <div className="flex items-center gap-3">
                                        <BarChart3 className="size-5" />
                                        <div>
                                            <p className="font-semibold">
                                                本週累計 23.0 小時
                                            </p>
                                            <p className="text-xs opacity-80">
                                                超過上限時自動提醒主管與成員
                                            </p>
                                        </div>
                                    </div>
                                    <Sparkles className="size-5" />
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </section>
    );
}

