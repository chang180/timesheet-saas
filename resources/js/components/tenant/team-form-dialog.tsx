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
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/input-error';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useState, useEffect } from 'react';
import teamsApi from '@/routes/api/v1/tenant/teams';
import { toast } from 'sonner';

type Organization = {
    divisions: Array<{ id: number; name: string; slug: string; is_active: boolean }>;
    departments: Array<{ id: number; division_id: number | null; name: string; slug: string; is_active: boolean }>;
    teams: Array<{ id: number; division_id: number | null; department_id: number | null; name: string; slug: string; is_active: boolean }>;
};

interface TeamFormDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    companySlug: string;
    team: {
        id: number;
        division_id: number | null;
        department_id: number | null;
        name: string;
        slug: string;
        description: string | null;
        sort_order: number;
        is_active: boolean;
    } | null;
    selectedDepartmentId: number | null;
    selectedDivisionId: number | null;
    organization: Organization;
    onSuccess: () => void;
}

export function TeamFormDialog({
    open,
    onOpenChange,
    companySlug,
    team,
    selectedDepartmentId,
    selectedDivisionId,
    organization,
    onSuccess,
}: TeamFormDialogProps) {
    const [formData, setFormData] = useState({
        division_id: '',
        department_id: '',
        name: '',
        slug: '',
        description: '',
        sort_order: 0,
        is_active: true,
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        if (open) {
            if (team) {
                setFormData({
                    division_id: team.division_id?.toString() ?? '',
                    department_id: team.department_id?.toString() ?? '',
                    name: team.name,
                    slug: team.slug,
                    description: team.description ?? '',
                    sort_order: team.sort_order,
                    is_active: team.is_active,
                });
            } else {
                setFormData({
                    division_id: selectedDivisionId?.toString() ?? '',
                    department_id: selectedDepartmentId?.toString() ?? '',
                    name: '',
                    slug: '',
                    description: '',
                    sort_order: 0,
                    is_active: true,
                });
            }
            setErrors({});
        }
    }, [open, team, selectedDepartmentId, selectedDivisionId]);

    const availableDepartments = organization.departments.filter(
        (d) => d.is_active && (!formData.division_id || d.division_id === Number(formData.division_id)),
    );

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);
        setErrors({});

        try {
            const payload: Record<string, unknown> = {
                name: formData.name,
                slug: formData.slug || undefined,
                description: formData.description || undefined,
                sort_order: formData.sort_order,
                is_active: formData.is_active,
            };

            if (formData.division_id) {
                payload.division_id = Number(formData.division_id);
            }
            if (formData.department_id) {
                payload.department_id = Number(formData.department_id);
            }

            const url = team
                ? teamsApi.update.url({ company: companySlug, team: team.id })
                : teamsApi.store.url({ company: companySlug });
            const method = team ? 'PATCH' : 'POST';

            const response = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'include',
                body: JSON.stringify(payload),
            });

            const data = await response.json();

            if (!response.ok) {
                if (response.status === 422) {
                    setErrors(data.errors || { message: data.message || '驗證失敗' });
                } else {
                    setErrors({ message: data.message || '操作失敗' });
                }
                setLoading(false);
                return;
            }

            toast.success(team ? '小組已更新' : '小組已建立');
            onOpenChange(false);
            onSuccess();
        } catch (error) {
            console.error('Error saving team:', error);
            setErrors({ message: '操作失敗，請稍後再試' });
        } finally {
            setLoading(false);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{team ? '編輯小組' : '新增小組'}</DialogTitle>
                    <DialogDescription>
                        {team ? '修改小組資訊' : '建立新的小組'}
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleSubmit} className="space-y-4">
                    {organization.divisions.length > 0 && (
                        <div className="space-y-2">
                            <Label htmlFor="team-division">事業群</Label>
                            <Select
                                value={formData.division_id}
                                onValueChange={(value) => {
                                    setFormData({
                                        ...formData,
                                        division_id: value,
                                        department_id: '',
                                    });
                                }}
                                disabled={loading}
                            >
                                <SelectTrigger id="team-division">
                                    <SelectValue placeholder="選擇事業群" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">無</SelectItem>
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
                            <Label htmlFor="team-department">部門</Label>
                            <Select
                                value={formData.department_id}
                                onValueChange={(value) => setFormData({ ...formData, department_id: value })}
                                disabled={loading || !formData.division_id}
                            >
                                <SelectTrigger id="team-department">
                                    <SelectValue placeholder="選擇部門" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">無</SelectItem>
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

                    <div className="space-y-2">
                        <Label htmlFor="team-name">
                            名稱 <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="team-name"
                            value={formData.name}
                            onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                            required
                            disabled={loading}
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="team-slug">Slug</Label>
                        <Input
                            id="team-slug"
                            value={formData.slug}
                            onChange={(e) => setFormData({ ...formData, slug: e.target.value })}
                            disabled={loading || !!team}
                            placeholder="自動產生"
                        />
                        <InputError message={errors.slug} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="team-description">描述</Label>
                        <Textarea
                            id="team-description"
                            value={formData.description}
                            onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                            disabled={loading}
                            rows={3}
                        />
                        <InputError message={errors.description} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="team-sort-order">排序順序</Label>
                        <Input
                            id="team-sort-order"
                            type="number"
                            min="0"
                            value={formData.sort_order}
                            onChange={(e) => setFormData({ ...formData, sort_order: Number(e.target.value) })}
                            disabled={loading}
                        />
                        <InputError message={errors.sort_order} />
                    </div>

                    <div className="flex items-center space-x-2">
                        <Checkbox
                            id="team-is-active"
                            checked={formData.is_active}
                            onCheckedChange={(checked) =>
                                setFormData({ ...formData, is_active: checked === true })
                            }
                            disabled={loading}
                        />
                        <Label htmlFor="team-is-active" className="cursor-pointer">
                            啟用
                        </Label>
                    </div>

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
                            {loading ? '儲存中...' : '儲存'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
