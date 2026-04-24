import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import settingsRoutes from '@/routes/tenant/settings';
import { router } from '@inertiajs/react';
import { TriangleAlert } from 'lucide-react';
import { useState } from 'react';

interface DissolveCompanyDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    companySlug: string;
    companyName: string;
}

export function DissolveCompanyDialog({
    open,
    onOpenChange,
    companySlug,
    companyName,
}: DissolveCompanyDialogProps) {
    const [submitting, setSubmitting] = useState(false);

    const confirm = () => {
        setSubmitting(true);
        router.delete(settingsRoutes.dissolve.url({ company: companySlug }), {
            onFinish: () => {
                setSubmitting(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <TriangleAlert className="size-5 text-destructive" />
                        關閉公司「{companyName}」
                    </DialogTitle>
                    <DialogDescription>
                        此操作無法撤銷，請確認您是公司唯一成員後再繼續。
                    </DialogDescription>
                </DialogHeader>

                <Alert variant="destructive">
                    <AlertDescription className="space-y-1">
                        <p>關閉公司後：</p>
                        <ul className="ml-4 list-disc space-y-1 text-sm">
                            <li>您的歷史週報將轉為個人週報</li>
                            <li>公司組織架構與設定將被刪除</li>
                            <li>您的帳號將切換為個人模式</li>
                        </ul>
                    </AlertDescription>
                </Alert>

                <DialogFooter>
                    <Button
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                        disabled={submitting}
                    >
                        取消
                    </Button>
                    <Button
                        variant="destructive"
                        onClick={confirm}
                        disabled={submitting}
                    >
                        {submitting ? '處理中…' : '確認關閉公司'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
