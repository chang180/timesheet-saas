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
import { useState, useEffect } from 'react';
import divisionsApi from '@/routes/api/v1/tenant/divisions';
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
    onSuccess: () => void;
}

export function DivisionFormDialog({
    open,
    onOpenChange,
    companySlug,
    division,
    onSuccess,
}: DivisionFormDialogProps) {
    const [formData, setFormData] = useState({
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
            if (division) {
                setFormData({
                    name: division.name,
                    slug: division.slug,
                    description: division.description ?? '',
                    sort_order: division.sort_order,
                    is_active: division.is_active,
                });
            } else {
                setFormData({
                    name: '',
                    slug: '',
                    description: '',
                    sort_order: 0,
                    is_active: true,
                });
            }
            setErrors({});
        }
    }, [open, division]);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);
        setErrors({});

        try {
            const url = division
                ? divisionsApi.update.url({ company: companySlug, division: division.id })
                : divisionsApi.store.url({ company: companySlug });
            const method = division ? 'PATCH' : 'POST';

            const response = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'include',
                body: JSON.stringify(formData),
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

            toast.success(division ? '事業群已更新' : '事業群已建立');
            onOpenChange(false);
            onSuccess();
        } catch (error) {
            console.error('Error saving division:', error);
            setErrors({ message: '操作失敗，請稍後再試' });
        } finally {
            setLoading(false);
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
                            value={formData.name}
                            onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                            required
                            disabled={loading}
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="division-slug">Slug</Label>
                        <Input
                            id="division-slug"
                            value={formData.slug}
                            onChange={(e) => setFormData({ ...formData, slug: e.target.value })}
                            disabled={loading || !!division}
                            placeholder="自動產生"
                        />
                        <InputError message={errors.slug} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="division-description">描述</Label>
                        <Textarea
                            id="division-description"
                            value={formData.description}
                            onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                            disabled={loading}
                            rows={3}
                        />
                        <InputError message={errors.description} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="division-sort-order">排序順序</Label>
                        <Input
                            id="division-sort-order"
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
                            id="division-is-active"
                            checked={formData.is_active}
                            onCheckedChange={(checked) =>
                                setFormData({ ...formData, is_active: checked === true })
                            }
                            disabled={loading}
                        />
                        <Label htmlFor="division-is-active" className="cursor-pointer">
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
