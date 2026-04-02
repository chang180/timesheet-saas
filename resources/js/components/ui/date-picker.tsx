import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { format, parseISO } from 'date-fns';
import { zhTW } from 'date-fns/locale';
import { CalendarIcon, X } from 'lucide-react';
import * as React from 'react';
import type { Matcher } from 'react-day-picker';

interface DatePickerProps {
    /** 當前選中的日期 (YYYY-MM-DD 格式字串) */
    value?: string | null;
    /** 日期變更回調 */
    onChange?: (date: string | null) => void;
    /** 最小可選日期 (YYYY-MM-DD 格式字串) */
    minDate?: string;
    /** 最大可選日期 (YYYY-MM-DD 格式字串) */
    maxDate?: string;
    /** 週範圍高亮 - 用於視覺化顯示整週 */
    weekRange?: {
        startDate: string;
        endDate: string;
    };
    /** Placeholder 文字 */
    placeholder?: string;
    /** 是否禁用 */
    disabled?: boolean;
    /** 自訂 className */
    className?: string;
    /** 輸入框 ID */
    id?: string;
    /** 假日日期（YYYY-MM-DD） */
    holidayDates?: string[];
    /** 補班日日期（YYYY-MM-DD） */
    workdayOverrideDates?: string[];
}

export function DatePicker({
    value,
    onChange,
    minDate,
    maxDate,
    weekRange,
    placeholder = '選擇日期',
    disabled = false,
    className,
    id,
    holidayDates = [],
    workdayOverrideDates = [],
}: DatePickerProps) {
    const [open, setOpen] = React.useState(false);

    // 將字串轉換為 Date 物件
    const selectedDate = value ? parseISO(value) : undefined;
    const minDateObj = minDate ? parseISO(minDate) : undefined;
    const maxDateObj = maxDate ? parseISO(maxDate) : undefined;
    const holidayDateObjs = holidayDates.map((date) => parseISO(date));
    const workdayOverrideDateObjs = workdayOverrideDates.map((date) => parseISO(date));
    
    // 計算當週所在的月份（用於顯示當週）
    const currentMonth = weekRange ? parseISO(weekRange.startDate) : (selectedDate || new Date());

    // 處理日期選擇
    const handleSelect = (date: Date | undefined) => {
        if (date) {
            onChange?.(format(date, 'yyyy-MM-dd'));
        } else {
            onChange?.(null);
        }
        setOpen(false);
    };

    // 處理清除
    const handleClear = (e: React.MouseEvent) => {
        e.stopPropagation();
        onChange?.(null);
    };

    // 建立禁用日期的 matcher
    const disabledMatcher: Matcher[] = [];

    if (minDateObj) {
        disabledMatcher.push({ before: minDateObj });
    }

    if (maxDateObj) {
        disabledMatcher.push({ after: maxDateObj });
    }

    // 週範圍高亮的 modifiers
    const modifiers: Record<string, Matcher> = {};
    const modifiersClassNames: Record<string, string> = {};

    if (weekRange) {
        const weekStartDate = parseISO(weekRange.startDate);
        const weekEndDate = parseISO(weekRange.endDate);

        modifiers.weekRange = {
            from: weekStartDate,
            to: weekEndDate,
        };

        modifiersClassNames.weekRange = cn(
            '!bg-blue-100/90 !text-blue-900 hover:!bg-blue-200 !font-semibold',
            'dark:!bg-blue-900/60 dark:!text-blue-100 dark:hover:!bg-blue-800/70',
            '!ring-2 !ring-blue-400/40 dark:!ring-blue-500/50'
        );
    }

    if (holidayDateObjs.length > 0) {
        modifiers.holiday = holidayDateObjs;
        modifiersClassNames.holiday = cn(
            '!bg-rose-50 !text-rose-700 hover:!bg-rose-100 !font-semibold',
            'dark:!bg-rose-950/40 dark:!text-rose-300 dark:hover:!bg-rose-900/50',
            '!ring-1 !ring-rose-300/70 dark:!ring-rose-700/70',
        );
    }

    if (workdayOverrideDateObjs.length > 0) {
        modifiers.workdayOverride = workdayOverrideDateObjs;
        modifiersClassNames.workdayOverride = cn(
            '!bg-emerald-50 !text-emerald-700 hover:!bg-emerald-100 !font-semibold',
            'dark:!bg-emerald-950/40 dark:!text-emerald-300 dark:hover:!bg-emerald-900/50',
            '!ring-1 !ring-emerald-300/70 dark:!ring-emerald-700/70',
        );
    }

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <button
                    id={id}
                    type="button"
                    disabled={disabled}
                    className={cn(
                        'flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background',
                        'hover:bg-accent hover:text-accent-foreground',
                        'focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2',
                        'disabled:cursor-not-allowed disabled:opacity-50',
                        !value && 'text-muted-foreground',
                        className,
                    )}
                >
                    <span className="flex items-center gap-2">
                        <CalendarIcon className="h-4 w-4" />
                        {value ? format(selectedDate!, 'yyyy-MM-dd', { locale: zhTW }) : placeholder}
                    </span>
                    {value && !disabled && (
                        <X
                            className="h-4 w-4 opacity-50 hover:opacity-100"
                            onClick={handleClear}
                        />
                    )}
                </button>
            </PopoverTrigger>
            <PopoverContent className="w-auto p-0" align="start">
                <Calendar
                    mode="single"
                    selected={selectedDate}
                    onSelect={handleSelect}
                    disabled={disabledMatcher}
                    modifiers={modifiers}
                    modifiersClassNames={modifiersClassNames}
                    defaultMonth={currentMonth}
                    initialFocus
                />
            </PopoverContent>
        </Popover>
    );
}
