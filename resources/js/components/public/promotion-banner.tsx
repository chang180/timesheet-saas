import { Link, usePage } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';

export function PromotionBanner() {
    const { auth } = usePage<{ auth?: { user?: unknown } }>().props;
    const isAuthenticated = Boolean(auth?.user);

    return (
        <footer
            className="sticky bottom-0 border-t border-border/60 bg-background/95 py-3 backdrop-blur"
            data-testid="promotion-banner"
        >
            <div className="mx-auto flex max-w-4xl flex-col items-center justify-between gap-2 px-4 text-sm sm:flex-row">
                <span className="text-muted-foreground">
                    由 <strong className="text-foreground">週報通</strong> 提供
                    · 輕量個人週報
                </span>
                <Link
                    href="/"
                    className="inline-flex items-center gap-1 text-primary hover:underline"
                    data-testid="promotion-banner-cta"
                >
                    {isAuthenticated ? '前往週報通' : '建立你的個人週報'}
                    <ArrowRight className="size-4" />
                </Link>
            </div>
        </footer>
    );
}
