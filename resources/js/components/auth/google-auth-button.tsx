import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/react';
import { AlertCircle } from 'lucide-react';

interface GoogleAuthButtonProps {
    intent?: 'register' | 'login' | 'invitation' | 'organization_invitation';
    companySlug?: string;
    invitationToken?: string;
    organizationInvitation?: {
        companySlug: string;
        token: string;
        type: string;
    };
    className?: string;
    variant?: 'default' | 'outline' | 'ghost' | 'link' | 'destructive' | 'secondary';
    showWarning?: boolean;
}

export default function GoogleAuthButton({
    intent = 'register',
    companySlug,
    invitationToken,
    organizationInvitation,
    className = '',
    variant = 'outline',
    showWarning = true,
}: GoogleAuthButtonProps) {
    const handleClick = () => {
        const params: Record<string, string> = {
            intent,
        };

        if (companySlug) {
            params.company_slug = companySlug;
        }

        if (invitationToken) {
            params.invitation_token = invitationToken;
        }

        if (organizationInvitation) {
            params.organization_invitation = JSON.stringify(organizationInvitation);
        }

        // 構建 URL
        const url = new URL('/auth/google', window.location.origin);
        Object.entries(params).forEach(([key, value]) => {
            url.searchParams.append(key, value);
        });

        router.visit(url.toString());
    };

    const buttonText = {
        register: '使用 Google 註冊',
        login: '使用 Google 登入',
        invitation: '使用 Google 接受邀請',
        organization_invitation: '使用 Google 註冊',
    }[intent];

    return (
        <div className="space-y-2">
            <Button
                type="button"
                variant={variant}
                onClick={handleClick}
                className={`w-full gap-2 ${className}`}
            >
                <svg
                    className="h-5 w-5"
                    viewBox="0 0 24 24"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                        fill="#4285F4"
                    />
                    <path
                        d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                        fill="#34A853"
                    />
                    <path
                        d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                        fill="#FBBC05"
                    />
                    <path
                        d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                        fill="#EA4335"
                    />
                </svg>
                {buttonText}
            </Button>
            {showWarning && (
                <div className="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800 dark:border-amber-800 dark:bg-amber-500/10 dark:text-amber-400">
                    <AlertCircle className="mt-0.5 h-4 w-4 flex-shrink-0" />
                    <span>
                        請使用您自己的 Google 帳號，以免無法取回帳號
                    </span>
                </div>
            )}
        </div>
    );
}
