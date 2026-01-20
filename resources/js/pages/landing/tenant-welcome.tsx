import { AnnouncementsModule } from '@/tenant/welcome-modules/announcements-module';
import { HeroModule } from '@/tenant/welcome-modules/hero-module';
import { QuickStartStepsModule } from '@/tenant/welcome-modules/quick-start-steps-module';
import { SupportContactsModule } from '@/tenant/welcome-modules/support-contacts-module';
import { WeeklyReportDemoModule } from '@/tenant/welcome-modules/weekly-report-demo-module';
import { useMemo } from 'react';

interface TenantSettings {
    companyName: string;
    brandColor?: string;
    logo?: string;
}

interface WelcomeConfig {
    hero?: {
        enabled: boolean;
        title: string;
        subtitle?: string;
        backgroundImage?: string;
        videoUrl?: string;
    };
    quickStartSteps?: {
        enabled: boolean;
        steps: Array<{
            title: string;
            description: string;
        }>;
    };
    announcements?: {
        enabled: boolean;
        items: Array<{
            title: string;
            content: string;
            publishedAt?: string;
        }>;
    };
    supportContacts?: {
        enabled: boolean;
        contacts: Array<{
            name: string;
            email?: string;
            phone?: string;
        }>;
    };
    weeklyReportDemo?: {
        enabled: boolean;
        highlights?: string[];
    };
    ctas?: Array<{
        text: string;
        url: string;
        variant?: 'primary' | 'secondary';
    }>;
}

interface PageProps {
    tenantSettings: TenantSettings;
    welcomeConfig: WelcomeConfig;
}

export default function TenantWelcomePage({
    tenantSettings,
    welcomeConfig,
}: PageProps) {
    const brandColor = tenantSettings.brandColor ?? '#2563eb';

    const safeHero = useMemo(() => {
        if (welcomeConfig.hero?.enabled === false) {
            return null;
        }

        const defaultTitle = `歡迎 ${tenantSettings.companyName} 團隊`;

        return {
            enabled: true,
            title: welcomeConfig.hero?.title || defaultTitle,
            subtitle:
                welcomeConfig.hero?.subtitle ??
                '快速建立週報流程，掌握團隊進度。',
            backgroundImage: welcomeConfig.hero?.backgroundImage,
            videoUrl: welcomeConfig.hero?.videoUrl,
            ctas: welcomeConfig.ctas ?? [],
        };
    }, [tenantSettings.companyName, welcomeConfig]);

    const quickStartConfig =
        welcomeConfig.quickStartSteps?.enabled === false
            ? null
            : welcomeConfig.quickStartSteps ?? {
                  enabled: true,
                  steps: [
                      {
                          title: '登入系統',
                          description: '使用公司信箱登入用戶後台。',
                      },
                      {
                          title: '設定成員',
                          description: '邀請同仁加入並指定主管層級。',
                      },
                      {
                          title: '開始填寫週報',
                          description: '匯入任務、拖曳排序並提交審核。',
                      },
                  ],
              };

    const announcementsConfig =
        welcomeConfig.announcements?.enabled === false
            ? null
            : {
                  enabled:
                      welcomeConfig.announcements?.enabled ??
                      (welcomeConfig.announcements?.items?.length ?? 0) > 0,
                  items: welcomeConfig.announcements?.items ?? [],
              };

    const supportContactsConfig =
        welcomeConfig.supportContacts?.enabled === false
            ? null
            : {
                  enabled:
                      welcomeConfig.supportContacts?.enabled ??
                      (welcomeConfig.supportContacts?.contacts?.length ?? 0) >
                          0,
                  contacts: welcomeConfig.supportContacts?.contacts ?? [],
              };

    const weeklyReportDemoConfig =
        welcomeConfig.weeklyReportDemo?.enabled === false
            ? null
            : {
                  enabled: true,
                  highlights: welcomeConfig.weeklyReportDemo?.highlights ?? [
                      '拖曳排序與直播更新狀態',
                      'Redmine/Jira 自動帶入任務',
                      '假日與工時上限即時提醒',
                  ],
              };

    const pageStyle = useMemo<React.CSSProperties>(
        () => ({
            '--tenant-brand-color': brandColor || '#2563eb',
        } as React.CSSProperties),
        [brandColor]
    );

    return (
        <div
            className="min-h-screen bg-gray-50 dark:bg-gray-900"
            style={pageStyle}
        >
            {/* Hero Module */}
            {safeHero && (
                <HeroModule
                    title={safeHero.title}
                    subtitle={safeHero.subtitle}
                    backgroundImage={safeHero.backgroundImage}
                    videoUrl={safeHero.videoUrl}
                    brandColor={brandColor}
                    ctas={safeHero.ctas}
                />
            )}

            {weeklyReportDemoConfig && (
                <WeeklyReportDemoModule
                    highlights={weeklyReportDemoConfig.highlights}
                    brandColor={brandColor}
                />
            )}

            {/* Quick Start Steps Module */}
            {quickStartConfig && quickStartConfig.steps && (
                    <QuickStartStepsModule
                        steps={quickStartConfig.steps}
                        brandColor={brandColor}
                    />
                )}

            {/* Announcements Module */}
            {announcementsConfig && announcementsConfig.enabled && (
                    <AnnouncementsModule
                        announcements={announcementsConfig.items}
                        brandColor={brandColor}
                    />
                )}

            {/* Support Contacts Module */}
            {supportContactsConfig && supportContactsConfig.enabled && (
                    <SupportContactsModule
                        contacts={supportContactsConfig.contacts}
                        brandColor={brandColor}
                    />
                )}
        </div>
    );
}
