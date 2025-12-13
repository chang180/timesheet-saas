import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import AuthSplitLayout from '@/layouts/auth/auth-split-layout';
import { login } from '@/routes';
import { store } from '@/routes/register';
import { Form, Head, router } from '@inertiajs/react';
import {
    ArrowRight,
    BadgeCheck,
    BellRing,
    Building2,
    CalendarClock,
    GitBranch,
    MailPlus,
    Shield,
    Sparkles,
    UserPlus,
    Users,
    AlertCircle,
} from 'lucide-react';

interface TenantRegisterProps {
    company: {
        id: number;
        name: string;
        slug: string;
    };
    canRegister: boolean;
    userLimit: number;
    currentUserCount: number;
}

export default function TenantRegister({
    company,
    canRegister,
    userLimit,
    currentUserCount,
}: TenantRegisterProps) {
    const loginUrl = login();

    return (
        <AuthSplitLayout
            title={`加入 ${company.name}`}
            description="填寫以下資訊即可加入團隊，開始使用週報系統。"
            aside={<RegisterAside />}
        >
            <Head title={`加入 ${company.name}`} />
            {!canRegister ? (
                <Card className="border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-500/10">
                    <CardHeader>
                        <div className="flex items-center gap-2">
                            <AlertCircle className="h-5 w-5 text-amber-600 dark:text-amber-400" />
                            <CardTitle className="text-2xl font-semibold text-amber-800 dark:text-amber-400">
                                無法註冊
                            </CardTitle>
                        </div>
                        <CardDescription className="text-amber-700 dark:text-amber-300">
                            此公司的成員數已達上限（{currentUserCount}/{userLimit}），目前無法接受新成員註冊。
                            請聯絡公司管理者以獲得邀請。
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Button
                            variant="outline"
                            onClick={() => router.visit(loginUrl)}
                            className="w-full"
                        >
                            返回登入頁面
                        </Button>
                    </CardContent>
                </Card>
            ) : (
                <Form
                    {...store.form()}
                    resetOnSuccess={['password', 'password_confirmation']}
                    className="group"
                >
                    {({ processing, errors }) => (
                        <Card className="border border-border/70 bg-background/95 shadow-lg shadow-purple-500/10 backdrop-blur transition group-hover:shadow-purple-500/20 dark:border-border/40">
                            <CardHeader className="space-y-4">
                                <Badge className="w-fit gap-2 rounded-full bg-purple-500/15 px-3 py-1 text-xs font-semibold text-purple-500 dark:bg-purple-500/20 dark:text-purple-200">
                                    <Sparkles className="size-3.5" />
                                    加入 {company.name}
                                </Badge>
                                <CardTitle className="text-2xl font-semibold">
                                    註冊帳號
                                </CardTitle>
                                <CardDescription className="leading-relaxed">
                                    填寫以下資訊即可加入 {company.name} 團隊，開始使用週報系統。
                                </CardDescription>
                            </CardHeader>
                            <Separator className="mx-6" />
                            <CardContent className="space-y-5 pt-6">
                                <input
                                    type="hidden"
                                    name="company_slug"
                                    value={company.slug}
                                />
                                <div className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="name">姓名</Label>
                                        <Input
                                            id="name"
                                            type="text"
                                            required
                                            autoFocus
                                            tabIndex={1}
                                            autoComplete="name"
                                            name="name"
                                            placeholder="請輸入姓名"
                                        />
                                        <InputError
                                            message={errors.name}
                                            className="mt-2"
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="email">Email</Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            required
                                            tabIndex={2}
                                            autoComplete="email"
                                            name="email"
                                            placeholder="your@email.com"
                                        />
                                        <InputError
                                            message={errors.email}
                                            className="mt-2"
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="password">密碼</Label>
                                        <Input
                                            id="password"
                                            type="password"
                                            required
                                            tabIndex={3}
                                            autoComplete="new-password"
                                            name="password"
                                            placeholder="至少 8 個字元"
                                        />
                                        <InputError
                                            message={errors.password}
                                            className="mt-2"
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="password_confirmation">
                                            確認密碼
                                        </Label>
                                        <Input
                                            id="password_confirmation"
                                            type="password"
                                            required
                                            tabIndex={4}
                                            autoComplete="new-password"
                                            name="password_confirmation"
                                            placeholder="再次輸入密碼"
                                        />
                                        <InputError
                                            message={errors.password_confirmation}
                                            className="mt-2"
                                        />
                                    </div>
                                </div>

                                <Separator className="my-6" />

                                <div className="flex items-center justify-between">
                                    <TextLink href={loginUrl}>
                                        已有帳號？立即登入
                                    </TextLink>

                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="group/button"
                                    >
                                        {processing ? (
                                            <>
                                                <Spinner className="mr-2 h-4 w-4" />
                                                註冊中...
                                            </>
                                        ) : (
                                            <>
                                                註冊
                                                <ArrowRight className="ml-2 h-4 w-4 transition-transform group-hover/button:translate-x-1" />
                                            </>
                                        )}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </Form>
            )}
        </AuthSplitLayout>
    );
}

function RegisterAside() {
    return (
        <div className="space-y-8">
            <div className="space-y-4">
                <h2 className="text-2xl font-bold tracking-tight">
                    為什麼選擇週報通？
                </h2>
                <p className="text-muted-foreground leading-relaxed">
                    不論是 Jira 任務、幫忙教新人或臨時開會，都可以在這裡記錄。下次週會、主管想看某人的一週重點時，隨時叫得出來。
                </p>
            </div>

            <div className="space-y-6">
                <div className="flex items-start gap-4">
                    <div className="rounded-lg bg-primary/10 p-3">
                        <CalendarClock className="h-5 w-5 text-primary" />
                    </div>
                    <div className="space-y-1">
                        <h3 className="font-semibold">每週自動整理</h3>
                        <p className="text-sm text-muted-foreground">
                            系統會自動整理您本週的工作內容，方便主管檢視與匯總。
                        </p>
                    </div>
                </div>

                <div className="flex items-start gap-4">
                    <div className="rounded-lg bg-primary/10 p-3">
                        <GitBranch className="h-5 w-5 text-primary" />
                    </div>
                    <div className="space-y-1">
                        <h3 className="font-semibold">Redmine/Jira 連動</h3>
                        <p className="text-sm text-muted-foreground">
                            自動帶入任務與工時，減少重複輸入的時間。
                        </p>
                    </div>
                </div>

                <div className="flex items-start gap-4">
                    <div className="rounded-lg bg-primary/10 p-3">
                        <Users className="h-5 w-5 text-primary" />
                    </div>
                    <div className="space-y-1">
                        <h3 className="font-semibold">團隊協作</h3>
                        <p className="text-sm text-muted-foreground">
                            主管可即時查看團隊成員的工作進度，方便管理與協調。
                        </p>
                    </div>
                </div>

                <div className="flex items-start gap-4">
                    <div className="rounded-lg bg-primary/10 p-3">
                        <BellRing className="h-5 w-5 text-primary" />
                    </div>
                    <div className="space-y-1">
                        <h3 className="font-semibold">自動提醒</h3>
                        <p className="text-sm text-muted-foreground">
                            系統會在適當時間提醒您填寫週報，不會錯過任何重要時機。
                        </p>
                    </div>
                </div>

                <div className="flex items-start gap-4">
                    <div className="rounded-lg bg-primary/10 p-3">
                        <Shield className="h-5 w-5 text-primary" />
                    </div>
                    <div className="space-y-1">
                        <h3 className="font-semibold">安全可靠</h3>
                        <p className="text-sm text-muted-foreground">
                            資料加密儲存，多層級權限管理，確保資料安全。
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}
