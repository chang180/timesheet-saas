import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User | null;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
    tenant?: {
        company?: {
            id: number;
            name: string;
            slug: string;
            [key: string]: unknown;
        };
        settings?: Record<string, unknown> | null;
    } | null;
    tenantConfig: {
        slugMode: string | null;
        primaryDomain: string | null;
    };
    appEnv: string;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    company?: {
        id: number;
        name: string;
        slug: string;
        [key: string]: unknown;
    } | null;
    [key: string]: unknown; // This allows for additional properties...
}
