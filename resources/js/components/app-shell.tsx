import { SidebarProvider } from '@/components/ui/sidebar';
import { useIsMobile } from '@/hooks/use-mobile';
import { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

interface AppShellProps {
    children: React.ReactNode;
    variant?: 'header' | 'sidebar';
}

export function AppShell({ children, variant = 'header' }: AppShellProps) {
    const isOpen = usePage<SharedData>().props.sidebarOpen;
    const isMobile = useIsMobile();
    const [isTablet, setIsTablet] = useState(false);

    useEffect(() => {
        // 檢查是否為平板尺寸（768px - 1024px）
        const checkTablet = () => {
            const width = window.innerWidth;
            setIsTablet(width >= 768 && width < 1024);
        };

        checkTablet();
        window.addEventListener('resize', checkTablet);
        return () => window.removeEventListener('resize', checkTablet);
    }, []);

    if (variant === 'header') {
        return (
            <div className="flex min-h-screen w-full flex-col">{children}</div>
        );
    }

    // 在行動裝置和平板上預設收合 sidebar
    const shouldDefaultOpen = isOpen && !isMobile && !isTablet;

    return <SidebarProvider defaultOpen={shouldDefaultOpen}>{children}</SidebarProvider>;
}
