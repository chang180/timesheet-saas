import { format, getISOWeek, getISOWeekYear, parseISO, startOfISOWeek } from 'date-fns';
import { zhTW } from 'date-fns/locale';

/**
 * 取得指定 ISO 週的日期範圍
 * @param year ISO 年份
 * @param week ISO 週數
 * @returns { startDate: string, endDate: string } - YYYY-MM-DD 格式
 */
export function getISOWeekRange(year: number, week: number): { startDate: string; endDate: string } {
    // 找到該年第一個 ISO 週的週一
    const jan4 = new Date(year, 0, 4);
    const jan4Day = jan4.getDay() || 7; // 週日為 7
    const firstMonday = new Date(jan4);
    firstMonday.setDate(jan4.getDate() - jan4Day + 1);

    // 計算目標週的週一
    const weekStart = new Date(firstMonday);
    weekStart.setDate(firstMonday.getDate() + (week - 1) * 7);

    // 計算目標週的週日
    const weekEnd = new Date(weekStart);
    weekEnd.setDate(weekStart.getDate() + 6);

    return {
        startDate: format(weekStart, 'yyyy-MM-dd'),
        endDate: format(weekEnd, 'yyyy-MM-dd'),
    };
}

/**
 * 判斷日期是否為週末 (週六或週日)
 * @param date Date 物件或 YYYY-MM-DD 字串
 * @returns boolean
 */
export function isWeekend(date: Date | string | null | undefined): boolean {
    if (!date) {
        return false;
    }

    const dateObj = typeof date === 'string' ? parseISO(date) : date;
    const day = dateObj.getDay();
    return day === 0 || day === 6; // 0 = 週日, 6 = 週六
}

/**
 * 格式化日期
 * @param date Date 物件或 YYYY-MM-DD 字串
 * @param formatStr 格式字串，預設 'yyyy-MM-dd'
 * @returns 格式化後的日期字串
 */
export function formatDate(date: Date | string | null | undefined, formatStr = 'yyyy-MM-dd'): string {
    if (!date) {
        return '';
    }

    const dateObj = typeof date === 'string' ? parseISO(date) : date;
    return format(dateObj, formatStr, { locale: zhTW });
}

/**
 * 取得日期的 ISO 週數和年份
 * @param date Date 物件或 YYYY-MM-DD 字串
 * @returns { year: number, week: number }
 */
export function getWeekNumber(date: Date | string): { year: number; week: number } {
    const dateObj = typeof date === 'string' ? parseISO(date) : date;
    return {
        year: getISOWeekYear(dateObj),
        week: getISOWeek(dateObj),
    };
}

/**
 * 取得指定日期所在週的週一
 * @param date Date 物件或 YYYY-MM-DD 字串
 * @returns Date 物件
 */
export function getWeekStartDate(date: Date | string): Date {
    const dateObj = typeof date === 'string' ? parseISO(date) : date;
    return startOfISOWeek(dateObj);
}

/**
 * 將 YYYY-MM-DD 字串轉換為 Date 物件
 * @param dateStr YYYY-MM-DD 格式的日期字串
 * @returns Date 物件或 null
 */
export function parseDate(dateStr: string | null | undefined): Date | null {
    if (!dateStr) {
        return null;
    }
    try {
        return parseISO(dateStr);
    } catch {
        return null;
    }
}

/**
 * 將 Date 物件轉換為 YYYY-MM-DD 字串
 * @param date Date 物件
 * @returns YYYY-MM-DD 格式的日期字串或 null
 */
export function toDateString(date: Date | null | undefined): string | null {
    if (!date) {
        return null;
    }
    return format(date, 'yyyy-MM-dd');
}
