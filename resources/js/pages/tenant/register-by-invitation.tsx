import GoogleAuthButton from '@/components/auth/google-auth-button';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import InputError from '@/components/input-error';
import { Head, useForm, router } from '@inertiajs/react';
import { Building2, UserPlus } from 'lucide-react';

interface PageProps {
    company: {
        name: string;
        slug: string;
    };
    organization: {
        id: number;
        name: string;
        type: string;
        division_id?: number | null;
        division_name?: string | null;
        department_id?: number | null;
        department_name?: string | null;
    };
    token: string;
    type: string;
}

const LEVEL_LABELS: Record<string, string> = {
    division: '事業群',
    department: '部門',
    team: '小組',
};

export default function RegisterByInvitationPage({ company, organization, token, type }: PageProps) {
    const form = useForm({
        token,
        type,
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const { data, errors, processing } = form;

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        router.post(`/app/${company.slug}/register-by-invitation`, data, {
            onError: () => {
                // Errors are handled by InputError components
            },
        });
    };

    return (
        <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-background to-muted p-4">
            <Head title={`加入 ${company.name}`} />
            <Card className="w-full max-w-md">
                <CardHeader className="text-center">
                    <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-primary/10">
                        <Building2 className="h-6 w-6 text-primary" />
                    </div>
                    <CardTitle className="text-2xl">加入 {company.name}</CardTitle>
                    <CardDescription>
                        您已被邀請加入 {LEVEL_LABELS[type] || type}「{organization.name}」
                    </CardDescription>
                    {organization.division_name && (
                        <CardDescription className="text-xs">
                            所屬事業群：{organization.division_name}
                        </CardDescription>
                    )}
                    {organization.department_name && (
                        <CardDescription className="text-xs">
                            所屬部門：{organization.department_name}
                        </CardDescription>
                    )}
                </CardHeader>
                <CardContent>
                    <GoogleAuthButton
                        intent="organization_invitation"
                        organizationInvitation={{
                            companySlug: company.slug,
                            token,
                            type,
                        }}
                    />

                    <div className="relative my-4">
                        <div className="absolute inset-0 flex items-center">
                            <Separator />
                        </div>
                        <div className="relative flex justify-center text-xs uppercase">
                            <span className="bg-background px-2 text-muted-foreground">
                                或
                            </span>
                        </div>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="name">姓名</Label>
                            <Input
                                id="name"
                                type="text"
                                value={data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                                required
                                disabled={processing}
                                autoFocus
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="email">電子郵件</Label>
                            <Input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(e) => form.setData('email', e.target.value)}
                                required
                                disabled={processing}
                            />
                            <InputError message={errors.email} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="password">密碼</Label>
                            <Input
                                id="password"
                                type="password"
                                value={data.password}
                                onChange={(e) => form.setData('password', e.target.value)}
                                required
                                disabled={processing}
                                minLength={8}
                            />
                            <InputError message={errors.password} />
                            <p className="text-xs text-muted-foreground">
                                密碼長度至少 8 個字元
                            </p>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="password_confirmation">確認密碼</Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                value={data.password_confirmation}
                                onChange={(e) => form.setData('password_confirmation', e.target.value)}
                                required
                                disabled={processing}
                                minLength={8}
                            />
                            <InputError message={errors.password_confirmation} />
                        </div>

                        <Button type="submit" className="w-full" disabled={processing}>
                            {processing ? (
                                '註冊中...'
                            ) : (
                                <>
                                    <UserPlus className="mr-2 h-4 w-4" />
                                    完成註冊
                                </>
                            )}
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    );
}

