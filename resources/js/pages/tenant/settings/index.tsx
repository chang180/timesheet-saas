import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import settingsRoutes from '@/routes/tenant/settings';
import { AnnouncementsModule } from '@/tenant/welcome-modules/announcements-module';
import { HeroModule } from '@/tenant/welcome-modules/hero-module';
import { QuickStartStepsModule } from '@/tenant/welcome-modules/quick-start-steps-module';
import { SupportContactsModule } from '@/tenant/welcome-modules/support-contacts-module';
import { WeeklyReportDemoModule } from '@/tenant/welcome-modules/weekly-report-demo-module';
import { cn } from '@/lib/utils';
import { Head, useForm } from '@inertiajs/react';
import { Fragment, useMemo } from 'react';

type WelcomeFormData = {
    hero: {
        enabled: boolean;
        title: string;
        subtitle?: string;
        backgroundImage?: string | null;
        videoUrl?: string | null;
    };
    quickStartSteps: {
        enabled: boolean;
        steps: Array<{ title: string; description: string }>;
    };
    weeklyReportDemo: {
        enabled: boolean;
        highlights: string[];
    };
    announcements: {
        enabled: boolean;
        items: Array<{ title: string; content: string; publishedAt?: string }>;
    };
    supportContacts: {
        enabled: boolean;
        contacts: Array<{ name: string; email?: string; phone?: string }>;
    };
    ctas: Array<{ text: string; url: string; variant?: 'primary' | 'secondary' }>;
};

type TenantSettingsPayload = {
    companyName: string;
    companySlug: string;
    brandColor?: string;
    logo?: string;
    welcomePage: Partial<WelcomeFormData>;
    ipWhitelist: string[];
};

interface PageProps {
    settings: TenantSettingsPayload;
}

const MAX_STEPS = 5;
const MAX_CTA = 3;
const MAX_SUPPORT_CONTACTS = 3;
const MAX_ANNOUNCEMENTS = 3;

export default function TenantSettingsPage({ settings }: PageProps) {
    return (
        <AppLayout
            breadcrumbs={[
                {
                    title: '用戶設定',
                    href: '#',
                },
            ]}
        >
            <Head title="用戶設定" />
            <div className="space-y-12 px-4 py-8 lg:px-0">
                <TenantWelcomeConfigurator
                    companyName={settings.companyName}
                    companySlug={settings.companySlug}
                    brandColor={settings.brandColor}
                    initialConfig={settings.welcomePage}
                />

                <IPWhitelistForm
                    initialAddresses={settings.ipWhitelist}
                    companySlug={settings.companySlug}
                />
            </div>
        </AppLayout>
    );
}

interface TenantWelcomeConfiguratorProps {
    companyName: string;
    companySlug: string;
    brandColor?: string;
    initialConfig: Partial<WelcomeFormData>;
}

function TenantWelcomeConfigurator({
    companyName,
    companySlug,
    brandColor,
    initialConfig,
}: TenantWelcomeConfiguratorProps) {
    const form = useForm<WelcomeFormData>({
        hero: {
            enabled: initialConfig.hero?.enabled ?? true,
            title:
                initialConfig.hero?.title ??
                `歡迎 ${companyName} 團隊`,
            subtitle:
                initialConfig.hero?.subtitle ??
                '快速建立週報流程，掌握團隊進度。',
            backgroundImage: initialConfig.hero?.backgroundImage ?? null,
            videoUrl: initialConfig.hero?.videoUrl ?? null,
        },
        quickStartSteps: {
            enabled: initialConfig.quickStartSteps?.enabled ?? true,
            steps: initialConfig.quickStartSteps?.steps?.length
                ? initialConfig.quickStartSteps.steps.slice(0, MAX_STEPS)
                : [
                      {
                          title: '登入系統',
                          description: '使用公司信箱登入用戶後台。',
                      },
                  ],
        },
        weeklyReportDemo: {
            enabled: initialConfig.weeklyReportDemo?.enabled ?? true,
            highlights:
                initialConfig.weeklyReportDemo?.highlights?.length
                    ? initialConfig.weeklyReportDemo.highlights.slice(0, 4)
                    : [
                          '拖曳排序同步更新主管檢視順序',
                          'Redmine/Jira 連動，自動帶入任務與工時',
                      ],
        },
        announcements: {
            enabled: initialConfig.announcements?.enabled ?? false,
            items: initialConfig.announcements?.items?.slice(
                0,
                MAX_ANNOUNCEMENTS,
            ) ?? [],
        },
        supportContacts: {
            enabled: initialConfig.supportContacts?.enabled ?? false,
            contacts: initialConfig.supportContacts?.contacts?.slice(
                0,
                MAX_SUPPORT_CONTACTS,
            ) ?? [],
        },
        ctas: initialConfig.ctas?.slice(0, MAX_CTA) ?? [],
    });

    const accentColor = brandColor ?? '#2563eb';
    const { data, errors, processing, recentlySuccessful } = form;

    const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        form.transform((formData) => ({
            ...formData,
            hero: {
                ...formData.hero,
                backgroundImage:
                    formData.hero.backgroundImage?.trim() || null,
                videoUrl: formData.hero.videoUrl?.trim() || null,
            },
            quickStartSteps: formData.quickStartSteps.enabled
                ? {
                      ...formData.quickStartSteps,
                      steps: formData.quickStartSteps.steps
                          .slice(0, MAX_STEPS)
                          .map((step) => ({
                              title: step.title.trim(),
                              description: step.description.trim(),
                          }))
                          .filter((step) => step.title),
                  }
                : {
                      enabled: false,
                      steps: [],
                  },
            weeklyReportDemo: formData.weeklyReportDemo.enabled
                ? {
                      enabled: true,
                      highlights: formData.weeklyReportDemo.highlights
                          .map((highlight) => highlight.trim())
                          .filter(Boolean)
                          .slice(0, 4),
                  }
                : {
                      enabled: false,
                      highlights: [],
                  },
            announcements: formData.announcements.enabled
                ? {
                      enabled: true,
                      items: formData.announcements.items
                          .slice(0, MAX_ANNOUNCEMENTS)
                          .map((item) => ({
                              ...item,
                              title: item.title.trim(),
                              content: item.content.trim(),
                          }))
                          .filter((item) => item.title && item.content),
                  }
                : { enabled: false, items: [] },
            supportContacts: formData.supportContacts.enabled
                ? {
                      enabled: true,
                      contacts: formData.supportContacts.contacts
                          .slice(0, MAX_SUPPORT_CONTACTS)
                          .map((contact) => ({
                              name: contact.name.trim(),
                              email: contact.email?.trim() || null,
                              phone: contact.phone?.trim() || null,
                          }))
                          .filter((contact) => contact.name),
                  }
                : { enabled: false, contacts: [] },
            ctas: formData.ctas
                .slice(0, MAX_CTA)
                .map((cta) => ({
                    ...cta,
                    text: cta.text.trim(),
                    url: cta.url.trim(),
                }))
                .filter((cta) => cta.text && cta.url),
        }));

        form.patch(settingsRoutes.welcomePage.url({ company: companySlug }), {
            preserveScroll: true,
        });
    };

    const previewConfig = useMemo(() => {
        return {
            hero: data.hero.enabled ? data.hero : null,
            quickStartSteps: data.quickStartSteps.enabled
                ? data.quickStartSteps
                : null,
            weeklyReportDemo: data.weeklyReportDemo.enabled
                ? data.weeklyReportDemo
                : null,
            announcements: data.announcements.enabled
                ? data.announcements
                : null,
            supportContacts: data.supportContacts.enabled
                ? data.supportContacts
                : null,
            ctas: data.ctas,
        };
    }, [data]);

    const addStep = () => {
        if (data.quickStartSteps.steps.length >= MAX_STEPS) {
            return;
        }

        form.setData('quickStartSteps', {
            ...data.quickStartSteps,
            steps: [
                ...data.quickStartSteps.steps,
                { title: '', description: '' },
            ],
        });
    };

    const updateStep = (
        index: number,
        key: 'title' | 'description',
        value: string,
    ) => {
        const steps = data.quickStartSteps.steps.map((step, idx) =>
            idx === index ? { ...step, [key]: value } : step,
        );
        form.setData('quickStartSteps', {
            ...data.quickStartSteps,
            steps,
        });
    };

    const removeStep = (index: number) => {
        form.setData('quickStartSteps', {
            ...data.quickStartSteps,
            steps: data.quickStartSteps.steps.filter(
                (_, idx) => idx !== index,
            ),
        });
    };

    const addHighlight = () => {
        if (data.weeklyReportDemo.highlights.length >= 4) {
            return;
        }
        form.setData('weeklyReportDemo', {
            ...data.weeklyReportDemo,
            highlights: [...data.weeklyReportDemo.highlights, ''],
        });
    };

    const updateHighlight = (index: number, value: string) => {
        const highlights = data.weeklyReportDemo.highlights.map(
            (item, idx) => (idx === index ? value : item),
        );
        form.setData('weeklyReportDemo', {
            ...data.weeklyReportDemo,
            highlights,
        });
    };

    const removeHighlight = (index: number) => {
        form.setData('weeklyReportDemo', {
            ...data.weeklyReportDemo,
            highlights: data.weeklyReportDemo.highlights.filter(
                (_, idx) => idx !== index,
            ),
        });
    };

    const addAnnouncement = () => {
        if (data.announcements.items.length >= MAX_ANNOUNCEMENTS) {
            return;
        }

        form.setData('announcements', {
            ...data.announcements,
            items: [
                ...data.announcements.items,
                { title: '', content: '', publishedAt: '' },
            ],
        });
    };

    const updateAnnouncement = (
        index: number,
        key: 'title' | 'content' | 'publishedAt',
        value: string,
    ) => {
        const items = data.announcements.items.map((item, idx) =>
            idx === index ? { ...item, [key]: value } : item,
        );
        form.setData('announcements', {
            ...data.announcements,
            items,
        });
    };

    const removeAnnouncement = (index: number) => {
        form.setData('announcements', {
            ...data.announcements,
            items: data.announcements.items.filter(
                (_, idx) => idx !== index,
            ),
        });
    };

    const addSupportContact = () => {
        if (data.supportContacts.contacts.length >= MAX_SUPPORT_CONTACTS) {
            return;
        }

        form.setData('supportContacts', {
            ...data.supportContacts,
            contacts: [
                ...data.supportContacts.contacts,
                { name: '', email: '', phone: '' },
            ],
        });
    };

    const updateSupportContact = (
        index: number,
        key: 'name' | 'email' | 'phone',
        value: string,
    ) => {
        const contacts = data.supportContacts.contacts.map(
            (contact, idx) =>
                idx === index ? { ...contact, [key]: value } : contact,
        );
        form.setData('supportContacts', {
            ...data.supportContacts,
            contacts,
        });
    };

    const removeSupportContact = (index: number) => {
        form.setData('supportContacts', {
            ...data.supportContacts,
            contacts: data.supportContacts.contacts.filter(
                (_, idx) => idx !== index,
            ),
        });
    };

    const addCta = () => {
        if (data.ctas.length >= MAX_CTA) {
            return;
        }

        form.setData('ctas', [
            ...data.ctas,
            { text: '', url: '', variant: 'primary' },
        ]);
    };

    const updateCta = (
        index: number,
        key: 'text' | 'url' | 'variant',
        value: string,
    ) => {
        const ctas = data.ctas.map((cta, idx) =>
            idx === index ? { ...cta, [key]: value } : cta,
        );
        form.setData('ctas', ctas);
    };

    const removeCta = (index: number) => {
        form.setData(
            'ctas',
            data.ctas.filter((_, idx) => idx !== index),
        );
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-2xl font-semibold">
                    歡迎頁設定
                </CardTitle>
                <p className="text-sm text-muted-foreground">
                    編輯用戶歡迎頁內容，右側即時預覽顯示成員看到的畫面。
                </p>
            </CardHeader>
            <CardContent>
                <div className="grid gap-10 xl:grid-cols-[minmax(0,0.55fr)_minmax(0,0.45fr)]">
                    <form
                        className="space-y-10"
                        onSubmit={handleSubmit}
                        noValidate
                    >
                        <section className="space-y-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h3 className="text-lg font-semibold text-foreground">
                                        主視覺模組
                                    </h3>
                                    <p className="text-sm text-muted-foreground">
                                        控制主視覺區塊與行動呼籲按鈕。
                                    </p>
                                </div>
                                <Checkbox
                                    checked={data.hero.enabled}
                                    onCheckedChange={(checked) =>
                                        form.setData('hero', {
                                            ...data.hero,
                                            enabled: Boolean(checked),
                                        })
                                    }
                                    aria-label="啟用主視覺模組"
                                />
                            </div>

                            {data.hero.enabled && (
                                <div className="space-y-4 rounded-lg border border-border p-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="hero-title">
                                            標題
                                        </Label>
                                        <Input
                                            id="hero-title"
                                            value={data.hero.title}
                                            onChange={(event) =>
                                                form.setData('hero', {
                                                    ...data.hero,
                                                    title: event.target.value,
                                                })
                                            }
                                            placeholder="歡迎訊息"
                                        />
                                        <InputError
                                            message={errors['hero.title']}
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="hero-subtitle">
                                            副標題
                                        </Label>
                                        <Input
                                            id="hero-subtitle"
                                            value={data.hero.subtitle ?? ''}
                                            onChange={(event) =>
                                                form.setData('hero', {
                                                    ...data.hero,
                                                    subtitle:
                                                        event.target.value,
                                                })
                                            }
                                            placeholder="簡短描述"
                                        />
                                        <InputError
                                            message={errors['hero.subtitle']}
                                        />
                                    </div>

                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label htmlFor="hero-background">
                                                背景圖片 URL
                                            </Label>
                                            <Input
                                                id="hero-background"
                                                value={
                                                    data.hero
                                                        .backgroundImage ?? ''
                                                }
                                                onChange={(event) =>
                                                    form.setData('hero', {
                                                        ...data.hero,
                                                        backgroundImage:
                                                            event.target.value,
                                                    })
                                                }
                                                placeholder="https://..."
                                            />
                                            <InputError
                                                message={
                                                    errors[
                                                        'hero.backgroundImage'
                                                    ]
                                                }
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="hero-video">
                                                介紹影片 URL
                                            </Label>
                                            <Input
                                                id="hero-video"
                                                value={data.hero.videoUrl ?? ''}
                                                onChange={(event) =>
                                                    form.setData('hero', {
                                                        ...data.hero,
                                                        videoUrl:
                                                            event.target.value,
                                                    })
                                                }
                                                placeholder="https://www.youtube.com/embed/..."
                                            />
                                            <InputError
                                                message={
                                                    errors['hero.videoUrl']
                                                }
                                            />
                                        </div>
                                    </div>

                                    <div className="space-y-3">
                                        <div className="flex items-center justify-between">
                                            <Label className="text-sm font-medium">
                                                行動呼籲（CTA）最多 3 個
                                            </Label>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={addCta}
                                                disabled={
                                                    data.ctas.length >= MAX_CTA
                                                }
                                            >
                                                新增 CTA
                                            </Button>
                                        </div>
                                        <div className="space-y-4">
                                            {data.ctas.map((cta, index) => (
                                                <Fragment key={`cta-${index}`}>
                                                    <div className="grid gap-3 rounded-lg border border-border p-3 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_120px_auto] md:items-end md:gap-4">
                                                        <div className="md:space-y-1.5">
                                                            <Label>
                                                                文案
                                                            </Label>
                                                            <Input
                                                                value={
                                                                    cta.text
                                                                }
                                                                onChange={(
                                                                    event,
                                                                ) =>
                                                                    updateCta(
                                                                        index,
                                                                        'text',
                                                                        event
                                                                            .target
                                                                            .value,
                                                                    )
                                                                }
                                                                placeholder="立即登入"
                                                            />
                                                            <InputError
                                                                message={
                                                                    errors[
                                                                        `ctas.${index}.text`
                                                                    ]
                                                                }
                                                            />
                                                        </div>
                                                        <div className="md:space-y-1.5">
                                                            <Label>
                                                                連結
                                                            </Label>
                                                            <Input
                                                                value={
                                                                    cta.url
                                                                }
                                                                onChange={(
                                                                    event,
                                                                ) =>
                                                                    updateCta(
                                                                        index,
                                                                        'url',
                                                                        event
                                                                            .target
                                                                            .value,
                                                                    )
                                                                }
                                                                placeholder="https://"
                                                            />
                                                            <InputError
                                                                message={
                                                                    errors[
                                                                        `ctas.${index}.url`
                                                                    ]
                                                                }
                                                            />
                                                        </div>
                                                        <div className="md:space-y-1.5">
                                                            <Label>
                                                                樣式
                                                            </Label>
                                                            <select
                                                                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                                                value={
                                                                    cta.variant ??
                                                                    'primary'
                                                                }
                                                                onChange={(
                                                                    event,
                                                                ) =>
                                                                    updateCta(
                                                                        index,
                                                                        'variant',
                                                                        event
                                                                            .target
                                                                            .value,
                                                                    )
                                                                }
                                                            >
                                                                <option value="primary">
                                                                    主要
                                                                </option>
                                                                <option value="secondary">
                                                                    次要
                                                                </option>
                                                            </select>
                                                        </div>
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            className="justify-self-end text-xs text-destructive hover:text-destructive"
                                                            onClick={() =>
                                                                removeCta(
                                                                    index,
                                                                )
                                                            }
                                                        >
                                                            移除
                                                        </Button>
                                                    </div>
                                                </Fragment>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            )}
                        </section>

                        <section className="space-y-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h3 className="text-lg font-semibold text-foreground">
                                        快速開始步驟
                                    </h3>
                                    <p className="text-sm text-muted-foreground">
                                        提醒新成員必備步驟（最多 {MAX_STEPS} 項）。
                                    </p>
                                </div>
                                <Checkbox
                                    checked={data.quickStartSteps.enabled}
                                    onCheckedChange={(checked) =>
                                        form.setData('quickStartSteps', {
                                            ...data.quickStartSteps,
                                            enabled: Boolean(checked),
                                        })
                                    }
                                    aria-label="啟用快速開始步驟"
                                />
                            </div>

                            {data.quickStartSteps.enabled && (
                                <div className="space-y-4 rounded-lg border border-border p-4">
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm font-medium text-muted-foreground">
                                            步驟列表
                                        </span>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={addStep}
                                            disabled={
                                                data.quickStartSteps.steps
                                                    .length >= MAX_STEPS
                                            }
                                        >
                                            新增步驟
                                        </Button>
                                    </div>

                                    <div className="space-y-4">
                                        {data.quickStartSteps.steps.map(
                                            (step, index) => (
                                                <div
                                                    key={`step-${index}`}
                                                    className="grid gap-3 rounded-lg border border-border p-3 md:grid-cols-[minmax(0,0.4fr)_minmax(0,1fr)_auto] md:items-end md:gap-4"
                                                >
                                                    <div className="md:space-y-1.5">
                                                        <Label>
                                                            標題
                                                        </Label>
                                                        <Input
                                                            value={
                                                                step.title
                                                            }
                                                            onChange={(
                                                                event,
                                                            ) =>
                                                                updateStep(
                                                                    index,
                                                                    'title',
                                                                    event.target
                                                                        .value,
                                                                )
                                                            }
                                                            placeholder="例如：登入系統"
                                                        />
                                                        <InputError
                                                            message={
                                                                errors[
                                                                    `quickStartSteps.steps.${index}.title`
                                                                ]
                                                            }
                                                        />
                                                    </div>
                                                    <div className="md:space-y-1.5">
                                                        <Label>
                                                            描述
                                                        </Label>
                                                        <Input
                                                            value={
                                                                step.description
                                                            }
                                                            onChange={(
                                                                event,
                                                            ) =>
                                                                updateStep(
                                                                    index,
                                                                    'description',
                                                                    event.target
                                                                        .value,
                                                                )
                                                            }
                                                            placeholder="提供更多細節"
                                                        />
                                                        <InputError
                                                            message={
                                                                errors[
                                                                    `quickStartSteps.steps.${index}.description`
                                                                ]
                                                            }
                                                        />
                                                    </div>
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        className="justify-self-end text-xs text-destructive hover:text-destructive"
                                                        onClick={() =>
                                                            removeStep(index)
                                                        }
                                                    >
                                                        移除
                                                    </Button>
                                                </div>
                                            ),
                                        )}
                                    </div>
                                </div>
                            )}
                        </section>

                        <section className="space-y-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h3 className="text-lg font-semibold text-foreground">
                                        週報示範亮點
                                    </h3>
                                    <p className="text-sm text-muted-foreground">
                                        強調週報填寫的特色功能。
                                    </p>
                                </div>
                                <Checkbox
                                    checked={data.weeklyReportDemo.enabled}
                                    onCheckedChange={(checked) =>
                                        form.setData('weeklyReportDemo', {
                                            ...data.weeklyReportDemo,
                                            enabled: Boolean(checked),
                                        })
                                    }
                                    aria-label="啟用週報示範"
                                />
                            </div>

                            {data.weeklyReportDemo.enabled && (
                                <div className="space-y-4 rounded-lg border border-border p-4">
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm font-medium text-muted-foreground">
                                            亮點列表（最多 4 項）
                                        </span>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={addHighlight}
                                            disabled={
                                                data.weeklyReportDemo
                                                    .highlights.length >= 4
                                            }
                                        >
                                            新增亮點
                                        </Button>
                                    </div>
                                    <div className="space-y-3">
                                        {data.weeklyReportDemo.highlights.map(
                                            (highlight, index) => (
                                                <div
                                                    key={`highlight-${index}`}
                                                    className="flex items-center gap-3"
                                                >
                                                    <Input
                                                        value={highlight}
                                                        onChange={(event) =>
                                                            updateHighlight(
                                                                index,
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                        placeholder="例如：Redmine/Jira 自動帶入"
                                                    />
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        className="text-xs text-destructive hover:text-destructive"
                                                        onClick={() =>
                                                            removeHighlight(
                                                                index,
                                                            )
                                                        }
                                                    >
                                                        移除
                                                    </Button>
                                                </div>
                                            ),
                                        )}
                                    </div>
                                </div>
                            )}
                        </section>

                        <section className="space-y-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h3 className="text-lg font-semibold text-foreground">
                                        公告與支援資訊
                                    </h3>
                                    <p className="text-sm text-muted-foreground">
                                        提供最新公告與支援聯絡窗口。
                                    </p>
                                </div>
                            </div>

                            <div className="space-y-6 rounded-lg border border-border p-4">
                                <div className="space-y-4">
                                    <div className="flex items-center justify-between">
                                        <Label className="text-sm font-medium">
                                            公告（最多 {MAX_ANNOUNCEMENTS} 則）
                                        </Label>
                                        <div className="flex items-center gap-3">
                                            <span className="text-xs text-muted-foreground">
                                                啟用
                                            </span>
                                            <Checkbox
                                                checked={
                                                    data.announcements.enabled
                                                }
                                                onCheckedChange={(checked) =>
                                                    form.setData(
                                                        'announcements',
                                                        {
                                                            ...data.announcements,
                                                            enabled: Boolean(
                                                                checked,
                                                            ),
                                                        },
                                                    )
                                                }
                                                aria-label="啟用公告"
                                            />
                                        </div>
                                    </div>

                                    {data.announcements.enabled && (
                                        <div className="space-y-4">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={addAnnouncement}
                                                disabled={
                                                    data.announcements.items
                                                        .length >=
                                                    MAX_ANNOUNCEMENTS
                                                }
                                            >
                                                新增公告
                                            </Button>
                                            <div className="space-y-4">
                                                {data.announcements.items.map(
                                                    (item, index) => (
                                                        <div
                                                            key={`announcement-${index}`}
                                                            className="space-y-3 rounded-lg border border-border p-3"
                                                        >
                                                            <div className="grid gap-3 md:grid-cols-2">
                                                                <div className="space-y-1.5">
                                                                    <Label>
                                                                        標題
                                                                    </Label>
                                                                    <Input
                                                                        value={
                                                                            item.title
                                                                        }
                                                                        onChange={(
                                                                            event,
                                                                        ) =>
                                                                            updateAnnouncement(
                                                                                index,
                                                                                'title',
                                                                                event
                                                                                    .target
                                                                                    .value,
                                                                            )
                                                                        }
                                                                    />
                                                                    <InputError
                                                                        message={
                                                                            errors[
                                                                                `announcements.items.${index}.title`
                                                                            ]
                                                                        }
                                                                    />
                                                                </div>
                                                                <div className="space-y-1.5">
                                                                    <Label>
                                                                        發佈日期
                                                                    </Label>
                                                                    <Input
                                                                        type="date"
                                                                        value={
                                                                            item.publishedAt ??
                                                                            ''
                                                                        }
                                                                        onChange={(
                                                                            event,
                                                                        ) =>
                                                                            updateAnnouncement(
                                                                                index,
                                                                                'publishedAt',
                                                                                event
                                                                                    .target
                                                                                    .value,
                                                                            )
                                                                        }
                                                                    />
                                                                </div>
                                                            </div>
                                                            <div className="space-y-1.5">
                                                                <Label>
                                                                    內容
                                                                </Label>
                                                                <textarea
                                                                    className="min-h-[96px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                                                    value={
                                                                        item.content
                                                                    }
                                                                    onChange={(
                                                                        event,
                                                                    ) =>
                                                                        updateAnnouncement(
                                                                            index,
                                                                            'content',
                                                                            event
                                                                                .target
                                                                                .value,
                                                                        )
                                                                    }
                                                                />
                                                                <InputError
                                                                    message={
                                                                        errors[
                                                                            `announcements.items.${index}.content`
                                                                        ]
                                                                    }
                                                                />
                                                            </div>
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                className="text-xs text-destructive hover:text-destructive"
                                                                onClick={() =>
                                                                    removeAnnouncement(
                                                                        index,
                                                                    )
                                                                }
                                                            >
                                                                移除公告
                                                            </Button>
                                                        </div>
                                                    ),
                                                )}
                                            </div>
                                        </div>
                                    )}
                                </div>

                                <div className="space-y-4">
                                    <div className="flex items-center justify-between">
                                        <Label className="text-sm font-medium">
                                            支援聯絡窗口（最多 {MAX_SUPPORT_CONTACTS} 位）
                                        </Label>
                                        <div className="flex items-center gap-3">
                                            <span className="text-xs text-muted-foreground">
                                                啟用
                                            </span>
                                            <Checkbox
                                                checked={
                                                    data.supportContacts
                                                        .enabled
                                                }
                                                onCheckedChange={(checked) =>
                                                    form.setData(
                                                        'supportContacts',
                                                        {
                                                            ...data.supportContacts,
                                                            enabled: Boolean(
                                                                checked,
                                                            ),
                                                        },
                                                    )
                                                }
                                                aria-label="啟用支援聯絡窗口"
                                            />
                                        </div>
                                    </div>

                                    {data.supportContacts.enabled && (
                                        <div className="space-y-4">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={addSupportContact}
                                                disabled={
                                                    data.supportContacts
                                                        .contacts.length >=
                                                    MAX_SUPPORT_CONTACTS
                                                }
                                            >
                                                新增聯絡人
                                            </Button>
                                            <div className="space-y-4">
                                                {data.supportContacts.contacts.map(
                                                    (contact, index) => (
                                                        <div
                                                            key={`contact-${index}`}
                                                            className="grid gap-3 rounded-lg border border-border p-3 md:grid-cols-[minmax(0,0.9fr)_minmax(0,0.8fr)_minmax(0,0.6fr)_auto]"
                                                        >
                                                            <div className="space-y-1.5">
                                                                <Label>
                                                                    姓名
                                                                </Label>
                                                                <Input
                                                                    value={
                                                                        contact.name
                                                                    }
                                                                    onChange={(
                                                                        event,
                                                                    ) =>
                                                                        updateSupportContact(
                                                                            index,
                                                                            'name',
                                                                            event
                                                                                .target
                                                                                .value,
                                                                        )
                                                                    }
                                                                />
                                                                <InputError
                                                                    message={
                                                                        errors[
                                                                            `supportContacts.contacts.${index}.name`
                                                                        ]
                                                                    }
                                                                />
                                                            </div>
                                                            <div className="space-y-1.5">
                                                                <Label>
                                                                    Email
                                                                </Label>
                                                                <Input
                                                                    type="email"
                                                                    value={
                                                                        contact.email ??
                                                                        ''
                                                                    }
                                                                    onChange={(
                                                                        event,
                                                                    ) =>
                                                                        updateSupportContact(
                                                                            index,
                                                                            'email',
                                                                            event
                                                                                .target
                                                                                .value,
                                                                        )
                                                                    }
                                                                />
                                                                <InputError
                                                                    message={
                                                                        errors[
                                                                            `supportContacts.contacts.${index}.email`
                                                                        ]
                                                                    }
                                                                />
                                                            </div>
                                                            <div className="space-y-1.5">
                                                                <Label>
                                                                    電話
                                                                </Label>
                                                                <Input
                                                                    value={
                                                                        contact.phone ??
                                                                        ''
                                                                    }
                                                                    onChange={(
                                                                        event,
                                                                    ) =>
                                                                        updateSupportContact(
                                                                            index,
                                                                            'phone',
                                                                            event
                                                                                .target
                                                                                .value,
                                                                        )
                                                                    }
                                                                />
                                                                <InputError
                                                                    message={
                                                                        errors[
                                                                            `supportContacts.contacts.${index}.phone`
                                                                        ]
                                                                    }
                                                                />
                                                            </div>
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                className="justify-self-end text-xs text-destructive hover:text-destructive"
                                                                onClick={() =>
                                                                    removeSupportContact(
                                                                        index,
                                                                    )
                                                                }
                                                            >
                                                                移除
                                                            </Button>
                                                        </div>
                                                    ),
                                                )}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </section>

                        <div className="flex items-center gap-4">
                            <Button type="submit" disabled={processing}>
                                儲存歡迎頁設定
                            </Button>
                            {recentlySuccessful && (
                                <span className="text-sm text-emerald-600">
                                    已儲存！
                                </span>
                            )}
                        </div>
                    </form>

                    <aside className="space-y-6">
                        <div className="rounded-2xl border border-dashed border-border p-4">
                            <span className="text-sm font-semibold text-muted-foreground">
                                預覽
                            </span>
                        </div>
                        <div className="overflow-hidden rounded-2xl border border-border shadow-sm">
                            <div className="bg-gray-50 dark:bg-gray-900">
                                {previewConfig.hero && (
                                    <HeroModule
                                        title={previewConfig.hero.title}
                                        subtitle={previewConfig.hero.subtitle}
                                        backgroundImage={
                                            previewConfig.hero.backgroundImage ??
                                            undefined
                                        }
                                        videoUrl={
                                            previewConfig.hero.videoUrl ??
                                            undefined
                                        }
                                        brandColor={accentColor}
                                        ctas={previewConfig.ctas}
                                    />
                                )}

                                {previewConfig.weeklyReportDemo && (
                                    <WeeklyReportDemoModule
                                        brandColor={accentColor}
                                        highlights={
                                            previewConfig.weeklyReportDemo
                                                .highlights
                                        }
                                    />
                                )}

                                {previewConfig.quickStartSteps &&
                                    previewConfig.quickStartSteps.steps
                                        ?.length > 0 && (
                                        <QuickStartStepsModule
                                            steps={
                                                previewConfig.quickStartSteps
                                                    .steps
                                            }
                                            brandColor={accentColor}
                                        />
                                    )}

                                {previewConfig.announcements &&
                                    previewConfig.announcements.items?.length >
                                        0 && (
                                        <AnnouncementsModule
                                            announcements={
                                                previewConfig.announcements.items
                                            }
                                            brandColor={accentColor}
                                        />
                                    )}

                                {previewConfig.supportContacts &&
                                    previewConfig.supportContacts.contacts
                                        ?.length > 0 && (
                                        <SupportContactsModule
                                            contacts={
                                                previewConfig.supportContacts
                                                    .contacts
                                            }
                                            brandColor={accentColor}
                                        />
                                    )}
                            </div>
                        </div>
                    </aside>
                </div>
            </CardContent>
        </Card>
    );
}

interface IPWhitelistFormProps {
    initialAddresses: string[];
    companySlug: string;
}

function IPWhitelistForm({ initialAddresses, companySlug }: IPWhitelistFormProps) {
    const form = useForm<{ ipAddresses: string[] }>({
        ipAddresses: initialAddresses.length ? initialAddresses : [''],
    });

    const { data, errors, processing, recentlySuccessful } = form;

    const addAddress = () => {
        if (data.ipAddresses.length >= 5) {
            return;
        }
        form.setData('ipAddresses', [...data.ipAddresses, '']);
    };

    const updateAddress = (index: number, value: string) => {
        const next = data.ipAddresses.map((ip, idx) =>
            idx === index ? value : ip,
        );
        form.setData('ipAddresses', next);
    };

    const removeAddress = (index: number) => {
        const remaining = data.ipAddresses.filter((_, idx) => idx !== index);
        form.setData('ipAddresses', remaining.length ? remaining : ['']);
    };

    const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        form.transform((formData) => ({
            ipAddresses: formData.ipAddresses
                .map((ip) => ip.trim())
                .filter(Boolean),
        }));

        form.patch(settingsRoutes.ipWhitelist.url({ company: companySlug }), {
            preserveScroll: true,
        });
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-2xl font-semibold">
                    IP 白名單
                </CardTitle>
                <p className="text-sm text-muted-foreground">
                    限制哪些 IP / CIDR 可登入用戶，最多 5 組。
                </p>
            </CardHeader>
            <CardContent>
                <form className="space-y-6" onSubmit={handleSubmit}>
                    <div className="space-y-3">
                        {data.ipAddresses.map((ip, index) => (
                            <div key={`ip-${index}`} className="space-y-2">
                                <div className="flex items-center gap-3">
                                    <Input
                                        value={ip}
                                        onChange={(event) =>
                                            updateAddress(
                                                index,
                                                event.target.value,
                                            )
                                        }
                                        placeholder="例如：203.66.113.1 或 10.0.0.0/8"
                                    />
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        className={cn(
                                            'text-xs text-destructive hover:text-destructive',
                                            data.ipAddresses.length === 1 &&
                                                'opacity-40',
                                        )}
                                        onClick={() => removeAddress(index)}
                                        disabled={data.ipAddresses.length === 1}
                                    >
                                        移除
                                    </Button>
                                </div>
                                <InputError
                                    message={
                                        errors[`ipAddresses.${index}`]
                                    }
                                />
                            </div>
                        ))}
                        <InputError message={errors.ipAddresses} />
                        <div className="flex items-center gap-3">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={addAddress}
                                disabled={data.ipAddresses.length >= 5}
                            >
                                新增 IP
                            </Button>
                            {recentlySuccessful && (
                                <span className="text-sm text-emerald-600">
                                    已更新！
                                </span>
                            )}
                        </div>
                    </div>

                    <Button type="submit" disabled={processing}>
                        儲存白名單
                    </Button>
                </form>
            </CardContent>
        </Card>
    );
}

