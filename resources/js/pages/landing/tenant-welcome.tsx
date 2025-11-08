import { AnnouncementsModule } from '@/tenant/welcome-modules/announcements-module';
import { HeroModule } from '@/tenant/welcome-modules/hero-module';
import { QuickStartStepsModule } from '@/tenant/welcome-modules/quick-start-steps-module';
import { SupportContactsModule } from '@/tenant/welcome-modules/support-contacts-module';

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
    const brandColor = tenantSettings.brandColor;

    return (
        <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
            {/* Hero Module */}
            {welcomeConfig.hero?.enabled && (
                <HeroModule
                    title={welcomeConfig.hero.title}
                    subtitle={welcomeConfig.hero.subtitle}
                    backgroundImage={welcomeConfig.hero.backgroundImage}
                    videoUrl={welcomeConfig.hero.videoUrl}
                    brandColor={brandColor}
                    ctas={welcomeConfig.ctas}
                />
            )}

            {/* Quick Start Steps Module */}
            {welcomeConfig.quickStartSteps?.enabled &&
                welcomeConfig.quickStartSteps.steps && (
                    <QuickStartStepsModule
                        steps={welcomeConfig.quickStartSteps.steps}
                        brandColor={brandColor}
                    />
                )}

            {/* Announcements Module */}
            {welcomeConfig.announcements?.enabled &&
                welcomeConfig.announcements.items && (
                    <AnnouncementsModule
                        announcements={welcomeConfig.announcements.items}
                        brandColor={brandColor}
                    />
                )}

            {/* Support Contacts Module */}
            {welcomeConfig.supportContacts?.enabled &&
                welcomeConfig.supportContacts.contacts && (
                    <SupportContactsModule
                        contacts={welcomeConfig.supportContacts.contacts}
                        brandColor={brandColor}
                    />
                )}
        </div>
    );
}
