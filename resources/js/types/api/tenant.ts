export interface WelcomePageHero {
    title: string;
    subtitle?: string;
    backgroundImage?: string;
}

export interface WelcomePageStep {
    title: string;
    description: string;
    icon?: string;
}

export interface WelcomePageCTA {
    text: string;
    url: string;
    variant?: 'primary' | 'secondary';
}

export interface WelcomePageAnnouncement {
    title: string;
    content: string;
    publishedAt?: string;
}

export interface WelcomePageSupport {
    name: string;
    email?: string;
    phone?: string;
}

export interface WelcomePageModule {
    type:
        | 'hero'
        | 'quickStartSteps'
        | 'weeklyReportDemo'
        | 'announcements'
        | 'supportContacts';
    enabled: boolean;
    order: number;
    data?: unknown;
}

export interface WelcomePageConfig {
    hero?: {
        enabled: boolean;
        title: string;
        subtitle?: string;
        backgroundImage?: string;
        videoUrl?: string;
    };
    quickStartSteps?: {
        enabled: boolean;
        steps: WelcomePageStep[];
    };
    weeklyReportDemo?: {
        enabled: boolean;
        highlights?: string[];
    };
    announcements?: {
        enabled: boolean;
        items: WelcomePageAnnouncement[];
    };
    supportContacts?: {
        enabled: boolean;
        contacts: WelcomePageSupport[];
    };
    ctas?: WelcomePageCTA[];
}

export interface TenantSettings {
    companyName: string;
    brandColor?: string;
    logo?: string;
    welcomePage?: WelcomePageConfig;
    ipWhitelist?: string[];
    workingHoursPerDay?: number;
    maxUserLimit?: number;
    currentUserCount?: number;
}

export interface IPWhitelistUpdate {
    ipAddresses: string[];
}
