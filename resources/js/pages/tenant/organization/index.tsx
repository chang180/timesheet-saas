import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import tenantRoutes from '@/routes/tenant';
import divisionsApi from '@/routes/api/v1/tenant/divisions';
import departmentsApi from '@/routes/api/v1/tenant/departments';
import teamsApi from '@/routes/api/v1/tenant/teams';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { Building2, Plus } from 'lucide-react';
import { useState, useEffect } from 'react';
import { OrganizationTree } from '@/components/tenant/organization-tree';
import { DivisionFormDialog } from '@/components/tenant/division-form-dialog';
import { DepartmentFormDialog } from '@/components/tenant/department-form-dialog';
import { TeamFormDialog } from '@/components/tenant/team-form-dialog';
import { toast } from 'sonner';

type Organization = {
    divisions: Array<{
        id: number;
        name: string;
        slug: string;
        description: string | null;
        sort_order: number;
        is_active: boolean;
        invitation_token: string | null;
        invitation_enabled: boolean;
    }>;
    departments: Array<{
        id: number;
        division_id: number | null;
        name: string;
        slug: string;
        description: string | null;
        sort_order: number;
        is_active: boolean;
        invitation_token: string | null;
        invitation_enabled: boolean;
    }>;
    teams: Array<{
        id: number;
        division_id: number | null;
        department_id: number | null;
        name: string;
        slug: string;
        description: string | null;
        sort_order: number;
        is_active: boolean;
        invitation_token: string | null;
        invitation_enabled: boolean;
    }>;
};

interface PageProps {
    company: {
        id: number;
        name: string;
        slug: string;
    };
    organization: Organization;
}

export default function OrganizationManagementPage(props: PageProps) {
    const { tenant } = usePage<SharedData>().props;
    const companySlug = (tenant?.company as { slug?: string } | undefined)?.slug ?? props.company.slug;

    const [organization, setOrganization] = useState<Organization>(props.organization);
    const [divisionDialogOpen, setDivisionDialogOpen] = useState(false);

    // 當 props 更新時，同步更新 organization 狀態
    useEffect(() => {
        setOrganization(props.organization);
    }, [props.organization]);
    const [departmentDialogOpen, setDepartmentDialogOpen] = useState(false);
    const [teamDialogOpen, setTeamDialogOpen] = useState(false);
    const [editingDivision, setEditingDivision] = useState<typeof organization.divisions[0] | null>(null);
    const [editingDepartment, setEditingDepartment] = useState<typeof organization.departments[0] | null>(null);
    const [editingTeam, setEditingTeam] = useState<typeof organization.teams[0] | null>(null);
    const [selectedDivisionId, setSelectedDivisionId] = useState<number | null>(null);
    const [selectedDepartmentId, setSelectedDepartmentId] = useState<number | null>(null);

    const fetchOrganization = async () => {
        try {
            const [divisionsRes, departmentsRes, teamsRes] = await Promise.all([
                fetch(divisionsApi.index.url({ company: companySlug }), {
                    credentials: 'include',
                    headers: { 'Accept': 'application/json' },
                }),
                fetch(departmentsApi.index.url({ company: companySlug }), {
                    credentials: 'include',
                    headers: { 'Accept': 'application/json' },
                }),
                fetch(teamsApi.index.url({ company: companySlug }), {
                    credentials: 'include',
                    headers: { 'Accept': 'application/json' },
                }),
            ]);

            const [divisionsData, departmentsData, teamsData] = await Promise.all([
                divisionsRes.json(),
                departmentsRes.json(),
                teamsRes.json(),
            ]);

            setOrganization({
                divisions: divisionsData.divisions,
                departments: departmentsData.departments,
                teams: teamsData.teams,
            });
        } catch (error) {
            console.error('Error fetching organization:', error);
            toast.error('載入組織資料失敗');
        }
    };

    const handleCreateDivision = () => {
        setEditingDivision(null);
        setDivisionDialogOpen(true);
    };

    const handleEditDivision = (division: typeof organization.divisions[0]) => {
        setEditingDivision(division);
        setDivisionDialogOpen(true);
    };

    const handleDeleteDivision = async (division: typeof organization.divisions[0]) => {
        if (!confirm(`確定要刪除「${division.name}」嗎？此操作無法復原。`)) {
            return;
        }

        try {
            const url = divisionsApi.destroy.url({ company: companySlug, division: division.id });
            const response = await fetch(url, {
                method: 'DELETE',
                credentials: 'include',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) {
                const data = await response.json();
                toast.error(data.message || '刪除失敗');
                return;
            }

            toast.success('事業群已刪除');
            fetchOrganization();
        } catch (error) {
            console.error('Error deleting division:', error);
            toast.error('刪除失敗');
        }
    };

    const handleCreateDepartment = (divisionId?: number) => {
        setSelectedDivisionId(divisionId ?? null);
        setEditingDepartment(null);
        setDepartmentDialogOpen(true);
    };

    const handleEditDepartment = (department: typeof organization.departments[0]) => {
        setEditingDepartment(department);
        setSelectedDivisionId(department.division_id);
        setDepartmentDialogOpen(true);
    };

    const handleDeleteDepartment = async (department: typeof organization.departments[0]) => {
        if (!confirm(`確定要刪除「${department.name}」嗎？此操作無法復原。`)) {
            return;
        }

        try {
            const url = departmentsApi.destroy.url({ company: companySlug, department: department.id });
            const response = await fetch(url, {
                method: 'DELETE',
                credentials: 'include',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) {
                const data = await response.json();
                toast.error(data.message || '刪除失敗');
                return;
            }

            toast.success('部門已刪除');
            fetchOrganization();
        } catch (error) {
            console.error('Error deleting department:', error);
            toast.error('刪除失敗');
        }
    };

    const handleCreateTeam = (departmentId?: number, divisionId?: number) => {
        setSelectedDepartmentId(departmentId ?? null);
        setSelectedDivisionId(divisionId ?? null);
        setEditingTeam(null);
        setTeamDialogOpen(true);
    };

    const handleEditTeam = (team: typeof organization.teams[0]) => {
        setEditingTeam(team);
        setSelectedDepartmentId(team.department_id);
        setSelectedDivisionId(team.division_id);
        setTeamDialogOpen(true);
    };

    const handleDeleteTeam = async (team: typeof organization.teams[0]) => {
        if (!confirm(`確定要刪除「${team.name}」嗎？此操作無法復原。`)) {
            return;
        }

        try {
            const url = teamsApi.destroy.url({ company: companySlug, team: team.id });
            const response = await fetch(url, {
                method: 'DELETE',
                credentials: 'include',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) {
                const data = await response.json();
                toast.error(data.message || '刪除失敗');
                return;
            }

            toast.success('小組已刪除');
            fetchOrganization();
        } catch (error) {
            console.error('Error deleting team:', error);
            toast.error('刪除失敗');
        }
    };

    const handleMoveUp = async (type: 'division' | 'department' | 'team', id: number, currentOrder: number) => {
        if (currentOrder <= 0) {
            return;
        }

        try {
            const apiMap = {
                division: divisionsApi,
                department: departmentsApi,
                team: teamsApi,
            };
            const api = apiMap[type];

            const url = api.update.url({ company: companySlug, [type]: id });
            const response = await fetch(url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'include',
                body: JSON.stringify({ sort_order: currentOrder - 1 }),
            });

            if (!response.ok) {
                toast.error('移動失敗');
                return;
            }

            fetchOrganization();
        } catch (error) {
            console.error('Error moving up:', error);
            toast.error('移動失敗');
        }
    };

    const handleMoveDown = async (type: 'division' | 'department' | 'team', id: number, currentOrder: number, maxOrder: number) => {
        if (currentOrder >= maxOrder) {
            return;
        }

        try {
            const apiMap = {
                division: divisionsApi,
                department: departmentsApi,
                team: teamsApi,
            };
            const api = apiMap[type];

            const url = api.update.url({ company: companySlug, [type]: id });
            const response = await fetch(url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'include',
                body: JSON.stringify({ sort_order: currentOrder + 1 }),
            });

            if (!response.ok) {
                toast.error('移動失敗');
                return;
            }

            fetchOrganization();
        } catch (error) {
            console.error('Error moving down:', error);
            toast.error('移動失敗');
        }
    };

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: '組織管理',
            href: tenantRoutes.organization.url({ company: companySlug }),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="組織管理" />
            <div className="space-y-6 px-4 py-8 lg:px-0">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight text-foreground">
                            組織管理
                        </h1>
                        <p className="text-sm text-muted-foreground mt-1">
                            管理事業群、部門與小組的層級結構
                        </p>
                    </div>
                    <Button onClick={handleCreateDivision}>
                        <Plus className="mr-2 h-4 w-4" />
                        新增事業群
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Building2 className="h-5 w-5" />
                            組織層級結構
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <OrganizationTree
                            organization={organization}
                            onCreateDivision={handleCreateDivision}
                            onEditDivision={handleEditDivision}
                            onDeleteDivision={handleDeleteDivision}
                            onCreateDepartment={handleCreateDepartment}
                            onEditDepartment={handleEditDepartment}
                            onDeleteDepartment={handleDeleteDepartment}
                            onCreateTeam={handleCreateTeam}
                            onEditTeam={handleEditTeam}
                            onDeleteTeam={handleDeleteTeam}
                            onMoveUp={handleMoveUp}
                            onMoveDown={handleMoveDown}
                        />
                    </CardContent>
                </Card>

                <DivisionFormDialog
                    open={divisionDialogOpen}
                    onOpenChange={setDivisionDialogOpen}
                    companySlug={companySlug}
                    division={editingDivision}
                    onSuccess={fetchOrganization}
                />

                <DepartmentFormDialog
                    open={departmentDialogOpen}
                    onOpenChange={setDepartmentDialogOpen}
                    companySlug={companySlug}
                    department={editingDepartment}
                    selectedDivisionId={selectedDivisionId}
                    organization={organization}
                    onSuccess={fetchOrganization}
                />

                <TeamFormDialog
                    open={teamDialogOpen}
                    onOpenChange={setTeamDialogOpen}
                    companySlug={companySlug}
                    team={editingTeam}
                    selectedDepartmentId={selectedDepartmentId}
                    selectedDivisionId={selectedDivisionId}
                    organization={organization}
                    onSuccess={fetchOrganization}
                />
            </div>
        </AppLayout>
    );
}
