import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import tenantRoutes from '@/routes/tenant';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ClipboardList, Settings2 } from 'lucide-react';
import AppLogo from './app-logo';

export function AppSidebar() {
    const { auth, tenant } = usePage<SharedData>().props;
    const userRole = (auth?.user?.role as string | undefined)?.toLowerCase();
    const canManageTenant = ['owner', 'admin', 'company_admin'].includes(
        userRole ?? '',
    );
    const companySlug = (tenant?.company as { slug?: string } | undefined)?.slug;

    const mainNavItems: NavItem[] = [];

    if (companySlug) {
        mainNavItems.push({
            title: '週報工作簿',
            href: tenantRoutes.weeklyReports.url({ company: companySlug }),
            icon: ClipboardList,
        });
    }

    if (canManageTenant && companySlug) {
        mainNavItems.push({
            title: '用戶設定',
            href: tenantRoutes.settings.url({ company: companySlug }),
            icon: Settings2,
        });
    }

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link
                                href={
                                    companySlug
                                        ? tenantRoutes.weeklyReports.url({ company: companySlug })
                                        : '/'
                                }
                                prefetch
                            >
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
