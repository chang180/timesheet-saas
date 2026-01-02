import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import { Building2, AlertTriangle } from 'lucide-react';
import { useForm, router } from '@inertiajs/react';
import { useState } from 'react';

interface OrganizationLevelsCardProps {
    companySlug: string;
    initialLevels: string[];
    organization: {
        divisions: Array<{ id: number }>;
        departments: Array<{ id: number }>;
        teams: Array<{ id: number }>;
    };
}

const LEVEL_LABELS: Record<string, string> = {
    division: '事業群',
    department: '部門',
    team: '小組',
};

export function OrganizationLevelsCard({
    companySlug,
    initialLevels,
    organization,
}: OrganizationLevelsCardProps) {
    const [selectedLevels, setSelectedLevels] = useState<string[]>(initialLevels);
    const [hasDataWarning, setHasDataWarning] = useState<string | null>(null);

    const form = useForm<{
        organization_levels: string[];
    }>({
        organization_levels: initialLevels,
    });

    const { data, errors, processing, recentlySuccessful } = form;

    const handleLevelToggle = (level: string, checked: boolean) => {
        const newLevels = checked
            ? [...selectedLevels, level]
            : selectedLevels.filter((l) => l !== level);

        // Check if removing a level that has data
        if (!checked) {
            if (level === 'division' && organization.divisions.length > 0) {
                setHasDataWarning('無法移除「事業群」層級，因為已有事業群資料。');
                return;
            }
            if (level === 'department' && organization.departments.length > 0) {
                setHasDataWarning('無法移除「部門」層級，因為已有部門資料。');
                return;
            }
            if (level === 'team' && organization.teams.length > 0) {
                setHasDataWarning('無法移除「小組」層級，因為已有小組資料。');
                return;
            }
        }

        setHasDataWarning(null);
        setSelectedLevels(newLevels);
        form.setData('organization_levels', newLevels);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (hasDataWarning) {
            return;
        }

        router.put(`/api/v1/${companySlug}/settings/organization-levels`, data, {
            preserveScroll: true,
            onError: () => {
                setHasDataWarning(null);
            },
        });
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Building2 className="h-5 w-5" />
                    組織層級設定
                </CardTitle>
                <CardDescription>
                    選擇您的公司要使用的組織層級結構。移除層級前請先處理該層級下的所有資料。
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="space-y-4">
                        {(['division', 'department', 'team'] as const).map((level) => {
                            const isChecked = selectedLevels.includes(level);
                            const hasData =
                                (level === 'division' && organization.divisions.length > 0) ||
                                (level === 'department' && organization.departments.length > 0) ||
                                (level === 'team' && organization.teams.length > 0);

                            return (
                                <div
                                    key={level}
                                    className="flex items-start space-x-3 rounded-lg border p-4"
                                >
                                    <Checkbox
                                        id={level}
                                        checked={isChecked}
                                        onCheckedChange={(checked) =>
                                            handleLevelToggle(level, checked === true)
                                        }
                                        disabled={processing || (hasData && !isChecked)}
                                    />
                                    <div className="flex-1 space-y-1">
                                        <Label
                                            htmlFor={level}
                                            className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                        >
                                            {LEVEL_LABELS[level]}
                                        </Label>
                                        <p className="text-xs text-muted-foreground">
                                            {level === 'division' &&
                                                '最高層級，可包含多個部門'}
                                            {level === 'department' &&
                                                '中間層級，可包含多個小組'}
                                            {level === 'team' &&
                                                '最底層級，直接管理成員'}
                                        </p>
                                        {hasData && !isChecked && (
                                            <div className="flex items-center gap-2 text-xs text-amber-600 dark:text-amber-400">
                                                <AlertTriangle className="h-4 w-4" />
                                                <span>
                                                    此層級已有資料，無法移除。請先處理或刪除該層級下的所有資料。
                                                </span>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    {hasDataWarning && (
                        <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950">
                            <div className="flex items-center gap-2 text-sm text-amber-800 dark:text-amber-200">
                                <AlertTriangle className="h-4 w-4" />
                                <span>{hasDataWarning}</span>
                            </div>
                        </div>
                    )}

                    <InputError message={errors.organization_levels} />

                    <div className="flex items-center gap-4">
                        <Button type="submit" disabled={processing || hasDataWarning !== null}>
                            儲存層級設定
                        </Button>
                        {recentlySuccessful && (
                            <span className="text-sm text-emerald-600 dark:text-emerald-400">
                                已儲存！
                            </span>
                        )}
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}

