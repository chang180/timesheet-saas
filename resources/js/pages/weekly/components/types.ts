export type WeeklyReportItemInput = {
    id?: number;
    localKey: string;
    title: string;
    content?: string | null;
    hours_spent: number;
    planned_hours?: number | null;
    issue_reference?: string | null;
    is_billable?: boolean;
    tags?: string[];
    tagsText?: string;
    started_at?: string | null;
    ended_at?: string | null;
};

export type DateRange = {
    startDate: string;
    endDate: string;
};

export type UpdateItemFn = (
    type: 'current_week' | 'next_week',
    index: number,
    key: keyof WeeklyReportItemInput,
    value: unknown,
) => void;

export type RemoveItemFn = (type: 'current_week' | 'next_week', index: number) => void;

export type MoveItemFn = (type: 'current_week' | 'next_week', index: number, direction: 'up' | 'down') => void;

export type GetErrorFn = (path: string) => string | undefined;
