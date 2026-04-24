import { PromotionBanner } from '@/components/public/promotion-banner';
import { Card, CardContent } from '@/components/ui/card';
import { Head, Link } from '@inertiajs/react';
import { CalendarDays, ChevronRight } from 'lucide-react';

interface PublicReportSummary {
    workYear: number;
    workWeek: number;
    summary: string | null;
    publishedAt: string | null;
}

interface PaginatedPublicReports {
    data: PublicReportSummary[];
    links?: { url: string | null; label: string; active: boolean }[];
}

interface PublicProfilePageProps {
    profile: { handle: string; name: string };
    reports: PaginatedPublicReports;
}

export default function PublicProfilePage({
    profile,
    reports,
}: PublicProfilePageProps) {
    return (
        <div className="flex min-h-screen flex-col bg-muted/30">
            <Head title={`${profile.name} 的週報 · 週報通`}>
                <meta
                    name="description"
                    content={`查看 @${profile.handle} 的公開週報，由週報通提供。`}
                />
                <meta property="og:title" content={`${profile.name} 的週報`} />
                <meta
                    property="og:description"
                    content={`查看 @${profile.handle} 的公開週報`}
                />
                <meta property="og:type" content="profile" />
            </Head>

            <main className="flex-1">
                <div className="mx-auto flex max-w-3xl flex-col gap-6 px-4 py-10 sm:px-6 lg:px-8">
                    <header className="space-y-2">
                        <h1 className="text-3xl font-bold tracking-tight text-foreground sm:text-4xl">
                            {profile.name} 的週報
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            @{profile.handle}
                        </p>
                    </header>

                    {reports.data.length === 0 ? (
                        <Card className="border-2 border-dashed border-border/60">
                            <CardContent className="flex flex-col items-center gap-3 p-10 text-center">
                                <CalendarDays className="size-10 text-muted-foreground" />
                                <p className="text-sm text-muted-foreground">
                                    {profile.name} 還沒有公開的週報。
                                </p>
                            </CardContent>
                        </Card>
                    ) : (
                        <div className="space-y-3">
                            {reports.data.map((report) => (
                                <Link
                                    key={`${report.workYear}-${report.workWeek}`}
                                    href={`/u/${profile.handle}/${report.workYear}/${report.workWeek}`}
                                    className="block"
                                >
                                    <Card className="transition-colors hover:border-primary/40">
                                        <CardContent className="flex items-center justify-between gap-4 p-4">
                                            <div className="space-y-1">
                                                <h2 className="font-semibold text-foreground">
                                                    {report.workYear} 年 第{' '}
                                                    {report.workWeek} 週
                                                </h2>
                                                {report.summary && (
                                                    <p className="line-clamp-2 text-sm text-muted-foreground">
                                                        {report.summary}
                                                    </p>
                                                )}
                                                {report.publishedAt && (
                                                    <p className="text-xs text-muted-foreground">
                                                        發布於{' '}
                                                        {new Date(
                                                            report.publishedAt,
                                                        ).toLocaleDateString(
                                                            'zh-TW',
                                                        )}
                                                    </p>
                                                )}
                                            </div>
                                            <ChevronRight className="size-5 text-muted-foreground" />
                                        </CardContent>
                                    </Card>
                                </Link>
                            ))}
                        </div>
                    )}

                    {reports.links && reports.links.length > 3 && (
                        <nav className="flex items-center justify-center gap-1 pt-2">
                            {reports.links.map((link, i) => (
                                <Link
                                    key={i}
                                    href={link.url ?? '#'}
                                    className={`rounded-md px-3 py-1 text-sm ${link.active ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted'}`}
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ))}
                        </nav>
                    )}
                </div>
            </main>

            <PromotionBanner />
        </div>
    );
}
