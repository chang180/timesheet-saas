import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/react';
import { Check, Loader2, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';

interface HandlePageProps {
    currentHandle: string | null;
}

type AvailabilityStatus =
    | { state: 'idle' }
    | { state: 'checking' }
    | { state: 'available' }
    | { state: 'unavailable'; reason: 'reserved' | 'taken' | 'invalid' };

const reasonMessage: Record<string, string> = {
    reserved: '此代號已被系統保留，請試試其他。',
    taken: '此代號已被其他用戶使用。',
    invalid: '代號格式不正確（3–30 字、小寫英數、底線、連字號）。',
};

export default function HandleSettingsPage({ currentHandle }: HandlePageProps) {
    const { flash } = usePage<{ flash: { success?: string } }>().props;

    const { data, setData, put, processing, errors } = useForm<{
        handle: string;
    }>({
        handle: currentHandle ?? '',
    });

    const [asyncResult, setAsyncResult] = useState<
        | { handle: string; state: 'checking' }
        | {
              handle: string;
              state: 'available' | 'unavailable';
              reason?: 'reserved' | 'taken' | 'invalid';
          }
        | null
    >(null);
    const latestValue = useRef<string>('');

    const normalized = useMemo(
        () => data.handle.toLowerCase().trim(),
        [data.handle],
    );

    const availability: AvailabilityStatus = useMemo(() => {
        if (normalized === '' || normalized === (currentHandle ?? '')) {
            return { state: 'idle' };
        }
        if (!/^[a-z0-9_-]{3,30}$/.test(normalized)) {
            return { state: 'unavailable', reason: 'invalid' };
        }
        if (asyncResult && asyncResult.handle === normalized) {
            if (asyncResult.state === 'checking') return { state: 'checking' };
            if (asyncResult.state === 'available')
                return { state: 'available' };
            return {
                state: 'unavailable',
                reason: asyncResult.reason ?? 'invalid',
            };
        }
        return { state: 'checking' };
    }, [normalized, currentHandle, asyncResult]);

    useEffect(() => {
        latestValue.current = normalized;

        if (normalized === '' || normalized === (currentHandle ?? '')) {
            return;
        }

        if (!/^[a-z0-9_-]{3,30}$/.test(normalized)) {
            return;
        }

        const handle = normalized;
        const timer = setTimeout(async () => {
            try {
                const res = await fetch(
                    `/settings/handle/check?handle=${encodeURIComponent(handle)}`,
                    { headers: { Accept: 'application/json' } },
                );
                const body = (await res.json()) as {
                    available: boolean;
                    reason?: 'reserved' | 'taken' | 'invalid';
                };

                if (latestValue.current !== handle) return;
                setAsyncResult({
                    handle,
                    state: body.available ? 'available' : 'unavailable',
                    reason: body.reason,
                });
            } catch {
                // Network failure — ignore; server-side validation catches it
            }
        }, 300);

        return () => clearTimeout(timer);
    }, [normalized, currentHandle]);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: '我的代號', href: '/settings/handle' },
    ];

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put('/settings/handle', { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="我的代號" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="我的代號"
                        description="設定代號後，你就能公開分享你的個人週報。"
                    />

                    {flash?.success && (
                        <div className="rounded border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                            {flash.success}
                        </div>
                    )}

                    <form onSubmit={submit} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="handle">代號</Label>
                            <div className="flex items-center gap-2">
                                <span className="text-sm text-muted-foreground">
                                    /u/
                                </span>
                                <Input
                                    id="handle"
                                    value={data.handle}
                                    onChange={(e) =>
                                        setData('handle', e.target.value)
                                    }
                                    placeholder="你的代號"
                                    autoComplete="off"
                                    className="max-w-xs"
                                />
                                <AvailabilityBadge status={availability} />
                            </div>
                            {availability.state === 'unavailable' && (
                                <p className="text-xs text-red-600">
                                    {reasonMessage[availability.reason]}
                                </p>
                            )}
                            {errors.handle && (
                                <p className="text-xs text-red-600">
                                    {errors.handle}
                                </p>
                            )}
                            <p className="text-xs text-muted-foreground">
                                規則：3–30
                                字、小寫英數、底線（_）與連字號（-）。
                            </p>
                        </div>

                        {data.handle &&
                            availability.state === 'available' &&
                            !errors.handle && (
                                <p className="text-xs text-muted-foreground">
                                    你的公開頁面將位於：
                                    <code className="rounded bg-muted px-1 py-0.5">
                                        /u/{normalized}
                                    </code>
                                </p>
                            )}

                        <Button
                            type="submit"
                            disabled={
                                processing ||
                                availability.state === 'checking' ||
                                availability.state === 'unavailable'
                            }
                        >
                            儲存
                        </Button>
                    </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}

function AvailabilityBadge({ status }: { status: AvailabilityStatus }) {
    if (status.state === 'checking') {
        return (
            <Loader2 className="size-4 animate-spin text-muted-foreground" />
        );
    }
    if (status.state === 'available') {
        return <Check className="size-4 text-emerald-600" />;
    }
    if (status.state === 'unavailable') {
        return <X className="size-4 text-red-600" />;
    }
    return null;
}
