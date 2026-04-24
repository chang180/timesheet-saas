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
import tenantMembers from '@/routes/tenant/members';
import { router } from '@inertiajs/react';
import { AlertTriangle } from 'lucide-react';
import { useState } from 'react';

type Member = {
    id: number;
    name: string;
    email: string;
    role: string;
};

interface MemberRemoveDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    member: Member;
    companySlug: string;
    onSuccess: () => void;
}

export function MemberRemoveDialog({
    open,
    onOpenChange,
    member,
    companySlug,
    onSuccess,
}: MemberRemoveDialogProps) {
    const [submitting, setSubmitting] = useState(false);

    const confirm = () => {
        setSubmitting(true);
        router.delete(
            tenantMembers.destroy.url({
                company: companySlug,
                member: member.id,
            }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    onSuccess();
                },
                onFinish: () => {
                    setSubmitting(false);
                },
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <AlertTriangle className="size-5 text-amber-600" />
                        移出成員「{member.name}」
                    </DialogTitle>
                    <DialogDescription>
                        該成員將失去公司存取權，但其過往週報仍會保留在公司紀錄。
                    </DialogDescription>
                </DialogHeader>

                <Alert variant="default">
                    <AlertDescription>
                        移出後，該用戶將轉為個人帳號，可繼續使用個人週報功能。
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
                        {submitting ? '處理中…' : '確認移出'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
