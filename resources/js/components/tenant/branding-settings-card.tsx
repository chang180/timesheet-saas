import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import { Palette, Image as ImageIcon } from 'lucide-react';
import { useForm } from '@inertiajs/react';
import settingsRoutes from '@/routes/tenant/settings';

interface BrandingSettingsCardProps {
    companySlug: string;
    initialBrandColor?: string | null;
    initialLogo?: string | null;
}

export function BrandingSettingsCard({
    companySlug,
    initialBrandColor,
    initialLogo,
}: BrandingSettingsCardProps) {
    const form = useForm<{
        branding: {
            color?: string | null;
            logo?: string | null;
        };
    }>({
        branding: {
            color: initialBrandColor ?? null,
            logo: initialLogo ?? null,
        },
    });

    const { data, errors, processing, recentlySuccessful } = form;

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        form.patch(settingsRoutes.branding.url({ company: companySlug }), {
            preserveScroll: true,
        });
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Palette className="h-5 w-5" />
                    品牌設定
                </CardTitle>
                <p className="text-sm text-muted-foreground">
                    設定公司品牌顏色與 Logo，將應用於歡迎頁與系統介面
                </p>
            </CardHeader>
            <CardContent>
                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid gap-6 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="brand-color">
                                品牌主色
                            </Label>
                            <div className="flex items-center gap-3">
                                <Input
                                    id="brand-color"
                                    type="color"
                                    value={data.branding.color ?? '#2563eb'}
                                    onChange={(e) =>
                                        form.setData('branding', {
                                            ...data.branding,
                                            color: e.target.value,
                                        })
                                    }
                                    className="h-12 w-20 cursor-pointer"
                                    disabled={processing}
                                />
                                <Input
                                    type="text"
                                    value={data.branding.color ?? ''}
                                    onChange={(e) =>
                                        form.setData('branding', {
                                            ...data.branding,
                                            color: e.target.value || null,
                                        })
                                    }
                                    placeholder="#2563eb"
                                    pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$"
                                    disabled={processing}
                                    className="flex-1"
                                />
                            </div>
                            <InputError message={errors['branding.color']} />
                            <p className="text-xs text-muted-foreground">
                                用於歡迎頁、按鈕與強調元素的顏色
                            </p>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="brand-logo">
                                Logo URL
                            </Label>
                            <div className="flex items-center gap-3">
                                {data.branding.logo && (
                                    <div className="h-12 w-12 rounded border border-border overflow-hidden bg-muted flex items-center justify-center">
                                        <ImageIcon className="h-6 w-6 text-muted-foreground" />
                                    </div>
                                )}
                                <Input
                                    id="brand-logo"
                                    type="url"
                                    value={data.branding.logo ?? ''}
                                    onChange={(e) =>
                                        form.setData('branding', {
                                            ...data.branding,
                                            logo: e.target.value || null,
                                        })
                                    }
                                    placeholder="https://example.com/logo.png"
                                    disabled={processing}
                                    className="flex-1"
                                />
                            </div>
                            <InputError message={errors['branding.logo']} />
                            <p className="text-xs text-muted-foreground">
                                建議尺寸：200x60px，支援 PNG、SVG 格式
                            </p>
                        </div>
                    </div>

                    <div className="flex items-center gap-4">
                        <Button type="submit" disabled={processing}>
                            儲存品牌設定
                        </Button>
                        {recentlySuccessful && (
                            <span className="text-sm text-emerald-600">
                                已儲存！
                            </span>
                        )}
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}
