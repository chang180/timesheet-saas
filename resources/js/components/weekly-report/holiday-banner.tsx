import { ExternalLink } from 'lucide-react';

interface HolidayBannerProps {
    holidayCalendar:
        | {
              source: {
                  provider: string;
                  name: string;
                  dataset_url: string;
              };
          }
        | null
        | undefined;
}

export function HolidayBanner({ holidayCalendar }: HolidayBannerProps) {
    if (!holidayCalendar) {
        return null;
    }

    return (
        <div className="rounded-xl border-2 border-slate-200 bg-linear-to-r from-slate-50 to-white p-4 shadow-sm dark:border-slate-700 dark:from-slate-900 dark:to-slate-950">
            <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p className="text-sm font-semibold text-foreground">
                        假日資料已串接政府行事曆
                    </p>
                    <p className="mt-1 text-sm text-muted-foreground">
                        來源：{holidayCalendar.source.provider}／
                        {holidayCalendar.source.name}
                    </p>
                </div>
                <div className="flex flex-wrap items-center gap-2 text-xs font-medium">
                    <span className="rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-rose-700 dark:border-rose-800 dark:bg-rose-950/30 dark:text-rose-300">
                        紅色：假日
                    </span>
                    <span className="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300">
                        綠色：補班日
                    </span>
                    <a
                        href={holidayCalendar.source.dataset_url}
                        target="_blank"
                        rel="noreferrer"
                        className="inline-flex items-center gap-1 rounded-full border border-slate-300 px-3 py-1 text-slate-700 transition-colors hover:border-slate-400 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
                    >
                        查看資料來源
                        <ExternalLink className="size-3.5" />
                    </a>
                </div>
            </div>
        </div>
    );
}
