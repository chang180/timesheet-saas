import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Building2, Users, Link as LinkIcon, Copy, Check } from 'lucide-react';
import { useState } from 'react';
import tenantRoutes from '@/routes/tenant';

interface CompanyInfoCardProps {
    companyName: string;
    companySlug: string;
    maxUserLimit: number;
    currentUserCount: number;
}

export function CompanyInfoCard({
    companyName,
    companySlug,
    maxUserLimit,
    currentUserCount,
}: CompanyInfoCardProps) {
    const usagePercentage = (currentUserCount / maxUserLimit) * 100;
    const isNearLimit = usagePercentage >= 80;
    const [copied, setCopied] = useState(false);
    
    // 安全地獲取註冊 URL
    const registrationUrl = tenantRoutes?.register?.url 
        ? tenantRoutes.register.url({ company: companySlug })
        : `/app/${companySlug}/register`;
    const fullRegistrationUrl = typeof window !== 'undefined' 
        ? `${window.location.origin}${registrationUrl}`
        : registrationUrl;

    const handleCopy = async () => {
        try {
            await navigator.clipboard.writeText(fullRegistrationUrl);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch (err) {
            console.error('Failed to copy:', err);
        }
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Building2 className="h-5 w-5" />
                    公司基本資訊
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div className="grid gap-6 md:grid-cols-3">
                    <div className="space-y-2">
                        <div className="text-sm font-medium text-muted-foreground">
                            公司名稱
                        </div>
                        <div className="text-lg font-semibold">{companyName}</div>
                    </div>

                    <div className="space-y-2">
                        <div className="text-sm font-medium text-muted-foreground">
                            公司註冊網址
                        </div>
                        <div className="flex items-center gap-2">
                            <LinkIcon className="h-4 w-4 text-muted-foreground shrink-0" />
                            <code className="text-sm font-mono bg-muted px-2 py-1 rounded flex-1 truncate">
                                {fullRegistrationUrl}
                            </code>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                className="h-8 w-8 p-0 shrink-0"
                                onClick={handleCopy}
                                title="複製網址"
                            >
                                {copied ? (
                                    <Check className="h-4 w-4 text-emerald-600" />
                                ) : (
                                    <Copy className="h-4 w-4" />
                                )}
                            </Button>
                        </div>
                        <p className="text-xs text-muted-foreground">
                            分享此網址給團隊成員，他們可透過此頁面註冊並自動加入公司
                        </p>
                    </div>

                    <div className="space-y-2">
                        <div className="flex items-center justify-between">
                            <div className="text-sm font-medium text-muted-foreground">
                                成員數
                            </div>
                            {isNearLimit && (
                                <Badge variant="outline" className="text-xs border-amber-500 text-amber-700 dark:text-amber-400">
                                    接近上限
                                </Badge>
                            )}
                        </div>
                        <div className="space-y-1">
                            <div className="flex items-center gap-2">
                                <Users className="h-4 w-4 text-muted-foreground" />
                                <span className="text-lg font-semibold">
                                    {currentUserCount} / {maxUserLimit}
                                </span>
                            </div>
                            <div className="w-full bg-muted rounded-full h-2">
                                <div
                                    className={`h-2 rounded-full transition-all ${
                                        isNearLimit
                                            ? 'bg-amber-500'
                                            : 'bg-primary'
                                    }`}
                                    style={{ width: `${Math.min(usagePercentage, 100)}%` }}
                                />
                            </div>
                            <div className="text-xs text-muted-foreground">
                                使用率：{usagePercentage.toFixed(1)}%
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
