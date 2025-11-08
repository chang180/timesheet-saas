export interface TaskItem {
    id?: string;
    title: string;
    description?: string;
    estimatedHours?: number;
    actualHours?: number;
    redmineIssueId?: string;
    jiraIssueId?: string;
    order: number;
}

export interface WeeklyReportData {
    id?: number;
    userId: number;
    weekStartDate: string;
    weekEndDate: string;
    currentWeekTasks: TaskItem[];
    nextWeekTasks: TaskItem[];
    totalCurrentWeekHours: number;
    totalNextWeekHours: number;
    status: 'draft' | 'submitted' | 'reopened';
    submittedAt?: string;
    createdAt?: string;
    updatedAt?: string;
}

export interface WeeklyReportListFilters {
    weekStartDate?: string;
    weekEndDate?: string;
    status?: 'draft' | 'submitted' | 'reopened';
    departmentId?: number;
    teamId?: number;
    userId?: number;
    search?: string;
    page?: number;
    perPage?: number;
}

export interface WeeklyReportSummary {
    id: number;
    userName: string;
    weekStartDate: string;
    weekEndDate: string;
    totalHours: number;
    status: 'draft' | 'submitted' | 'reopened';
    submittedAt?: string;
    hasHolidayWarning: boolean;
}

export interface WeeklyReportListResponse {
    data: WeeklyReportSummary[];
    total: number;
    currentPage: number;
    lastPage: number;
    perPage: number;
}

export interface RedmineIssue {
    id: number;
    subject: string;
    estimatedHours?: number;
    description?: string;
}

export interface JiraIssue {
    id: string;
    key: string;
    summary: string;
    estimatedHours?: number;
    description?: string;
}

export interface HolidayInfo {
    date: string;
    name: string;
    isNationalHoliday: boolean;
}

export interface WeeklyReportFormData {
    weekStartDate: string;
    currentWeekTasks: Omit<TaskItem, 'id'>[];
    nextWeekTasks: Omit<TaskItem, 'id'>[];
}

export interface WeeklyReportSubmitResponse {
    id: number;
    message: string;
}
