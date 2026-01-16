import AppLogoIcon from './app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-linear-to-br from-indigo-500 via-sky-500 to-emerald-500 text-white shadow-sm">
                <AppLogoIcon className="size-5 fill-current text-white" />
            </div>
            <div className="ml-2 grid flex-1 text-left text-sm leading-tight">
                <span className="font-semibold text-foreground">
                    週報通 Timesheet SaaS
                </span>
                <span className="text-xs text-muted-foreground">
                    Multi-tenant weekly reporting
                </span>
            </div>
        </>
    );
}
