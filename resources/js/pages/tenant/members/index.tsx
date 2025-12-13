import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import tenantRoutes from '@/routes/tenant';
import tenantApiRoutes from '@/routes/api/v1/tenant';
import membersApi from '@/routes/api/v1/tenant/members';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { Plus, Search, Users } from 'lucide-react';
import { useEffect, useState } from 'react';
import { MemberInviteDialog } from '@/components/tenant/member-invite-dialog';
import { MemberListTable } from '@/components/tenant/member-list-table';
import { MemberRoleEditDialog } from '@/components/tenant/member-role-edit-dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { apiRequest, ensureCsrfCookie } from '@/lib/api-client';

type Member = {
    id: number;
    name: string;
    email: string;
    role: string;
    division: { id: number; name: string } | null;
    department: { id: number; name: string } | null;
    team: { id: number; name: string } | null;
    last_active_at: string | null;
    invitation_sent_at: string | null;
    invitation_accepted_at: string | null;
};

type Organization = {
    divisions: Array<{ id: number; name: string; slug: string; is_active: boolean }>;
    departments: Array<{ id: number; division_id: number | null; name: string; slug: string; is_active: boolean }>;
    teams: Array<{ id: number; division_id: number | null; department_id: number | null; name: string; slug: string; is_active: boolean }>;
};

interface PageProps {
    company: {
        id: number;
        name: string;
        slug: string;
        user_limit: number;
        current_user_count: number;
    };
    organization: Organization;
    roles: {
        available: string[];
    };
}

export default function MemberManagementPage(props: PageProps) {
    const { tenant } = usePage<SharedData>().props;
    const companySlug = (tenant?.company as { slug?: string } | undefined)?.slug ?? props.company.slug;

    const [members, setMembers] = useState<Member[]>([]);
    const [loading, setLoading] = useState(true);
    const [pagination, setPagination] = useState({
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
    });
    const [filters, setFilters] = useState({
        role: 'all',
        division_id: 'all',
        department_id: 'all',
        team_id: 'all',
        keyword: '',
    });
    const [inviteDialogOpen, setInviteDialogOpen] = useState(false);
    const [editDialogOpen, setEditDialogOpen] = useState(false);
    const [selectedMember, setSelectedMember] = useState<Member | null>(null);

    const fetchMembers = async (page = 1) => {
        setLoading(true);
        try {
            await ensureCsrfCookie();

            const params = new URLSearchParams({
                page: page.toString(),
                per_page: '15',
            });

            if (filters.role && filters.role !== 'all') {
                params.append('role', filters.role);
            }
            if (filters.division_id && filters.division_id !== 'all') {
                params.append('division_id', filters.division_id);
            }
            if (filters.department_id && filters.department_id !== 'all') {
                params.append('department_id', filters.department_id);
            }
            if (filters.team_id && filters.team_id !== 'all') {
                params.append('team_id', filters.team_id);
            }
            if (filters.keyword && filters.keyword.trim() !== '') {
                params.append('keyword', filters.keyword.trim());
            }

            const url = membersApi.index.url({ company: companySlug }) + '?' + params.toString();
            const response = await apiRequest(url, {
                method: 'GET',
            });

            if (!response.ok) {
                throw new Error('Failed to fetch members');
            }

            const data = await response.json();
            
            // 確保 members 是數組
            const membersArray = Array.isArray(data.members) ? data.members : [];
            const paginationData = data.pagination || {
                current_page: 1,
                last_page: 1,
                per_page: 15,
                total: 0,
            };
            
            setMembers(membersArray);
            setPagination(paginationData);
        } catch (error) {
            console.error('Error fetching members:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchMembers(1);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [filters.role, filters.division_id, filters.department_id, filters.team_id, filters.keyword, companySlug]);

    const handleInviteSuccess = () => {
        setInviteDialogOpen(false);
        fetchMembers(pagination.current_page);
    };

    const handleEditSuccess = () => {
        setEditDialogOpen(false);
        setSelectedMember(null);
        fetchMembers(pagination.current_page);
    };

    const handleEditMember = (member: Member) => {
        setSelectedMember(member);
        setEditDialogOpen(true);
    };

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: '成員管理',
            href: tenantRoutes.members.url({ company: companySlug }),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="成員管理" />
            <div className="space-y-6 px-4 py-8 lg:px-0">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight text-foreground">
                            成員管理
                        </h1>
                        <p className="text-sm text-muted-foreground mt-1">
                            管理團隊成員、邀請新成員並調整角色與權限
                        </p>
                    </div>
                    <Button
                        onClick={() => setInviteDialogOpen(true)}
                        disabled={props.company.current_user_count >= props.company.user_limit}
                    >
                        <Plus className="mr-2 h-4 w-4" />
                        邀請成員
                    </Button>
                </div>

                {props.company.current_user_count >= props.company.user_limit && (
                    <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-500/10 dark:text-amber-400">
                        成員數已達上限（{props.company.current_user_count}/{props.company.user_limit}），無法邀請新成員。
                    </div>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Users className="h-5 w-5" />
                            成員列表
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            <div className="grid gap-4 md:grid-cols-5">
                                <div className="md:col-span-2">
                                    <div className="relative">
                                        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                        <Input
                                            placeholder="搜尋姓名或 Email..."
                                            value={filters.keyword}
                                            onChange={(e) =>
                                                setFilters({ ...filters, keyword: e.target.value })
                                            }
                                            className="pl-9"
                                        />
                                    </div>
                                </div>
                                <Select
                                    value={filters.role || 'all'}
                                    onValueChange={(value) =>
                                        setFilters({ ...filters, role: value === 'all' ? 'all' : value })
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="所有角色" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">所有角色</SelectItem>
                                        {props.roles.available.map((role) => (
                                            <SelectItem key={role} value={role}>
                                                {role === 'company_admin' && '公司管理者'}
                                                {role === 'division_lead' && '事業群主管'}
                                                {role === 'department_manager' && '部門主管'}
                                                {role === 'team_lead' && '小組主管'}
                                                {role === 'member' && '一般成員'}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <Select
                                    value={filters.division_id || 'all'}
                                    onValueChange={(value) =>
                                        setFilters({ ...filters, division_id: value === 'all' ? 'all' : value, department_id: 'all', team_id: 'all' })
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="所有事業群" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">所有事業群</SelectItem>
                                        {props.organization.divisions
                                            .filter((d) => d.is_active)
                                            .map((division) => (
                                                <SelectItem key={division.id} value={division.id.toString()}>
                                                    {division.name}
                                                </SelectItem>
                                            ))}
                                    </SelectContent>
                                </Select>
                                <Select
                                    value={filters.department_id || 'all'}
                                    onValueChange={(value) =>
                                        setFilters({ ...filters, department_id: value === 'all' ? 'all' : value, team_id: 'all' })
                                    }
                                    disabled={!filters.division_id || filters.division_id === 'all'}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="所有部門" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">所有部門</SelectItem>
                                        {props.organization.departments
                                            .filter((d) => d.is_active && (!filters.division_id || d.division_id === Number(filters.division_id)))
                                            .map((department) => (
                                                <SelectItem key={department.id} value={department.id.toString()}>
                                                    {department.name}
                                                </SelectItem>
                                            ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <MemberListTable
                                members={members}
                                loading={loading}
                                pagination={pagination}
                                onEdit={handleEditMember}
                                onPageChange={fetchMembers}
                            />
                        </div>
                    </CardContent>
                </Card>

                <MemberInviteDialog
                    open={inviteDialogOpen}
                    onOpenChange={setInviteDialogOpen}
                    companySlug={companySlug}
                    organization={props.organization}
                    roles={props.roles.available}
                    currentUserCount={props.company.current_user_count}
                    userLimit={props.company.user_limit}
                    onSuccess={handleInviteSuccess}
                />

                {selectedMember && (
                    <MemberRoleEditDialog
                        open={editDialogOpen}
                        onOpenChange={setEditDialogOpen}
                        member={selectedMember}
                        companySlug={companySlug}
                        organization={props.organization}
                        roles={props.roles.available}
                        onSuccess={handleEditSuccess}
                    />
                )}
            </div>
        </AppLayout>
    );
}
