import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useState } from 'react';
import membersApi from '@/routes/api/v1/tenant/members';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';
import { apiRequest, ensureCsrfCookie } from '@/lib/api-client';

type Organization = {
    divisions: Array<{ id: number; name: string; slug: string; is_active: boolean }>;
    departments: Array<{ id: number; division_id: number | null; name: string; slug: string; is_active: boolean }>;
    teams: Array<{ id: number; division_id: number | null; department_id: number | null; name: string; slug: string; is_active: boolean }>;
};

interface MemberInviteDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    companySlug: string;
    organization: Organization;
    roles: string[];
    currentUserCount: number;
    userLimit: number;
    onSuccess: () => void;
}

const ROLE_LABELS: Record<string, string> = {
    company_admin: '公司管理者',
    division_lead: '事業群主管',
    department_manager: '部門主管',
    team_lead: '小組主管',
    member: '一般成員',
};

export function MemberInviteDialog({
    open,
    onOpenChange,
    companySlug,
    organization,
    roles,
    currentUserCount,
    userLimit,
    onSuccess,
}: MemberInviteDialogProps) {
    const [formData, setFormData] = useState({
        name: '',
        email: '',
        role: 'member',
        division_id: '',
        department_id: '',
        team_id: '',
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [loading, setLoading] = useState(false);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);
        setErrors({});

        try {
            const payload: Record<string, unknown> = {
                name: formData.name,
                email: formData.email,
                role: formData.role,
            };

            if (formData.division_id && formData.division_id !== 'none') {
                payload.division_id = Number(formData.division_id);
            }
            if (formData.department_id && formData.department_id !== 'none') {
                payload.department_id = Number(formData.department_id);
            }
            if (formData.team_id && formData.team_id !== 'none') {
                payload.team_id = Number(formData.team_id);
            }

            await ensureCsrfCookie();

            const url = membersApi.invite.url({ company: companySlug });
            const response = await apiRequest(url, {
                method: 'POST',
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
                    setErrors({ message: data.message || '邀請失敗' });
                }
                setLoading(false);
                return;
            }

            toast.success('邀請已發送！');
            setFormData({
                name: '',
                email: '',
                role: 'member',
                division_id: '',
                department_id: '',
                team_id: '',
            });
            onOpenChange(false);
            onSuccess();
        } catch (error) {
            console.error('Error inviting member:', error);
            setErrors({ message: '邀請失敗，請稍後再試' });
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
            <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>邀請新成員</DialogTitle>
                    <DialogDescription>
                        邀請新成員加入團隊。邀請信將發送到指定的 Email 地址。
                        {currentUserCount >= userLimit && (
                            <span className="block mt-2 text-amber-600 dark:text-amber-400">
                                成員數已達上限（{currentUserCount}/{userLimit}）
                            </span>
                        )}
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="invite-name">
                            姓名 <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="invite-name"
                            value={formData.name}
                            onChange={(e) =>
                                setFormData({ ...formData, name: e.target.value })
                            }
                            required
                            disabled={loading || currentUserCount >= userLimit}
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="invite-email">
                            Email <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="invite-email"
                            type="email"
                            value={formData.email}
                            onChange={(e) =>
                                setFormData({ ...formData, email: e.target.value })
                            }
                            required
                            disabled={loading || currentUserCount >= userLimit}
                        />
                        <InputError message={errors.email} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="invite-role">
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
                            disabled={loading || currentUserCount >= userLimit}
                        >
                            <SelectTrigger id="invite-role">
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
                            <Label htmlFor="invite-division">
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
                                disabled={loading || currentUserCount >= userLimit}
                                required={requiresDivision}
                            >
                                <SelectTrigger id="invite-division">
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
                            <Label htmlFor="invite-department">
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
                                disabled={loading || currentUserCount >= userLimit || !formData.division_id}
                                required={requiresDepartment}
                            >
                                <SelectTrigger id="invite-department">
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
                            <Label htmlFor="invite-team">
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
                                disabled={loading || currentUserCount >= userLimit || !formData.department_id}
                                required={requiresTeam}
                            >
                                <SelectTrigger id="invite-team">
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
                        <Button type="submit" disabled={loading || currentUserCount >= userLimit}>
                            {loading ? '發送中...' : '發送邀請'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
