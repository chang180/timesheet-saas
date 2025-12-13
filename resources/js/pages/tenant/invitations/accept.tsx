import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import { Head, useForm } from '@inertiajs/react';
import { CheckCircle2, Mail } from 'lucide-react';
import tenantRoutes from '@/routes/tenant';
import authApi from '@/routes/api/v1/tenant/auth';

interface InvitationAcceptProps {
    company: {
        name: string;
        slug: string;
    };
    user: {
        name: string;
        email: string;
    };
    token: string;
}

export default function InvitationAcceptPage({
    company,
    user,
    token,
}: InvitationAcceptProps) {
    const form = useForm({
        token,
        password: '',
        password_confirmation: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        form.post(authApi.invitations.accept.url({ company: company.slug }), {
            data: {
                token: form.data.token,
                password: form.data.password,
                password_confirmation: form.data.password_confirmation,
            },
            preserveScroll: true,
            onSuccess: () => {
                // 成功後會自動導向 dashboard
            },
        });
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-12 dark:bg-gray-900 sm:px-6 lg:px-8">
            <Head title="接受邀請" />
            <Card className="w-full max-w-md">
                <CardHeader className="space-y-1 text-center">
                    <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-primary/10">
                        <Mail className="h-6 w-6 text-primary" />
                    </div>
                    <CardTitle className="text-2xl font-bold">
                        接受邀請
                    </CardTitle>
                    <CardDescription>
                        您已被邀請加入 <strong>{company.name}</strong>
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="mb-6 space-y-2 rounded-lg border border-border bg-muted/50 p-4">
                        <div className="flex items-center gap-2 text-sm">
                            <CheckCircle2 className="h-4 w-4 text-emerald-600" />
                            <span className="font-medium">邀請資訊</span>
                        </div>
                        <div className="space-y-1 text-sm text-muted-foreground">
                            <div>姓名：{user.name}</div>
                            <div>Email：{user.email}</div>
                            <div>公司：{company.name}</div>
                        </div>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="password">
                                設定密碼 <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="password"
                                type="password"
                                value={form.data.password}
                                onChange={(e) =>
                                    form.setData('password', e.target.value)
                                }
                                required
                                autoFocus
                                disabled={form.processing}
                            />
                            <InputError message={form.errors.password} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="password_confirmation">
                                確認密碼 <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                value={form.data.password_confirmation}
                                onChange={(e) =>
                                    form.setData('password_confirmation', e.target.value)
                                }
                                required
                                disabled={form.processing}
                            />
                            <InputError message={form.errors.password_confirmation} />
                        </div>

                        {form.errors.token && (
                            <div className="rounded-lg border border-destructive bg-destructive/10 p-3 text-sm text-destructive">
                                {form.errors.token}
                            </div>
                        )}

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={form.processing}
                        >
                            {form.processing ? '處理中...' : '接受邀請並登入'}
                        </Button>
                    </form>

                    <p className="mt-4 text-center text-xs text-muted-foreground">
                        此邀請連結將在 7 天後過期
                    </p>
                </CardContent>
            </Card>
        </div>
    );
}
