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
import { Building2, ClipboardList, Settings2, Users } from 'lucide-react';
import AppLogo from './app-logo';

export function AppSidebar() {
    const { auth, tenant } = usePage<SharedData>().props;
    const userRole = (auth?.user?.role as string | undefined)?.toLowerCase();
    const canManageTenant = ['owner', 'admin', 'company_admin'].includes(
        userRole ?? '',
    );
    
    // 優先使用 tenant context 的 company，否則使用 auth.user.company
    const companyFromTenant = tenant?.company as { slug?: string } | undefined;
    const companyFromUser = auth?.user?.company as { slug?: string } | undefined;
    const companySlug = companyFromTenant?.slug ?? companyFromUser?.slug;

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
            title: '成員管理',
            href: tenantRoutes.members.url({ company: companySlug }),
            icon: Users,
        });
        mainNavItems.push({
            title: '組織管理',
            href: tenantRoutes.organization.url({ company: companySlug }),
            icon: Building2,
        });
        mainNavItems.push({
            title: '租戶設定',
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
