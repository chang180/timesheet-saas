import { PromotionBanner } from '@/components/public/promotion-banner';
import { Card, CardContent } from '@/components/ui/card';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, Target } from 'lucide-react';

interface PublicItem {
    title: string;
    content: string | null;
    hoursSpent?: number;
    plannedHours?: number | null;
    tags: string[];
}

interface PublicReport {
    workYear: number;
    workWeek: number;
    summary: string | null;
    publishedAt: string | null;
    currentWeek: PublicItem[];
    nextWeek: PublicItem[];
}

interface PublicReportPageProps {
    profile: { handle: string; name: string };
    report: PublicReport;
}

export default function PublicReportPage({
    profile,
    report,
}: PublicReportPageProps) {
    return (
        <div className="flex min-h-screen flex-col bg-muted/30">
            <Head
                title={`${profile.name} · ${report.workYear}/${report.workWeek} · 週報通`}
            >
                <meta
                    name="description"
                    content={`${profile.name} 於 ${report.workYear} 年第 ${report.workWeek} 週的公開週報`}
                />
                <meta
                    property="og:title"
                    content={`${profile.name} · ${report.workYear}/${report.workWeek}`}
                />
                <meta
                    property="og:description"
                    content={`查看 @${profile.handle} 的公開週報`}
                />
                <meta property="og:type" content="article" />
            </Head>

            <main className="flex-1">
                <div className="mx-auto flex max-w-3xl flex-col gap-6 px-4 py-10 sm:px-6 lg:px-8">
                    <Link
                        href={`/u/${profile.handle}`}
                        className="inline-flex w-fit items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        回到 {profile.name} 的所有週報
                    </Link>

                    <header className="space-y-2">
                        <h1 className="text-3xl font-bold tracking-tight text-foreground sm:text-4xl">
                            {report.workYear} 年 第 {report.workWeek} 週
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            由 {profile.name}
                            {report.publishedAt &&
                                ` 於 ${new Date(report.publishedAt).toLocaleDateString('zh-TW')} 發布`}
                        </p>
                    </header>

                    {report.summary && (
                        <Card>
                            <CardContent className="p-5 text-sm leading-relaxed text-foreground">
                                {report.summary}
                            </CardContent>
                        </Card>
                    )}

                    <section className="space-y-3">
                        <h2 className="flex items-center gap-2 text-lg font-semibold">
                            <CheckCircle2 className="size-5 text-emerald-600" />
                            本週工作
                        </h2>
                        {report.currentWeek.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                本週無工作項目紀錄。
                            </p>
                        ) : (
                            <div className="space-y-3">
                                {report.currentWeek.map((item, i) => (
                                    <Card key={i}>
                                        <CardContent className="space-y-2 p-4">
                                            <h3 className="font-semibold text-foreground">
                                                {item.title}
                                            </h3>
                                            {item.content && (
                                                <p className="text-sm whitespace-pre-wrap text-muted-foreground">
                                                    {item.content}
                                                </p>
                                            )}
                                            {typeof item.hoursSpent ===
                                                'number' &&
                                                item.hoursSpent > 0 && (
                                                    <p className="text-xs text-muted-foreground">
                                                        工時：
                                                        {item.hoursSpent.toFixed(
                                                            1,
                                                        )}{' '}
                                                        小時
                                                    </p>
                                                )}
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        )}
                    </section>

                    {report.nextWeek.length > 0 && (
                        <section className="space-y-3">
                            <h2 className="flex items-center gap-2 text-lg font-semibold">
                                <Target className="size-5 text-sky-600" />
                                下週計畫
                            </h2>
                            <div className="space-y-3">
                                {report.nextWeek.map((item, i) => (
                                    <Card key={i}>
                                        <CardContent className="space-y-2 p-4">
                                            <h3 className="font-semibold text-foreground">
                                                {item.title}
                                            </h3>
                                            {item.content && (
                                                <p className="text-sm whitespace-pre-wrap text-muted-foreground">
                                                    {item.content}
                                                </p>
                                            )}
                                            {item.plannedHours && (
                                                <p className="text-xs text-muted-foreground">
                                                    預估：
                                                    {item.plannedHours.toFixed(
                                                        1,
                                                    )}{' '}
                                                    小時
                                                </p>
                                            )}
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        </section>
                    )}
                </div>
            </main>

            <PromotionBanner />
        </div>
    );
}
