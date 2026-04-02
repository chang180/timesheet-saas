import { isWeekend } from '@/lib/date-utils';
import { eachDayOfInterval, format, parseISO } from 'date-fns';

import type { HolidayEntry } from '@/pages/weekly/components/types';

export type HolidayDateStatus = {
    tone: 'holiday' | 'makeup_workday' | 'weekend';
    label: string;
    detail: string;
};

export type WorkRangeStats = {
    workingDays: number;
    holidayDays: number;
    makeupWorkdays: number;
    availableHours: number;
    holidayNames: string[];
};

const CATEGORY_LABELS: Record<string, string> = {
    national: '國定假日',
    weekday_off: '放假日',
    makeup_workday: '補班日',
    weekend: '週末',
};

function getHolidayDisplayLabel(entry: HolidayEntry): string {
    if (entry.name) {
        return entry.name;
    }

    if (entry.note?.includes('補假')) {
        return '補假日';
    }

    if (entry.note?.includes('補行上班')) {
        return '補班日';
    }

    return CATEGORY_LABELS[entry.category] ?? '假日';
}

export function buildHolidayMap(
    holidays: HolidayEntry[],
): Map<string, HolidayEntry> {
    return new Map(holidays.map((entry) => [entry.date, entry]));
}

export function getHolidayMarkerDates(
    holidayMap: ReadonlyMap<string, HolidayEntry>,
): {
    holidayDates: string[];
    workdayOverrideDates: string[];
} {
    const holidayDates: string[] = [];
    const workdayOverrideDates: string[] = [];

    holidayMap.forEach((entry, date) => {
        if (entry.is_workday_override) {
            workdayOverrideDates.push(date);
            return;
        }

        if (entry.is_holiday) {
            holidayDates.push(date);
        }
    });

    return { holidayDates, workdayOverrideDates };
}

export function getHolidayDateStatus(
    date: string | null | undefined,
    holidayMap: ReadonlyMap<string, HolidayEntry>,
): HolidayDateStatus | null {
    if (!date) {
        return null;
    }

    const entry = holidayMap.get(date);

    if (entry?.is_workday_override) {
        return {
            tone: 'makeup_workday',
            label: getHolidayDisplayLabel(entry),
            detail: entry.note ?? CATEGORY_LABELS[entry.category] ?? '補班日',
        };
    }

    if (entry?.is_holiday) {
        return {
            tone: 'holiday',
            label: getHolidayDisplayLabel(entry),
            detail: entry.note ?? CATEGORY_LABELS[entry.category] ?? '假日',
        };
    }

    if (isWeekend(date)) {
        return {
            tone: 'weekend',
            label: '週末',
            detail: '非標準工作日',
        };
    }

    return null;
}

export function calculateWorkRangeStats(
    startDate: string | null | undefined,
    endDate: string | null | undefined,
    holidayMap: ReadonlyMap<string, HolidayEntry>,
    hoursPerDay = 8,
): WorkRangeStats | null {
    if (!startDate || !endDate) {
        return null;
    }

    const start = parseISO(startDate);
    const end = parseISO(endDate);

    if (
        Number.isNaN(start.getTime()) ||
        Number.isNaN(end.getTime()) ||
        start > end
    ) {
        return null;
    }

    let workingDays = 0;
    let holidayDays = 0;
    let makeupWorkdays = 0;
    const holidayNames = new Set<string>();

    for (const date of eachDayOfInterval({ start, end })) {
        const key = format(date, 'yyyy-MM-dd');
        const entry = holidayMap.get(key);

        if (entry?.is_workday_override) {
            workingDays++;
            makeupWorkdays++;
            continue;
        }

        if (entry?.is_holiday) {
            holidayDays++;

            if (entry.name) {
                holidayNames.add(entry.name);
            }

            continue;
        }

        if (isWeekend(date)) {
            holidayDays++;
            continue;
        }

        workingDays++;
    }

    return {
        workingDays,
        holidayDays,
        makeupWorkdays,
        availableHours: workingDays * hoursPerDay,
        holidayNames: [...holidayNames],
    };
}
