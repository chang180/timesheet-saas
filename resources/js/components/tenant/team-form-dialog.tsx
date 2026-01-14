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
import { useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import tenantRoutes from '@/routes/tenant';
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
}

export function TeamFormDialog({
    open,
    onOpenChange,
    companySlug,
    team,
    selectedDepartmentId,
    selectedDivisionId,
    organization,
}: TeamFormDialogProps) {
    const form = useForm({
        division_id: 'none',
        department_id: 'none',
        name: '',
        slug: '',
        description: '',
        sort_order: 0,
        is_active: true,
    });

    useEffect(() => {
        if (open) {
            if (team) {
                form.setData({
                    division_id: team.division_id?.toString() ?? 'none',
                    department_id: team.department_id?.toString() ?? 'none',
                    name: team.name,
                    slug: team.slug,
                    description: team.description ?? '',
                    sort_order: team.sort_order,
                    is_active: team.is_active,
                });
            } else {
                form.reset();
                form.setData({
                    division_id: selectedDivisionId?.toString() ?? 'none',
                    department_id: selectedDepartmentId?.toString() ?? 'none',
                    name: '',
                    slug: '',
                    description: '',
                    sort_order: 0,
                    is_active: true,
                });
            }
            form.clearErrors();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, team, selectedDepartmentId, selectedDivisionId]);

    const availableDepartments = organization.departments.filter(
        (d) => d.is_active && (form.data.division_id === 'none' || !form.data.division_id || d.division_id === Number(form.data.division_id)),
    );

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        // Transform form data before submission
        const submitData = {
            name: form.data.name,
            slug: form.data.slug || undefined,
            description: form.data.description || undefined,
            sort_order: form.data.sort_order,
            is_active: form.data.is_active,
            division_id: form.data.division_id && form.data.division_id !== 'none' ? Number(form.data.division_id) : undefined,
            department_id: form.data.department_id && form.data.department_id !== 'none' ? Number(form.data.department_id) : undefined,
        };

        form.transform(() => submitData);

        if (team) {
            form.patch(tenantRoutes.teams.update.url({ company: companySlug, team: team.id }), {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('小組已更新');
                    onOpenChange(false);
                    // Inertia 會自動重新載入頁面，不需要手動調用 onSuccess
                },
            });
        } else {
            form.post(tenantRoutes.teams.store.url({ company: companySlug }), {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('小組已建立');
                    onOpenChange(false);
                    // Inertia 會自動重新載入頁面，不需要手動調用 onSuccess
                },
            });
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
                                value={form.data.division_id}
                                onValueChange={(value) => {
                                    form.setData({
                                        division_id: value,
                                        department_id: 'none',
                                    });
                                }}
                                disabled={form.processing}
                            >
                                <SelectTrigger id="team-division">
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
                            <InputError message={form.errors.division_id} />
                        </div>
                    )}

                    {availableDepartments.length > 0 && (
                        <div className="space-y-2">
                            <Label htmlFor="team-department">部門</Label>
                            <Select
                                value={form.data.department_id}
                                onValueChange={(value) => form.setData('department_id', value)}
                                disabled={form.processing || !form.data.division_id || form.data.division_id === 'none'}
                            >
                                <SelectTrigger id="team-department">
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
                            <InputError message={form.errors.department_id} />
                        </div>
                    )}

                    <div className="space-y-2">
                        <Label htmlFor="team-name">
                            名稱 <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="team-name"
                            value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)}
                            required
                            disabled={form.processing}
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="team-slug">Slug</Label>
                        <Input
                            id="team-slug"
                            value={form.data.slug}
                            onChange={(e) => form.setData('slug', e.target.value)}
                            disabled={form.processing || !!team}
                            placeholder="自動產生"
                        />
                        <InputError message={form.errors.slug} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="team-description">描述</Label>
                        <Textarea
                            id="team-description"
                            value={form.data.description}
                            onChange={(e) => form.setData('description', e.target.value)}
                            disabled={form.processing}
                            rows={3}
                        />
                        <InputError message={form.errors.description} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="team-sort-order">排序順序</Label>
                        <Input
                            id="team-sort-order"
                            type="number"
                            min="0"
                            value={form.data.sort_order}
                            onChange={(e) => form.setData('sort_order', Number(e.target.value))}
                            disabled={form.processing}
                        />
                        <InputError message={form.errors.sort_order} />
                    </div>

                    <div className="flex items-center space-x-2">
                        <Checkbox
                            id="team-is-active"
                            checked={form.data.is_active}
                            onCheckedChange={(checked) =>
                                form.setData('is_active', checked === true)
                            }
                            disabled={form.processing}
                        />
                        <Label htmlFor="team-is-active" className="cursor-pointer">
                            啟用
                        </Label>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                            disabled={form.processing}
                        >
                            取消
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? '儲存中...' : '儲存'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
