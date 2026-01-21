import * as React from 'react';
import { DayPicker, type DayPickerProps } from 'react-day-picker';

import { cn } from '@/lib/utils';

export type CalendarProps = DayPickerProps;

function Calendar({ className, classNames, showOutsideDays = false, ...props }: CalendarProps) {
    return (
        <DayPicker
            showOutsideDays={showOutsideDays}
            weekStartsOn={1}
            className={cn('p-3', className)}
            classNames={{
                // Container elements (react-day-picker v9 API)
                months: 'flex flex-col sm:flex-row gap-4',
                month: 'space-y-3',
                month_caption: 'hidden',
                caption_label: 'hidden',
                nav: 'hidden',
                button_previous: 'hidden',
                button_next: 'hidden',
                month_grid: 'w-full border-collapse',
                weekdays: 'flex gap-2',
                weekday: 'text-center text-muted-foreground w-12 min-w-12 max-w-12 h-8 shrink-0 font-semibold text-xs leading-8 dark:text-slate-400',
                weeks: '',
                week: 'flex w-full gap-2 mt-1.5',
                day: 'h-12 w-12 min-w-12 max-w-12 text-center text-sm p-0 relative shrink-0',
                day_button: cn(
                    'h-12 w-12 p-0 font-normal',
                    'inline-flex items-center justify-center rounded-md text-sm ring-offset-background transition-colors',
                    'hover:bg-accent hover:text-accent-foreground',
                    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
                    'disabled:pointer-events-none disabled:opacity-50',
                    'aria-disabled:pointer-events-none aria-disabled:opacity-40',
                ),
                // Selection states (react-day-picker v9 API)
                range_end: 'day-range-end',
                selected:
                    'bg-primary text-primary-foreground hover:bg-primary hover:text-primary-foreground focus:bg-primary focus:text-primary-foreground font-bold shadow-md dark:bg-blue-600 dark:text-white dark:shadow-blue-500/50',
                today: 'bg-accent text-accent-foreground font-semibold ring-2 ring-primary/30 dark:bg-slate-700 dark:text-white dark:ring-blue-400/50',
                outside: 'hidden',
                disabled: 'text-muted-foreground opacity-40 dark:opacity-30',
                range_middle: 'aria-selected:bg-accent aria-selected:text-accent-foreground',
                hidden: 'hidden',
                ...classNames,
            }}
            {...props}
        />
    );
}
Calendar.displayName = 'Calendar';

export { Calendar };
