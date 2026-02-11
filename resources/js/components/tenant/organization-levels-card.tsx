import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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
import { Building2, AlertTriangle } from 'lucide-react';
import { useForm } from '@inertiajs/react';
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

function levelHasData(
    level: string,
    org: OrganizationLevelsCardProps['organization']
): boolean {
    if (level === 'division') return org.divisions.length > 0;
    if (level === 'department') return org.departments.length > 0;
    if (level === 'team') return org.teams.length > 0;
    return false;
}

export function OrganizationLevelsCard({
    companySlug,
    initialLevels,
    organization,
}: OrganizationLevelsCardProps) {
    const [selectedLevels, setSelectedLevels] = useState<string[]>(initialLevels);
    const [forceRemoveConfirmOpen, setForceRemoveConfirmOpen] = useState(false);
    const [pendingForceRemoveLevels, setPendingForceRemoveLevels] = useState<string[]>([]);

    const form = useForm<{
        organization_levels: string[];
    }>({
        organization_levels: initialLevels,
    });

    const { errors, processing, recentlySuccessful } = form;

    const handleLevelToggle = (level: string, checked: boolean) => {
        const newLevels = checked
            ? [...selectedLevels, level]
            : selectedLevels.filter((l) => l !== level);
        setSelectedLevels(newLevels);
        form.setData('organization_levels', newLevels);
    };

    const getRemovedLevels = (): string[] =>
        initialLevels.filter((l) => !form.data.organization_levels.includes(l));

    const getRemovedLevelsWithData = (): string[] => {
        const removed = getRemovedLevels();
        return removed.filter((l) => levelHasData(l, organization));
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const levelsToForceRemove = getRemovedLevelsWithData();

        if (levelsToForceRemove.length > 0) {
            setPendingForceRemoveLevels(levelsToForceRemove);
            setForceRemoveConfirmOpen(true);
            return;
        }

        submitPatch();
    };

    const submitPatch = (forceRemoveLevels: string[] = []) => {
        if (forceRemoveLevels.length > 0) {
            form.transform((data) => ({
                ...data,
                force_remove_levels: forceRemoveLevels,
            }));
        }
        form.patch(`/app/${companySlug}/settings/organization-levels`, {
            preserveScroll: true,
            onSuccess: () => {
                form.transform((data) => data);
                setForceRemoveConfirmOpen(false);
                setPendingForceRemoveLevels([]);
            },
            onError: (errors) => {
                const msg = errors.organization_levels as string | undefined;
                if (msg && /無法移除.*層級.*已有.*資料/.test(msg)) {
                    setPendingForceRemoveLevels(getRemovedLevels());
                    setForceRemoveConfirmOpen(true);
                }
            },
        });
    };

    const handleConfirmForceRemove = () => {
        submitPatch(pendingForceRemoveLevels);
    };

    return (
        <>
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
                                        disabled={processing}
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
                                                    此層級已有資料。儲存時將移除該層級並清空相關資料（成員歸屬與週報歸屬會一併清除）。
                                                </span>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    <InputError message={errors.organization_levels} />

                    <div className="flex items-center gap-4">
                        <Button type="submit" disabled={processing}>
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

        <Dialog open={forceRemoveConfirmOpen} onOpenChange={setForceRemoveConfirmOpen}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>確認強制移除組織層級</DialogTitle>
                    <DialogDescription>
                        以下層級目前有資料，移除後將刪除該層級的所有資料，並清除成員與週報的歸屬設定，此操作無法復原。確定要繼續嗎？
                        {pendingForceRemoveLevels.length > 0 && (
                            <span className="mt-2 block font-medium text-foreground">
                                {pendingForceRemoveLevels.map((l) => LEVEL_LABELS[l]).join('、')}
                            </span>
                        )}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => setForceRemoveConfirmOpen(false)}
                    >
                        取消
                    </Button>
                    <Button type="button" variant="destructive" onClick={handleConfirmForceRemove}>
                        確認強制移除並儲存
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
        </>
    );
}

