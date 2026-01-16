import GoogleAuthButton from '@/components/auth/google-auth-button';
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
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
// 使用簡單的按鈕切換，不使用 Tabs 組件
import AuthSplitLayout from '@/layouts/auth/auth-split-layout';
import { store as loginStore } from '@/routes/login';
import { store as registerStore } from '@/routes/register';
import { request } from '@/routes/password';
import { Form, Head } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowRight,
    BellRing,
    CalendarClock,
    GitBranch,
    LogIn,
    Shield,
    Sparkles,
    Users,
} from 'lucide-react';
import { useState } from 'react';

interface TenantAuthProps {
    company: {
        id: number;
        name: string;
        slug: string;
    };
    canRegister: boolean;
    userLimit: number;
    currentUserCount: number;
    canResetPassword: boolean;
    status?: string;
}

export default function TenantAuth({
    company,
    canRegister,
    userLimit,
    currentUserCount,
    canResetPassword,
    status,
}: TenantAuthProps) {
    const [activeTab, setActiveTab] = useState<'login' | 'register'>('login');

    return (
        <AuthSplitLayout
            title={`${company.name} 登入/註冊`}
            description="使用此專屬網址登入或註冊，僅限 {company.name} 的成員使用。"
            aside={<AuthAside />}
        >
            <Head title={`${company.name} - 登入/註冊`} />

            {!canRegister && activeTab === 'register' ? (
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
                            onClick={() => setActiveTab('login')}
                            className="w-full"
                        >
                            返回登入
                        </Button>
                    </CardContent>
                </Card>
            ) : (
                <div className="w-full">
                    <div className="mb-6 flex gap-2 rounded-lg border border-border/70 bg-muted/30 p-1">
                        <button
                            type="button"
                            onClick={() => setActiveTab('login')}
                            className={`flex-1 rounded-md px-4 py-2 text-sm font-medium transition-colors ${
                                activeTab === 'login'
                                    ? 'bg-background text-foreground shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground'
                            }`}
                        >
                            登入
                        </button>
                        <button
                            type="button"
                            onClick={() => setActiveTab('register')}
                            disabled={!canRegister}
                            className={`flex-1 rounded-md px-4 py-2 text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed ${
                                activeTab === 'register'
                                    ? 'bg-background text-foreground shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground'
                            }`}
                        >
                            註冊
                        </button>
                    </div>

                    {activeTab === 'login' && (
                        <div className="mt-6">
                        <Form
                            {...loginStore.form()}
                            resetOnSuccess={['password']}
                            className="group"
                        >
                            {({ processing, errors }) => (
                                <Card className="border border-border/70 bg-background/95 shadow-lg shadow-indigo-500/10 backdrop-blur transition group-hover:shadow-indigo-500/20 dark:border-border/40">
                                    <CardHeader className="space-y-4">
                                        <Badge className="w-fit gap-2 rounded-full bg-indigo-500/15 px-3 py-1 text-xs font-semibold text-indigo-500 dark:bg-indigo-500/20 dark:text-indigo-200">
                                            <Sparkles className="size-3.5" />
                                            {company.name} 成員登入
                                        </Badge>
                                        <CardTitle className="text-2xl font-semibold">
                                            登入帳號
                                        </CardTitle>
                                        <CardDescription className="leading-relaxed">
                                            僅限 {company.name} 的成員使用此頁面登入。其他公司的成員將無法登入。
                                        </CardDescription>
                                    </CardHeader>
                                    <Separator className="mx-6" />
                                    <CardContent className="space-y-5 pt-6">
                                        <input
                                            type="hidden"
                                            name="company_slug"
                                            value={company.slug}
                                        />
                                        <GoogleAuthButton
                                            intent="login"
                                            companySlug={company.slug}
                                        />

                                        <div className="relative">
                                            <div className="absolute inset-0 flex items-center">
                                                <Separator />
                                            </div>
                                            <div className="relative flex justify-center text-xs uppercase">
                                                <span className="bg-background px-2 text-muted-foreground">
                                                    或
                                                </span>
                                            </div>
                                        </div>

                                        <div className="space-y-4">
                                            <div className="space-y-2">
                                                <Label htmlFor="login-email">Email</Label>
                                                <Input
                                                    id="login-email"
                                                    type="email"
                                                    name="email"
                                                    required
                                                    autoFocus
                                                    tabIndex={1}
                                                    autoComplete="email"
                                                    placeholder="name@company.com"
                                                />
                                                <InputError message={errors.email} />
                                            </div>

                                            <div className="space-y-2">
                                                <div className="flex items-center">
                                                    <Label htmlFor="login-password">密碼</Label>
                                                    {canResetPassword && (
                                                        <TextLink
                                                            href={request()}
                                                            className="ml-auto text-sm"
                                                            tabIndex={5}
                                                        >
                                                            忘記密碼？
                                                        </TextLink>
                                                    )}
                                                </div>
                                                <Input
                                                    id="login-password"
                                                    type="password"
                                                    name="password"
                                                    required
                                                    tabIndex={2}
                                                    autoComplete="current-password"
                                                    placeholder="輸入密碼"
                                                />
                                                <InputError message={errors.password} />
                                            </div>

                                            <div className="flex items-center gap-3">
                                                <Checkbox
                                                    id="remember"
                                                    name="remember"
                                                    tabIndex={3}
                                                />
                                                <Label htmlFor="remember">保持登入狀態</Label>
                                            </div>
                                        </div>

                                        <Button
                                            type="submit"
                                            className="mt-2 w-full gap-2"
                                            tabIndex={4}
                                            disabled={processing}
                                        >
                                            {processing && <Spinner />}
                                            {!processing && <LogIn className="size-4" />}
                                            登入
                                        </Button>
                                    </CardContent>
                                </Card>
                            )}
                        </Form>
                        </div>
                    )}

                    {activeTab === 'register' && (
                        <div className="mt-6">
                        {canRegister ? (
                            <Form
                                {...registerStore.form()}
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
                                            <GoogleAuthButton
                                                intent="register"
                                                companySlug={company.slug}
                                            />

                                            <div className="relative">
                                                <div className="absolute inset-0 flex items-center">
                                                    <Separator />
                                                </div>
                                                <div className="relative flex justify-center text-xs uppercase">
                                                    <span className="bg-background px-2 text-muted-foreground">
                                                        或
                                                    </span>
                                                </div>
                                            </div>

                                            <div className="space-y-4">
                                                <div className="space-y-2">
                                                    <Label htmlFor="register-name">姓名</Label>
                                                    <Input
                                                        id="register-name"
                                                        type="text"
                                                        required
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
                                                    <Label htmlFor="register-email">Email</Label>
                                                    <Input
                                                        id="register-email"
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
                                                    <Label htmlFor="register-password">密碼</Label>
                                                    <Input
                                                        id="register-password"
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
                                                    <Label htmlFor="register-password-confirmation">
                                                        確認密碼
                                                    </Label>
                                                    <Input
                                                        id="register-password-confirmation"
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
                                                <TextLink
                                                    href="#"
                                                    onClick={(e) => {
                                                        e.preventDefault();
                                                        setActiveTab('login');
                                                    }}
                                                >
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
                        ) : null}
                        </div>
                    )}
                </div>
            )}

            {status && (
                <div className="mt-4 text-center text-sm font-medium text-emerald-600">
                    {status}
                </div>
            )}
        </AuthSplitLayout>
    );
}

function AuthAside() {
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
