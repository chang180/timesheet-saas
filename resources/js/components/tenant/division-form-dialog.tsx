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
import { useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import tenantRoutes from '@/routes/tenant';
import { toast } from 'sonner';

interface DivisionFormDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    companySlug: string;
    division: {
        id: number;
        name: string;
        slug: string;
        description: string | null;
        sort_order: number;
        is_active: boolean;
    } | null;
}

export function DivisionFormDialog({
    open,
    onOpenChange,
    companySlug,
    division,
}: DivisionFormDialogProps) {
    const form = useForm({
        name: '',
        slug: '',
        description: '',
        sort_order: 0,
        is_active: true,
    });

    useEffect(() => {
        if (open) {
            if (division) {
                form.setData({
                    name: division.name,
                    slug: division.slug,
                    description: division.description ?? '',
                    sort_order: division.sort_order,
                    is_active: division.is_active,
                });
            } else {
                form.reset();
                form.setData({
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
    }, [open, division]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (division) {
            form.patch(tenantRoutes.divisions.update.url({ company: companySlug, division: division.id }), {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('事業群已更新');
                    onOpenChange(false);
                    // Inertia 會自動重新載入頁面，不需要手動調用 onSuccess
                },
            });
        } else {
            form.post(tenantRoutes.divisions.store.url({ company: companySlug }), {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('事業群已建立');
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
                    <DialogTitle>{division ? '編輯事業群' : '新增事業群'}</DialogTitle>
                    <DialogDescription>
                        {division ? '修改事業群資訊' : '建立新的事業群'}
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="division-name">
                            名稱 <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="division-name"
                            value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)}
                            required
                            disabled={form.processing}
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="division-slug">Slug</Label>
                        <Input
                            id="division-slug"
                            value={form.data.slug}
                            onChange={(e) => form.setData('slug', e.target.value)}
                            disabled={form.processing || !!division}
                            placeholder="自動產生"
                        />
                        <InputError message={form.errors.slug} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="division-description">描述</Label>
                        <Textarea
                            id="division-description"
                            value={form.data.description}
                            onChange={(e) => form.setData('description', e.target.value)}
                            disabled={form.processing}
                            rows={3}
                        />
                        <InputError message={form.errors.description} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="division-sort-order">排序順序</Label>
                        <Input
                            id="division-sort-order"
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
                            id="division-is-active"
                            checked={form.data.is_active}
                            onCheckedChange={(checked) =>
                                form.setData('is_active', checked === true)
                            }
                            disabled={form.processing}
                        />
                        <Label htmlFor="division-is-active" className="cursor-pointer">
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
