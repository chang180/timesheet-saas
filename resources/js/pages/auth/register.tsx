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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import AuthSplitLayout from '@/layouts/auth/auth-split-layout';
import { login } from '@/routes';
import { store } from '@/routes/register';
import { Form, Head } from '@inertiajs/react';
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
} from 'lucide-react';

export default function Register() {
    return (
        <AuthSplitLayout
            title="建立週報工作簿"
            description="給團隊一個地方，隨手記下這週做了什麼、支援了誰、開了哪些會。週會或臨時提報時，自動整理的摘要立刻就緒。"
            aside={<RegisterAside />}
        >
            <Head title="註冊" />
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="group"
            >
                {({ processing, errors }) => (
                    <Card className="border border-border/70 bg-background/95 shadow-lg shadow-purple-500/10 backdrop-blur transition group-hover:shadow-purple-500/20 dark:border-border/40">
                        <CardHeader className="space-y-4">
                            <Badge className="w-fit gap-2 rounded-full bg-purple-500/15 px-3 py-1 text-xs font-semibold text-purple-500 dark:bg-purple-500/20 dark:text-purple-200">
                                <Sparkles className="size-3.5" />
                                5 分鐘整理好這週
                            </Badge>
                            <CardTitle className="text-2xl font-semibold">
                                建立週報工作簿
                            </CardTitle>
                            <CardDescription className="leading-relaxed">
                                不論是 Jira 任務、幫忙教新人或臨時開會，都可以在這裡記錄。下次週會、主管想看某人的一週重點時，隨時叫得出來。
                            </CardDescription>
                        </CardHeader>
                        <Separator className="mx-6" />
                        <CardContent className="space-y-5 pt-6">
                            <GoogleAuthButton intent="register" />

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
                                    <Label htmlFor="company_name">公司或團隊名稱</Label>
                                    <Input
                                        id="company_name"
                                        type="text"
                                        required
                                        tabIndex={1}
                                        autoComplete="organization"
                                        name="company_name"
                                        placeholder="例如：Acme 研發部"
                                    />
                                    <InputError message={errors.company_name} className="mt-2" />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="name">姓名</Label>
                                    <Input
                                        id="name"
                                        type="text"
                                        required
                                        autoFocus
                                        tabIndex={2}
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
                                        tabIndex={3}
                                        autoComplete="email"
                                        name="email"
                                        placeholder="name@company.com"
                                    />
                                    <InputError message={errors.email} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="password">密碼</Label>
                                    <Input
                                        id="password"
                                        type="password"
                                        required
                                        tabIndex={4}
                                        autoComplete="new-password"
                                        name="password"
                                        placeholder="至少 8 碼，包含英數字"
                                    />
                                    <InputError message={errors.password} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="password_confirmation">
                                        確認密碼
                                    </Label>
                                    <Input
                                        id="password_confirmation"
                                        type="password"
                                        required
                                        tabIndex={5}
                                        autoComplete="new-password"
                                        name="password_confirmation"
                                        placeholder="再次輸入密碼"
                                    />
                                    <InputError
                                        message={errors.password_confirmation}
                                    />
                                </div>
                            </div>

                            <Button
                                type="submit"
                                className="mt-2 w-full gap-2"
                                tabIndex={6}
                                disabled={processing}
                                data-test="register-user-button"
                            >
                                {processing && <Spinner />}
                                {!processing && <UserPlus className="size-4" />}
                                建立帳號
                                {!processing && <ArrowRight className="ml-1 size-4" />}
                            </Button>

                            <Separator className="my-6" />

                            <div className="grid gap-4 text-sm text-muted-foreground">
                                <div className="flex items-center gap-3">
                                    <Building2 className="size-4 text-indigo-500" />
                                    <span>依公司／部門／小組分層管理週報，週會時快速切換想看的單位。</span>
                                </div>
                                <div className="flex items-center gap-3">
                                    <Users className="size-4 text-sky-500" />
                                    <span>成員可自由補充臨時支援、教學、會議紀錄，不只限於專案任務。</span>
                                </div>
                                <div className="flex items-center gap-3">
                                    <MailPlus className="size-4 text-emerald-500" />
                                    <span>會議前自動提醒，主管與 PM 能提早閱讀本週焦點。</span>
                                </div>
                            </div>

                            <div className="rounded-2xl border border-border/60 bg-muted/30 p-4 text-xs text-muted-foreground dark:bg-muted/10">
                                <p className="flex items-center gap-2">
                                    <BadgeCheck className="size-4 text-purple-500" />
                                    註冊後立即取得週報摘要連結、Slack / Email 通知與下載報表。
                                </p>
                            </div>

                            <div className="text-center text-sm text-muted-foreground">
                                已經有帳號了嗎？{' '}
                                <TextLink href={login()} tabIndex={7}>
                                    直接登入
                                </TextLink>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </Form>
        </AuthSplitLayout>
    );
}

function RegisterAside() {
    return (
        <div className="flex h-full flex-col justify-between space-y-12">
            <div className="space-y-8">
                <div className="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/5 px-4 py-1 text-xs font-semibold uppercase tracking-widest text-white/90 backdrop-blur">
                    <Shield className="size-3.5" />
                    週會筆記最佳夥伴
                </div>
                <div className="space-y-3">
                    <h2 className="text-3xl font-semibold leading-tight text-white">
                        快速寫下「這週做了什麼」
                    </h2>
                    <p className="max-w-md text-sm leading-relaxed text-white/80">
                        不只紀錄 Jira / Redmine 任務，也把臨時支援、教新人、跨部門協作等內容寫進來。開週會或臨時提報時，即時拉出重點。
                    </p>
                </div>
                <dl className="grid gap-4 sm:grid-cols-3">
                    <div className="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">
                        <dt className="text-xs uppercase tracking-wide text-white/60">
                            每週整理時間
                        </dt>
                        <dd className="mt-2 text-2xl font-semibold text-white">5 分</dd>
                    </div>
                    <div className="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">
                        <dt className="text-xs uppercase tracking-wide text-white/60">
                            週會準備效率
                        </dt>
                        <dd className="mt-2 text-2xl font-semibold text-white">＋65%</dd>
                    </div>
                    <div className="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">
                        <dt className="text-xs uppercase tracking-wide text-white/60">
                            臨時支援被看見
                        </dt>
                        <dd className="mt-2 text-2xl font-semibold text-white">3×</dd>
                    </div>
                </dl>
            </div>

            <div className="space-y-5 rounded-3xl border border-white/15 bg-black/30 p-6 backdrop-blur">
                <h3 className="text-sm font-semibold uppercase tracking-wide text-white/80">
                    註冊後你可以
                </h3>
                <ul className="space-y-4 text-sm text-white/75">
                    <li className="flex gap-3">
                        <GitBranch className="mt-1 size-4 text-sky-300" />
                        <div>
                            <p className="font-medium text-white">打造本週重點模板</p>
                            <p className="text-xs text-white/65">
                                Done / Support / Meeting / Risk 等欄位固定呈現，週會照著筆記就能講完。
                            </p>
                        </div>
                    </li>
                    <li className="flex gap-3">
                        <CalendarClock className="mt-1 size-4 text-emerald-300" />
                        <div>
                            <p className="font-medium text-white">週會前提醒成員更新</p>
                            <p className="text-xs text-white/65">
                                系統會透過行事曆或通知提前提醒，避免臨時補寫。
                            </p>
                        </div>
                    </li>
                    <li className="flex gap-3">
                        <BellRing className="mt-1 size-4 text-purple-300" />
                        <div>
                            <p className="font-medium text-white">保留手動輸入的彈性</p>
                            <p className="text-xs text-white/65">
                                可匯入專案任務，也能自由記錄教新人、臨時支援、跨部門協作等內容。
                            </p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    );
}
