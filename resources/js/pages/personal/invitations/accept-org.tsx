import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Head, router } from '@inertiajs/react';
import { Building2, CheckCircle2 } from 'lucide-react';
import { useState } from 'react';

interface OrgData {
    id: number;
    name: string;
    type: string;
    division_name?: string;
    department_name?: string;
}

interface Props {
    company: {
        name: string;
        slug: string;
    };
    organization: OrgData;
    token: string;
    type: string;
}

export default function AcceptOrgInvitationPage({
    company,
    organization,
    token,
    type,
}: Props) {
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const handleAccept = (e: React.FormEvent) => {
        e.preventDefault();
        setProcessing(true);
        setError(null);
        router.post(
            `/me/invitations/join/${company.slug}/${token}/${type}`,
            {},
            {
                onError: (errors) => {
                    setError(errors.company ?? '發生錯誤，請重試。');
                    setProcessing(false);
                },
                onFinish: () => setProcessing(false),
            },
        );
    };

    const orgLabel = {
        division: '單位',
        department: '部門',
        team: '小組',
    }[type] ?? type;

    return (
        <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8 dark:bg-gray-900">
            <Head title="加入組織" />
            <Card className="w-full max-w-md">
                <CardHeader className="space-y-1 text-center">
                    <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-primary/10">
                        <Building2 className="h-6 w-6 text-primary" />
                    </div>
                    <CardTitle className="text-2xl font-bold">加入組織</CardTitle>
                    <CardDescription>
                        您已被邀請加入 <strong>{company.name}</strong>
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-6">
                    <div className="rounded-lg border border-border bg-muted/50 p-4 space-y-2">
                        <div className="flex items-center gap-2 text-sm">
                            <CheckCircle2 className="h-4 w-4 text-emerald-600" />
                            <span className="font-medium">邀請詳情</span>
                        </div>
                        <div className="space-y-1 text-sm text-muted-foreground">
                            <div>公司：{company.name}</div>
                            <div>
                                {orgLabel}：{organization.name}
                            </div>
                            {organization.division_name && (
                                <div>單位：{organization.division_name}</div>
                            )}
                            {organization.department_name && (
                                <div>部門：{organization.department_name}</div>
                            )}
                        </div>
                    </div>

                    <form onSubmit={handleAccept} className="space-y-4">
                        {error && (
                            <div className="rounded-lg border border-destructive bg-destructive/10 p-3 text-sm text-destructive">
                                {error}
                            </div>
                        )}
                        <Button
                            type="submit"
                            className="w-full"
                            disabled={processing}
                        >
                            {processing
                                ? '處理中...'
                                : `確認加入 ${company.name}`}
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    );
}
