import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Copy, Check, Link as LinkIcon, RefreshCw } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

interface OrganizationInvitationSectionProps {
    companySlug: string;
    organizationId: number;
    organizationName: string;
    organizationType: 'division' | 'department' | 'team';
    invitationToken: string | null;
    invitationEnabled: boolean;
    onUpdate: () => void;
}

const API_BASE = '/api/v1';

export function OrganizationInvitationSection({
    companySlug,
    organizationId,
    organizationName,
    organizationType,
    invitationToken,
    invitationEnabled,
    onUpdate,
}: OrganizationInvitationSectionProps) {
    const [copied, setCopied] = useState(false);
    const [loading, setLoading] = useState(false);
    const [enabled, setEnabled] = useState(invitationEnabled);
    const [token, setToken] = useState(invitationToken);

    const getInvitationUrl = () => {
        if (!token) {
            return null;
        }
        const baseUrl = typeof window !== 'undefined' ? window.location.origin : '';
        return `${baseUrl}/app/${companySlug}/register/${token}/${organizationType}`;
    };

    const invitationUrl = getInvitationUrl();

    const handleCopy = async () => {
        if (!invitationUrl) {
            return;
        }
        try {
            await navigator.clipboard.writeText(invitationUrl);
            setCopied(true);
            toast.success('邀請連結已複製到剪貼簿');
            setTimeout(() => setCopied(false), 2000);
        } catch (err) {
            toast.error('複製失敗');
        }
    };

    const handleGenerate = async () => {
        setLoading(true);
        try {
            const response = await fetch(
                `${API_BASE}/${companySlug}/${organizationType}s/${organizationId}/invitation/generate`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    credentials: 'include',
                }
            );

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || '生成邀請連結失敗');
            }

            const data = await response.json();
            setToken(data.invitation_token);
            setEnabled(true);
            toast.success('邀請連結已生成');
            onUpdate();
        } catch (error) {
            toast.error(error instanceof Error ? error.message : '生成邀請連結失敗');
        } finally {
            setLoading(false);
        }
    };

    const handleToggle = async (checked: boolean) => {
        setLoading(true);
        try {
            const response = await fetch(
                `${API_BASE}/${companySlug}/${organizationType}s/${organizationId}/invitation/toggle`,
                {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    credentials: 'include',
                    body: JSON.stringify({ enabled: checked }),
                }
            );

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || '更新失敗');
            }

            const data = await response.json();
            setEnabled(data.invitation_enabled);
            if (data.invitation_url) {
                const url = new URL(data.invitation_url);
                const pathParts = url.pathname.split('/');
                const tokenFromUrl = pathParts[pathParts.length - 2];
                setToken(tokenFromUrl);
            }
            toast.success(checked ? '邀請連結已啟用' : '邀請連結已停用');
            onUpdate();
        } catch (error) {
            toast.error(error instanceof Error ? error.message : '更新失敗');
            setEnabled(!checked); // Revert on error
        } finally {
            setLoading(false);
        }
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2 text-lg">
                    <LinkIcon className="h-5 w-5" />
                    邀請連結
                </CardTitle>
                <CardDescription>
                    {organizationName} 的專屬邀請連結，使用此連結註冊的使用者將自動加入此層級
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                {!token ? (
                    <div className="space-y-4">
                        <p className="text-sm text-muted-foreground">
                            尚未生成邀請連結。點擊下方按鈕生成連結。
                        </p>
                        <Button onClick={handleGenerate} disabled={loading}>
                            {loading ? (
                                <>
                                    <RefreshCw className="mr-2 h-4 w-4 animate-spin" />
                                    生成中...
                                </>
                            ) : (
                                <>
                                    <LinkIcon className="mr-2 h-4 w-4" />
                                    生成邀請連結
                                </>
                            )}
                        </Button>
                    </div>
                ) : (
                    <div className="space-y-4">
                        <div className="flex items-center justify-between">
                            <Label htmlFor="invitation-enabled" className="text-sm font-medium">
                                啟用邀請連結
                            </Label>
                            <Checkbox
                                id="invitation-enabled"
                                checked={enabled}
                                onCheckedChange={(checked) => handleToggle(checked === true)}
                                disabled={loading}
                            />
                        </div>

                        {enabled && invitationUrl && (
                            <div className="space-y-2">
                                <Label className="text-sm font-medium">邀請連結 URL</Label>
                                <div className="flex items-center gap-2">
                                    <div className="flex-1 rounded-md border bg-muted px-3 py-2 text-sm font-mono text-muted-foreground break-all">
                                        {invitationUrl}
                                    </div>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        onClick={handleCopy}
                                        disabled={loading}
                                    >
                                        {copied ? (
                                            <Check className="h-4 w-4 text-emerald-600" />
                                        ) : (
                                            <Copy className="h-4 w-4" />
                                        )}
                                    </Button>
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    點擊複製按鈕將連結複製到剪貼簿，然後分享給要邀請的成員
                                </p>
                            </div>
                        )}

                        {!enabled && (
                            <p className="text-sm text-amber-600 dark:text-amber-400">
                                邀請連結已停用，使用此連結將無法註冊
                            </p>
                        )}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

