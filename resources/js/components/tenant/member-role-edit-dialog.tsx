import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useState, useEffect } from 'react';
import membersApi from '@/routes/api/v1/tenant/members';
import { toast } from 'sonner';
import { apiRequest, ensureCsrfCookie } from '@/lib/api-client';

type Member = {
    id: number;
    name: string;
    email: string;
    role: string;
    division: { id: number; name: string } | null;
    department: { id: number; name: string } | null;
    team: { id: number; name: string } | null;
};

type Organization = {
    divisions: Array<{ id: number; name: string; slug: string; is_active: boolean }>;
    departments: Array<{ id: number; division_id: number | null; name: string; slug: string; is_active: boolean }>;
    teams: Array<{ id: number; division_id: number | null; department_id: number | null; name: string; slug: string; is_active: boolean }>;
};

interface MemberRoleEditDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    member: Member;
    companySlug: string;
    organization: Organization;
    roles: string[];
    onSuccess: () => void;
}

const ROLE_LABELS: Record<string, string> = {
    company_admin: '公司管理者',
    division_lead: '事業群主管',
    department_manager: '部門主管',
    team_lead: '小組主管',
    member: '一般成員',
};

export function MemberRoleEditDialog({
    open,
    onOpenChange,
    member,
    companySlug,
    organization,
    roles,
    onSuccess,
}: MemberRoleEditDialogProps) {
    const [formData, setFormData] = useState({
        role: member.role,
        division_id: member.division?.id.toString() ?? '',
        department_id: member.department?.id.toString() ?? '',
        team_id: member.team?.id.toString() ?? '',
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        if (open) {
            setFormData({
                role: member.role,
                division_id: member.division?.id.toString() ?? '',
                department_id: member.department?.id.toString() ?? '',
                team_id: member.team?.id.toString() ?? '',
            });
            setErrors({});
        }
    }, [open, member]);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);
        setErrors({});

        try {
            const payload: Record<string, unknown> = {
                role: formData.role,
            };

            if (formData.division_id && formData.division_id !== 'none') {
                payload.division_id = Number(formData.division_id);
            } else {
                payload.division_id = null;
            }
            if (formData.department_id && formData.department_id !== 'none') {
                payload.department_id = Number(formData.department_id);
            } else {
                payload.department_id = null;
            }
            if (formData.team_id && formData.team_id !== 'none') {
                payload.team_id = Number(formData.team_id);
            } else {
                payload.team_id = null;
            }

            await ensureCsrfCookie();

            const url = membersApi.roles.update.url({ company: companySlug, member: member.id });
            const response = await apiRequest(url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json();

            if (!response.ok) {
                if (response.status === 422) {
                    setErrors(data.errors || { message: data.message || '驗證失敗' });
                } else {
                    setErrors({ message: data.message || '更新失敗' });
                }
                setLoading(false);
                return;
            }

            toast.success('成員角色已更新！');
            onOpenChange(false);
            onSuccess();
        } catch (error) {
            console.error('Error updating member role:', error);
            setErrors({ message: '更新失敗，請稍後再試' });
        } finally {
            setLoading(false);
        }
    };

    const availableDepartments = organization.departments.filter(
        (d) => d.is_active && (!formData.division_id || d.division_id === Number(formData.division_id)),
    );

    const availableTeams = organization.teams.filter(
        (t) =>
            t.is_active &&
            (!formData.department_id || t.department_id === Number(formData.department_id)) &&
            (!formData.division_id || t.division_id === Number(formData.division_id)),
    );

    const requiresDivision = formData.role === 'division_lead';
    const requiresDepartment = formData.role === 'department_manager';
    const requiresTeam = formData.role === 'team_lead';

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>編輯成員角色</DialogTitle>
                    <DialogDescription>
                        調整 {member.name} 的角色與組織層級
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="edit-role">
                            角色 <span className="text-destructive">*</span>
                        </Label>
                        <Select
                            value={formData.role}
                            onValueChange={(value) => {
                                setFormData({
                                    ...formData,
                                    role: value,
                                    division_id: requiresDivision && value !== 'division_lead' ? '' : formData.division_id,
                                    department_id: requiresDepartment && value !== 'department_manager' ? '' : formData.department_id,
                                    team_id: requiresTeam && value !== 'team_lead' ? '' : formData.team_id,
                                });
                            }}
                            disabled={loading}
                        >
                            <SelectTrigger id="edit-role">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {roles.map((role) => (
                                    <SelectItem key={role} value={role}>
                                        {ROLE_LABELS[role] || role}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.role} />
                    </div>

                    {organization.divisions.length > 0 && (
                        <div className="space-y-2">
                            <Label htmlFor="edit-division">
                                事業群
                                {requiresDivision && (
                                    <span className="text-destructive"> *</span>
                                )}
                            </Label>
                            <Select
                                value={formData.division_id || 'none'}
                                onValueChange={(value) => {
                                    setFormData({
                                        ...formData,
                                        division_id: value === 'none' ? '' : value,
                                        department_id: '',
                                        team_id: '',
                                    });
                                }}
                                disabled={loading}
                                required={requiresDivision}
                            >
                                <SelectTrigger id="edit-division">
                                    <SelectValue placeholder="選擇事業群" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">無</SelectItem>
                                    {organization.divisions
                                        .filter((d) => d.is_active)
                                        .map((division) => (
                                            <SelectItem key={division.id} value={division.id.toString()}>
                                                {division.name}
                                            </SelectItem>
                                        ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.division_id} />
                        </div>
                    )}

                    {availableDepartments.length > 0 && (
                        <div className="space-y-2">
                            <Label htmlFor="edit-department">
                                部門
                                {requiresDepartment && (
                                    <span className="text-destructive"> *</span>
                                )}
                            </Label>
                            <Select
                                value={formData.department_id || 'none'}
                                onValueChange={(value) => {
                                    setFormData({
                                        ...formData,
                                        department_id: value === 'none' ? '' : value,
                                        team_id: '',
                                    });
                                }}
                                disabled={loading || !formData.division_id}
                                required={requiresDepartment}
                            >
                                <SelectTrigger id="edit-department">
                                    <SelectValue placeholder="選擇部門" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">無</SelectItem>
                                    {availableDepartments.map((department) => (
                                        <SelectItem key={department.id} value={department.id.toString()}>
                                            {department.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.department_id} />
                        </div>
                    )}

                    {availableTeams.length > 0 && (
                        <div className="space-y-2">
                            <Label htmlFor="edit-team">
                                小組
                                {requiresTeam && (
                                    <span className="text-destructive"> *</span>
                                )}
                            </Label>
                            <Select
                                value={formData.team_id || 'none'}
                                onValueChange={(value) => {
                                    setFormData({ ...formData, team_id: value === 'none' ? '' : value });
                                }}
                                disabled={loading || !formData.department_id}
                                required={requiresTeam}
                            >
                                <SelectTrigger id="edit-team">
                                    <SelectValue placeholder="選擇小組" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">無</SelectItem>
                                    {availableTeams.map((team) => (
                                        <SelectItem key={team.id} value={team.id.toString()}>
                                            {team.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.team_id} />
                        </div>
                    )}

                    {errors.message && (
                        <div className="rounded-lg border border-destructive bg-destructive/10 p-3 text-sm text-destructive">
                            {errors.message}
                        </div>
                    )}

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                            disabled={loading}
                        >
                            取消
                        </Button>
                        <Button type="submit" disabled={loading}>
                            {loading ? '更新中...' : '儲存變更'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
