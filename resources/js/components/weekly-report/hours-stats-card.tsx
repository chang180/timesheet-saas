import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { WorkRangeStats } from '@/lib/holiday-utils';
import { Clock } from 'lucide-react';

interface HoursStatsCardProps {
    totalCurrentWeekHours: number;
    totalNextWeekHours: number;
    currentWeekCapacity: WorkRangeStats | null;
    nextWeekCapacity: WorkRangeStats | null;
    currentWeekItemCount: number;
    nextWeekItemCount: number;
}

export function HoursStatsCard({
    totalCurrentWeekHours,
    totalNextWeekHours,
    currentWeekCapacity,
    nextWeekCapacity,
    currentWeekItemCount,
    nextWeekItemCount,
}: HoursStatsCardProps) {
    const currentWeekTargetHours = currentWeekCapacity?.availableHours ?? 40;
    const nextWeekTargetHours = nextWeekCapacity?.availableHours ?? 40;

    return (
        <Card className="border-2 border-border/60 shadow-lg">
            <CardHeader className="border-b-2 border-border/60 bg-linear-to-r from-muted/50 to-muted/30 pb-5">
                <CardTitle className="text-xl font-bold text-foreground sm:text-2xl">
                    工時統計
                </CardTitle>
            </CardHeader>
            <CardContent className="pt-8">
                <div className="grid gap-6 xl:grid-cols-4">
                    <div className="relative rounded-xl border-2 border-blue-300 bg-linear-to-br from-blue-50 via-blue-100/50 to-blue-50 p-8 shadow-lg dark:border-blue-700 dark:from-blue-950/30 dark:via-blue-900/20 dark:to-blue-950/30">
                        <div className="absolute top-4 right-4">
                            <Clock className="size-6 text-blue-400/40 dark:text-blue-500/30" />
                        </div>
                        <div className="text-sm font-semibold text-muted-foreground">
                            本週實際總工時
                        </div>
                        <div className="mt-3 text-4xl font-bold text-blue-600 sm:text-5xl dark:text-blue-400">
                            {totalCurrentWeekHours.toFixed(1)}
                        </div>
                        <div className="mt-2 text-sm font-medium text-muted-foreground">
                            小時
                        </div>
                        <div className="mt-4 text-sm font-medium text-muted-foreground">
                            {currentWeekItemCount} 個項目
                        </div>
                        {currentWeekCapacity &&
                            totalCurrentWeekHours >
                                currentWeekCapacity.availableHours && (
                                <div className="mt-3 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200">
                                    已高於本週可排工時{' '}
                                    {Math.abs(
                                        totalCurrentWeekHours -
                                            currentWeekCapacity.availableHours,
                                    ).toFixed(1)}{' '}
                                    小時
                                </div>
                            )}
                    </div>
                    <div className="relative rounded-xl border-2 border-sky-300 bg-linear-to-br from-sky-50 via-sky-100/50 to-sky-50 p-8 shadow-lg dark:border-sky-700 dark:from-sky-950/30 dark:via-sky-900/20 dark:to-sky-950/30">
                        <div className="absolute top-4 right-4">
                            <Clock className="size-6 text-sky-400/40 dark:text-sky-500/30" />
                        </div>
                        <div className="text-sm font-semibold text-muted-foreground">
                            本週應填總工時
                        </div>
                        <div className="mt-3 text-4xl font-bold text-sky-600 sm:text-5xl dark:text-sky-400">
                            {currentWeekTargetHours.toFixed(1)}
                        </div>
                        <div className="mt-2 text-sm font-medium text-muted-foreground">
                            小時
                        </div>
                        <div className="mt-4 text-sm font-medium text-muted-foreground">
                            與實際差異{' '}
                            {(
                                totalCurrentWeekHours - currentWeekTargetHours
                            ).toFixed(1)}{' '}
                            小時
                        </div>
                        <div className="mt-3 text-sm text-muted-foreground">
                            依本週假日與補班日自動計算
                        </div>
                    </div>
                    <div className="relative rounded-xl border-2 border-emerald-300 bg-linear-to-br from-emerald-50 via-emerald-100/50 to-emerald-50 p-8 shadow-lg dark:border-emerald-700 dark:from-emerald-950/30 dark:via-emerald-900/20 dark:to-emerald-950/30">
                        <div className="absolute top-4 right-4">
                            <Clock className="size-6 text-emerald-400/40 dark:text-emerald-500/30" />
                        </div>
                        <div className="text-sm font-semibold text-muted-foreground">
                            下週目前已排工時
                        </div>
                        <div className="mt-3 text-4xl font-bold text-emerald-600 sm:text-5xl dark:text-emerald-400">
                            {totalNextWeekHours.toFixed(1)}
                        </div>
                        <div className="mt-2 text-sm font-medium text-muted-foreground">
                            小時
                        </div>
                        <div className="mt-4 text-sm font-medium text-muted-foreground">
                            {nextWeekItemCount} 個項目
                        </div>
                    </div>
                    <div className="relative rounded-xl border-2 border-teal-300 bg-linear-to-br from-teal-50 via-teal-100/50 to-teal-50 p-8 shadow-lg dark:border-teal-700 dark:from-teal-950/30 dark:via-teal-900/20 dark:to-teal-950/30">
                        <div className="absolute top-4 right-4">
                            <Clock className="size-6 text-teal-400/40 dark:text-teal-500/30" />
                        </div>
                        <div className="text-sm font-semibold text-muted-foreground">
                            下週可排總工時
                        </div>
                        <div className="mt-3 text-4xl font-bold text-teal-600 sm:text-5xl dark:text-teal-400">
                            {nextWeekTargetHours.toFixed(1)}
                        </div>
                        <div className="mt-2 text-sm font-medium text-muted-foreground">
                            小時
                        </div>
                        <div className="mt-4 text-sm font-medium text-muted-foreground">
                            與已排差異{' '}
                            {(totalNextWeekHours - nextWeekTargetHours).toFixed(
                                1,
                            )}{' '}
                            小時
                        </div>
                        {nextWeekCapacity && (
                            <div className="mt-3 text-sm text-muted-foreground">
                                依下週假日與補班日自動計算
                            </div>
                        )}
                        {nextWeekCapacity &&
                            totalNextWeekHours >
                                nextWeekCapacity.availableHours && (
                                <div className="mt-3 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200">
                                    已高於下週可排工時{' '}
                                    {Math.abs(
                                        totalNextWeekHours -
                                            nextWeekCapacity.availableHours,
                                    ).toFixed(1)}{' '}
                                    小時
                                </div>
                            )}
                    </div>
                </div>
                {(currentWeekCapacity || nextWeekCapacity) && (
                    <div className="mt-6 grid gap-4 lg:grid-cols-2">
                        {currentWeekCapacity && (
                            <div className="rounded-xl border border-border/60 bg-muted/20 p-4">
                                <div className="text-sm font-semibold text-foreground">
                                    本週可排工時基準
                                </div>
                                <div className="mt-2 text-sm text-muted-foreground">
                                    工作日 {currentWeekCapacity.workingDays}{' '}
                                    天，扣除假日{' '}
                                    {currentWeekCapacity.holidayDays} 天
                                    {currentWeekCapacity.makeupWorkdays > 0 &&
                                        `，含補班日 ${currentWeekCapacity.makeupWorkdays} 天`}
                                </div>
                            </div>
                        )}
                        {nextWeekCapacity && (
                            <div className="rounded-xl border border-border/60 bg-muted/20 p-4">
                                <div className="text-sm font-semibold text-foreground">
                                    下週可排工時基準
                                </div>
                                <div className="mt-2 text-sm text-muted-foreground">
                                    工作日 {nextWeekCapacity.workingDays}{' '}
                                    天，扣除假日 {nextWeekCapacity.holidayDays}{' '}
                                    天
                                    {nextWeekCapacity.makeupWorkdays > 0 &&
                                        `，含補班日 ${nextWeekCapacity.makeupWorkdays} 天`}
                                </div>
                            </div>
                        )}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
