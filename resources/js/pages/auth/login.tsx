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
import AuthSplitLayout from '@/layouts/auth/auth-split-layout';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { Form, Head } from '@inertiajs/react';
import {
    BellRing,
    Briefcase,
    CalendarClock,
    Check,
    ClipboardList,
    LogIn,
    ShieldCheck,
    Sparkles,
    TimerReset,
} from 'lucide-react';

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}

export default function Login({
    status,
    canResetPassword,
    canRegister,
}: LoginProps) {
    return (
        <AuthSplitLayout
            title="登入週報通 Timesheet SaaS"
            description="集中管理多用戶週報、工時與提醒。登入後即可掌握個人與團隊的最新進度。"
            aside={<LoginAside />}
        >
            <Head title="登入" />

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="group"
            >
                {({ processing, errors }) => (
                    <Card className="border border-border/70 bg-background/95 shadow-lg shadow-indigo-500/10 backdrop-blur transition group-hover:shadow-indigo-500/20 dark:border-border/40">
                        <CardHeader className="space-y-4">
                            <div className="flex items-center justify-between">
                                <Badge className="gap-2 rounded-full bg-indigo-500/15 px-3 py-1 text-xs font-semibold text-indigo-500 dark:bg-indigo-500/20 dark:text-indigo-200">
                                    <Sparkles className="size-3.5" />
                                    歡迎回來
                                </Badge>
                                <span className="text-xs font-medium text-muted-foreground">
                                    成員登入入口
                                </span>
                            </div>
                            <CardTitle className="text-2xl font-semibold">
                                登入用戶控制台
                            </CardTitle>
                            <CardDescription className="leading-relaxed">
                                集中管理週報、審核進度與提醒。登入後即可檢視個人與團隊的最新狀態。
                            </CardDescription>
                        </CardHeader>
                        <Separator className="mx-6" />
                        <CardContent className="space-y-5 pt-6">
                            <div className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="email">Email</Label>
                                    <Input
                                        id="email"
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
                                        <Label htmlFor="password">Password</Label>
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
                                        id="password"
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
                                data-test="login-button"
                            >
                                {processing && <Spinner />}
                                {!processing && <LogIn className="size-4" />}
                                開始使用
                            </Button>

                            <Separator className="my-6" />

                            <div className="grid gap-4 text-sm text-muted-foreground">
                                <div className="flex items-center gap-3">
                                    <ShieldCheck className="size-4 text-emerald-500" />
                                    <span>支援雙因素驗證與 IP 白名單，保障用戶資料安全。</span>
                                </div>
                                <div className="flex items-center gap-3">
                                    <CalendarClock className="size-4 text-sky-500" />
                                    <span>整合假日提醒與工時上限，避免提交錯誤。</span>
                                </div>
                            </div>

                            {canRegister && (
                                <div className="rounded-2xl border border-border/60 bg-muted/30 p-4 text-xs text-muted-foreground dark:bg-muted/10">
                                    <p className="flex items-center gap-2">
                                        <Check className="size-4 text-indigo-500" />
                                        還不是成員？{' '}
                                        <TextLink href={register()} tabIndex={5}>
                                            建立週報帳號
                                        </TextLink>
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}
            </Form>

            {status && (
                <div className="mt-4 text-center text-sm font-medium text-emerald-600">
                    {status}
                </div>
            )}
        </AuthSplitLayout>
    );
}

function LoginAside() {
    return (
        <div className="flex h-full flex-col justify-between space-y-12">
            <div className="space-y-8">
                <div className="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/5 px-4 py-1 text-xs font-semibold uppercase tracking-widest text白色/90 backdrop-blur">
                    <ClipboardList className="size-3.5" />
                    週報一次搞定
                </div>
                <div className="space-y-3">
                    <h2 className="text-3xl font-semibold leading-tight text-white">
                        追蹤週報進度，就在這裡
                    </h2>
                    <p className="max-w-md text-sm leading-relaxed text-white/80">
                        登入後即可檢視本週待辦、審核狀態與提醒。支援多用戶快速切換，無縫掌握跨團隊進度。
                    </p>
                </div>
                <dl className="grid gap-4 sm:grid-cols-3">
                    <div className="rounded-2xl border border-white/15 bg白色/10 p-4 backdrop-blur">
                        <dt className="text-xs uppercase tracking-wide text白色/60">
                            待審週報
                        </dt>
                        <dd className="mt-2 text-2xl font-semibold text白色">12</dd>
                    </div>
                    <div className="rounded-2xl border border白色/15 bg白色/10 p-4 backdrop-blur">
                        <dt className="text-xs uppercase tracking-wide text白色/60">
                            拖延週數
                        </dt>
                        <dd className="mt-2 text-2xl font-semibold text白色">-45%</dd>
                    </div>
                    <div className="rounded-2xl border border白色/15 bg白色/10 p-4 backdrop-blur">
                        <dt className="text-xs uppercase tracking-wide text白色/60">
                            平均提交時間
                        </dt>
                        <dd className="mt-2 text-2xl font-semibold text白色">2.8h</dd>
                    </div>
                </dl>
            </div>

            <div className="space-y-5 rounded-3xl border border白色/15 bg黑色/30 p-6 backdrop-blur">
                <h3 className="text-sm font-semibold uppercase tracking-wide text白色/80">
                    使用者最常做的事
                </h3>
                <ul className="space-y-4 text-sm text白色/75">
                    <li className="flex gap-3">
                        <Briefcase className="mt-1 size-4 text-sky-300" />
                        <div>
                            <p className="font-medium text白色">
                                管理跨團隊週報審核
                            </p>
                            <p className="text-xs text白色/65">
                                一鍵篩選事業群、部門、小組狀態，掌握誰已提交與待補。
                            </p>
                        </div>
                    </li>
                    <li className="flex gap-3">
                        <TimerReset className="mt-1 size-4 text-emerald-300" />
                        <div>
                            <p className="font-medium text白色">
                                追蹤站會與工時提醒
                            </p>
                            <p className="text-xs text白色/65">
                                自動同步公司行事曆，提醒成員補齊工時與假日調整。
                            </p>
                        </div>
                    </li>
                    <li className="flex gap-3">
                        <BellRing className="mt-1 size-4 text-purple-300" />
                        <div>
                            <p className="font-medium text白色">
                                收到審核／提醒通知
                            </p>
                            <p className="text-xs text白色/65">
                                Slack、Email、Webhook 即時更新，不再漏掉任何週報。
                            </p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    );
}
