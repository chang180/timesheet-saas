import AppearanceToggleDropdown from '@/components/appearance-dropdown';
import AppLogoIcon from '@/components/app-logo-icon';
import { home } from '@/routes';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { type PropsWithChildren, type ReactNode } from 'react';

interface AuthLayoutProps {
    title?: string;
    description?: string;
    aside?: ReactNode;
}

export default function AuthSplitLayout({
    children,
    title,
    description,
    aside,
}: PropsWithChildren<AuthLayoutProps>) {
    const { quote } = usePage<SharedData>().props;
    const brandTitle = '週報通 Timesheet SaaS';

    const defaultQuote = quote ? (
        <blockquote className="space-y-3 text-left text-sm text-neutral-200/90">
            <p className="text-lg leading-7 text-white/90">
                &ldquo;{quote.message}&rdquo;
            </p>
            <footer className="text-xs uppercase tracking-wide text-neutral-300">
                {quote.author}
            </footer>
        </blockquote>
    ) : (
        <div className="space-y-3 text-sm text-neutral-200/85">
            <p className="text-base font-medium text-white">
                Timesheet SaaS，成就多用戶週報的最佳實踐。
            </p>
            <p className="text-sm leading-relaxed">
                整合週報填寫、審核與提醒流程，讓團隊清楚掌握每一個專案節奏。
            </p>
        </div>
    );

    return (
        <div className="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
            <div className="relative hidden h-full flex-col bg-muted p-10 text-white lg:flex dark:border-r">
                <div className="absolute inset-0 bg-linear-to-br from-indigo-900 via-slate-900 to-emerald-900 opacity-95" />
                <div className="relative z-20 flex h-full flex-col">
                    <div className="flex items-center justify-between">
                        <Link
                            href={home()}
                            className="flex items-center text-lg font-semibold tracking-tight"
                        >
                            <div className="flex size-9 items-center justify-center rounded-md bg-white/10 ring-1 ring-white/30 backdrop-blur">
                                <AppLogoIcon className="size-5 fill-current text-white" />
                            </div>
                            <span className="ml-3">{brandTitle}</span>
                        </Link>
                        <AppearanceToggleDropdown />
                    </div>
                    {aside ? (
                        <div className="mt-14 flex-1">
                            {aside}
                        </div>
                    ) : (
                        <div className="mt-auto">
                            {defaultQuote}
                        </div>
                    )}
                </div>
            </div>
            <div className="w-full lg:p-8">
                <div className="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[370px]">
                    <div className="flex items-center justify-between lg:hidden">
                        <Link
                            href={home()}
                            className="relative z-20 flex items-center gap-3 rounded-xl border border-border/70 bg-background/70 px-3 py-2 shadow-sm backdrop-blur-sm"
                        >
                            <div className="flex size-10 items-center justify-center rounded-md bg-linear-to-br from-indigo-500 via-sky-500 to-emerald-500 shadow-sm">
                                <AppLogoIcon className="h-6 fill-current text-white" />
                            </div>
                            <span className="text-sm font-semibold text-foreground">
                                {brandTitle}
                            </span>
                        </Link>
                        <AppearanceToggleDropdown />
                    </div>
                    <div className="flex flex-col items-start gap-2 text-left sm:items-center sm:text-center">
                        <h1 className="text-xl font-medium">{title}</h1>
                        <p className="text-sm text-balance text-muted-foreground">
                            {description}
                        </p>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
