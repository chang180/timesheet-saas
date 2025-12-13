import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { weeklyReports } from '@/routes/tenant';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, GripVertical, PlusCircle, Trash2 } from 'lucide-react';
import {
    DndContext,
    closestCenter,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,
    type DragEndEvent,
} from '@dnd-kit/core';
import {
    arrayMove,
    SortableContext,
    sortableKeyboardCoordinates,
    useSortable,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

type WeeklyReportItemInput = {
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

interface WeeklyReportFormProps {
    mode: 'create' | 'edit';
    report: {
        id: number;
        workYear: number;
        workWeek: number;
        status: string;
        summary: string | null;
        currentWeek: Omit<WeeklyReportItemInput, 'localKey' | 'tagsText'>[];
        nextWeek: Omit<WeeklyReportItemInput, 'localKey' | 'tagsText'>[];
    } | null;
    company?: {
        id: number;
        slug: string;
        name: string;
    };
    defaults: {
        year: number;
        week: number;
    };
    weekDateRange?: {
        startDate: string;
        endDate: string;
    };
    nextWeekDateRange?: {
        startDate: string;
        endDate: string;
    };
    prefill: {
        currentWeek: Omit<WeeklyReportItemInput, 'localKey' | 'tagsText'>[];
        nextWeek: Omit<WeeklyReportItemInput, 'localKey' | 'tagsText'>[];
    };
}

type FormData = {
    work_year: number;
    work_week: number;
    summary: string;
    current_week: WeeklyReportItemInput[];
    next_week: WeeklyReportItemInput[];
};

const STATUS_TEXT: Record<string, string> = {
    draft: '草稿',
    submitted: '已送出',
    locked: '已鎖定',
};

const toFormItem = (
    item: Omit<WeeklyReportItemInput, 'localKey' | 'tagsText'>,
    prefix: string,
    index: number,
    defaults: { hours_spent?: number; planned_hours?: number | null } = {},
): WeeklyReportItemInput => {
    // 確保 hours_spent 和 planned_hours 始終是數字類型，避免後端返回字符串時導致計算錯誤
    const hoursSpent = item.hours_spent ?? defaults.hours_spent ?? 0;
    const plannedHours = item.planned_hours ?? defaults.planned_hours ?? null;

    // 處理日期：如果後端返回的是 ISO 字符串，轉換為 YYYY-MM-DD 格式
    const formatDate = (date: string | null | undefined): string | null => {
        if (!date) {
            return null;
        }
        if (typeof date === 'string') {
            // 如果是 ISO 格式，提取日期部分
            return date.split('T')[0];
        }
        return null;
    };

    return {
        id: item.id,
        localKey: `${prefix}-${item.id ?? `new-${index}`}-${Date.now()}`,
        title: item.title ?? '',
        content: item.content ?? '',
        hours_spent: typeof hoursSpent === 'number' ? hoursSpent : Number(hoursSpent) || 0,
        planned_hours:
            plannedHours === null || plannedHours === undefined
                ? null
                : typeof plannedHours === 'number'
                  ? plannedHours
                  : Number(plannedHours) || null,
        issue_reference: item.issue_reference ?? '',
        is_billable: item.is_billable ?? false,
        tags: item.tags ?? [],
        tagsText: (item.tags ?? []).join(', '),
        started_at: formatDate(item.started_at),
        ended_at: formatDate(item.ended_at),
    };
};

type SortableCurrentWeekRowProps = {
    item: WeeklyReportItemInput;
    index: number;
    updateItem: (
        type: 'current_week' | 'next_week',
        index: number,
        key: keyof WeeklyReportItemInput,
        value: unknown,
    ) => void;
    removeItem: (type: 'current_week' | 'next_week', index: number) => void;
    getError: (path: string) => string | undefined;
    weekDateRange?: {
        startDate: string;
        endDate: string;
    };
};

function SortableCurrentWeekRow({
    item,
    index,
    updateItem,
    removeItem,
    getError,
    weekDateRange,
}: SortableCurrentWeekRowProps) {
    // 檢查日期是否為週六或週日
    const isWeekend = (dateStr: string | null | undefined): boolean => {
        if (!dateStr) {
            return false;
        }
        const date = new Date(dateStr);
        const day = date.getDay();
        return day === 0 || day === 6; // 0 = 週日, 6 = 週六
    };

    const startedAtIsWeekend = isWeekend(item.started_at);
    const endedAtIsWeekend = isWeekend(item.ended_at);
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
        id: item.localKey,
    });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.5 : 1,
    };

    return (
        <tr ref={setNodeRef} style={style} className="transition-colors hover:bg-muted/30">
            <td className="px-4 py-3 cursor-move" {...attributes} {...listeners}>
                <div className="flex items-center justify-center text-muted-foreground hover:text-foreground">
                    <GripVertical className="size-4" />
                </div>
            </td>
            <td className="px-4 py-3">
                <Input
                    value={item.title}
                    placeholder="任務名稱"
                    className="min-w-[180px] border-border/60 focus:border-primary focus:ring-primary"
                    data-testid={`current_week.${index}.title`}
                    onChange={(event) => updateItem('current_week', index, 'title', event.target.value)}
                />
                <InputError message={getError(`current_week.${index}.title`)} className="mt-1" />
            </td>
            <td className="px-4 py-3">
                <Textarea
                    rows={2}
                    value={item.content ?? ''}
                    placeholder="詳細說明"
                    className="min-w-[200px] border-border/60 focus:border-primary focus:ring-primary"
                    data-testid={`current_week.${index}.content`}
                    onChange={(event) => updateItem('current_week', index, 'content', event.target.value)}
                />
                <InputError message={getError(`current_week.${index}.content`)} className="mt-1" />
            </td>
            <td className="px-4 py-3">
                <div className="space-y-2">
                    <div>
                        <Label htmlFor={`started_at_${item.localKey}`} className="text-xs text-muted-foreground">
                            開始日期
                        </Label>
                        <Input
                            id={`started_at_${item.localKey}`}
                            type="date"
                            value={item.started_at ?? ''}
                            min={weekDateRange?.startDate}
                            max={weekDateRange?.endDate}
                            className={`w-full border-border/60 focus:border-primary focus:ring-primary ${startedAtIsWeekend ? 'bg-yellow-50 dark:bg-yellow-950/20 border-yellow-300 dark:border-yellow-700' : ''} ${item.ended_at && item.started_at && item.started_at > item.ended_at ? 'border-red-500 dark:border-red-500' : ''}`}
                            onChange={(event) => {
                                const newStartedAt = event.target.value || null;
                                updateItem('current_week', index, 'started_at', newStartedAt);
                                // 如果新的開始日期晚於結束日期，清除結束日期或顯示錯誤
                                if (newStartedAt && item.ended_at && newStartedAt > item.ended_at) {
                                    // 保持結束日期，讓後端驗證處理錯誤訊息
                                }
                            }}
                        />
                        {startedAtIsWeekend && (
                            <p className="text-xs text-yellow-600 dark:text-yellow-400 mt-1">週末日期</p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor={`ended_at_${item.localKey}`} className="text-xs text-muted-foreground">
                            結束日期
                        </Label>
                        <Input
                            id={`ended_at_${item.localKey}`}
                            type="date"
                            value={item.ended_at ?? ''}
                            min={item.started_at || weekDateRange?.startDate}
                            max={weekDateRange?.endDate}
                            className={`w-full border-border/60 focus:border-primary focus:ring-primary ${endedAtIsWeekend ? 'bg-yellow-50 dark:bg-yellow-950/20 border-yellow-300 dark:border-yellow-700' : ''} ${item.started_at && item.ended_at && item.ended_at < item.started_at ? 'border-red-500 dark:border-red-500' : ''}`}
                            onChange={(event) =>
                                updateItem('current_week', index, 'ended_at', event.target.value || null)
                            }
                        />
                        {item.started_at && item.ended_at && item.ended_at < item.started_at && (
                            <p className="text-xs text-red-600 dark:text-red-400 mt-1">結束日期不能早於開始日期</p>
                        )}
                        {endedAtIsWeekend && (
                            <p className="text-xs text-yellow-600 dark:text-yellow-400 mt-1">週末日期</p>
                        )}
                    </div>
                </div>
                <InputError message={getError(`current_week.${index}.started_at`)} className="mt-1" />
                <InputError message={getError(`current_week.${index}.ended_at`)} className="mt-1" />
            </td>
            <td className="px-4 py-3">
                <Input
                    type="number"
                    step="0.5"
                    min="0"
                    value={item.planned_hours ?? ''}
                    className="w-20 border-border/60 focus:border-primary focus:ring-primary"
                    data-testid={`current_week.${index}.planned_hours`}
                    onChange={(event) =>
                        updateItem(
                            'current_week',
                            index,
                            'planned_hours',
                            event.target.value === '' ? null : Number(event.target.value),
                        )
                    }
                />
                <InputError message={getError(`current_week.${index}.planned_hours`)} className="mt-1" />
            </td>
            <td className="px-4 py-3">
                <Input
                    type="number"
                    step="0.5"
                    min="0"
                    value={item.hours_spent}
                    className="w-20 border-border/60 focus:border-primary focus:ring-primary font-medium"
                    data-testid={`current_week.${index}.hours_spent`}
                    onChange={(event) =>
                        updateItem(
                            'current_week',
                            index,
                            'hours_spent',
                            event.target.value === '' ? 0 : Number(event.target.value),
                        )
                    }
                />
                <InputError message={getError(`current_week.${index}.hours_spent`)} className="mt-1" />
            </td>
            <td className="px-4 py-3">
                <Input
                    value={item.issue_reference ?? ''}
                    placeholder="JIRA-1234"
                    className="min-w-[120px] border-border/60 focus:border-primary focus:ring-primary"
                    onChange={(event) => updateItem('current_week', index, 'issue_reference', event.target.value)}
                />
                <InputError message={getError(`current_week.${index}.issue_reference`)} className="mt-1" />
            </td>
            <td className="px-4 py-3">
                <Input
                    value={item.tagsText ?? ''}
                    placeholder="標籤"
                    className="min-w-[120px] border-border/60 focus:border-primary focus:ring-primary"
                    onChange={(event) => updateItem('current_week', index, 'tagsText', event.target.value)}
                />
                <InputError message={getError(`current_week.${index}.tags`)} className="mt-1" />
            </td>
            <td className="px-4 py-3 text-center">
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    onClick={() => removeItem('current_week', index)}
                    className="text-muted-foreground transition-colors hover:bg-destructive/10 hover:text-destructive"
                >
                    <Trash2 className="size-4" />
                </Button>
            </td>
        </tr>
    );
}

type SortableNextWeekRowProps = {
    item: WeeklyReportItemInput;
    index: number;
    updateItem: (
        type: 'current_week' | 'next_week',
        index: number,
        key: keyof WeeklyReportItemInput,
        value: unknown,
    ) => void;
    removeItem: (type: 'current_week' | 'next_week', index: number) => void;
    getError: (path: string) => string | undefined;
    nextWeekDateRange?: {
        startDate: string;
        endDate: string;
    };
};

function SortableNextWeekRow({ item, index, updateItem, removeItem, getError, nextWeekDateRange }: SortableNextWeekRowProps) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
        id: item.localKey,
    });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.5 : 1,
    };

    const isWeekend = (dateStr: string | null | undefined): boolean => {
        if (!dateStr) {
            return false;
        }
        const date = new Date(dateStr);
        const day = date.getDay();
        return day === 0 || day === 6; // 0 = Sunday, 6 = Saturday
    };

    const startedAtIsWeekend = isWeekend(item.started_at);
    const endedAtIsWeekend = isWeekend(item.ended_at);

    return (
        <tr ref={setNodeRef} style={style} className="transition-colors hover:bg-muted/30">
            <td className="px-4 py-3 cursor-move" {...attributes} {...listeners}>
                <div className="flex items-center justify-center text-muted-foreground hover:text-foreground">
                    <GripVertical className="size-4" />
                </div>
            </td>
            <td className="px-4 py-3">
                <Input
                    value={item.title}
                    placeholder="預計事項"
                    className="min-w-[180px] border-border/60 focus:border-primary focus:ring-primary"
                    data-testid={`next_week.${index}.title`}
                    onChange={(event) => updateItem('next_week', index, 'title', event.target.value)}
                />
                <InputError message={getError(`next_week.${index}.title`)} className="mt-1" />
            </td>
            <td className="px-4 py-3">
                <Textarea
                    rows={2}
                    value={item.content ?? ''}
                    placeholder="說明"
                    className="min-w-[200px] border-border/60 focus:border-primary focus:ring-primary"
                    data-testid={`next_week.${index}.content`}
                    onChange={(event) => updateItem('next_week', index, 'content', event.target.value)}
                />
                <InputError message={getError(`next_week.${index}.content`)} className="mt-1" />
            </td>
            <td className="px-4 py-3">
                <div className="space-y-2">
                    <div>
                        <Label htmlFor={`next_started_at_${item.localKey}`} className="text-xs text-muted-foreground">
                            開始日期
                        </Label>
                        <Input
                            id={`next_started_at_${item.localKey}`}
                            type="date"
                            value={item.started_at ?? ''}
                            min={nextWeekDateRange?.startDate}
                            max={nextWeekDateRange?.endDate}
                            className={`w-full border-border/60 focus:border-primary focus:ring-primary ${startedAtIsWeekend ? 'bg-yellow-50 dark:bg-yellow-950/20 border-yellow-300 dark:border-yellow-700' : ''} ${item.ended_at && item.started_at && item.started_at > item.ended_at ? 'border-red-500 dark:border-red-500' : ''}`}
                            onChange={(event) => {
                                const newStartedAt = event.target.value || null;
                                updateItem('next_week', index, 'started_at', newStartedAt);
                            }}
                        />
                        {startedAtIsWeekend && (
                            <p className="text-xs text-yellow-600 dark:text-yellow-400 mt-1">週末日期</p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor={`next_ended_at_${item.localKey}`} className="text-xs text-muted-foreground">
                            結束日期
                        </Label>
                        <Input
                            id={`next_ended_at_${item.localKey}`}
                            type="date"
                            value={item.ended_at ?? ''}
                            min={item.started_at || nextWeekDateRange?.startDate}
                            max={nextWeekDateRange?.endDate}
                            className={`w-full border-border/60 focus:border-primary focus:ring-primary ${endedAtIsWeekend ? 'bg-yellow-50 dark:bg-yellow-950/20 border-yellow-300 dark:border-yellow-700' : ''} ${item.started_at && item.ended_at && item.ended_at < item.started_at ? 'border-red-500 dark:border-red-500' : ''}`}
                            onChange={(event) =>
                                updateItem('next_week', index, 'ended_at', event.target.value || null)
                            }
                        />
                        {item.started_at && item.ended_at && item.ended_at < item.started_at && (
                            <p className="text-xs text-red-600 dark:text-red-400 mt-1">結束日期不能早於開始日期</p>
                        )}
                        {endedAtIsWeekend && (
                            <p className="text-xs text-yellow-600 dark:text-yellow-400 mt-1">週末日期</p>
                        )}
                    </div>
                </div>
                <InputError message={getError(`next_week.${index}.started_at`)} className="mt-1" />
                <InputError message={getError(`next_week.${index}.ended_at`)} className="mt-1" />
            </td>
            <td className="px-4 py-3">
                <Input
                    type="number"
                    step="0.5"
                    min="0"
                    value={item.planned_hours ?? ''}
                    className="w-20 border-border/60 focus:border-primary focus:ring-primary font-medium"
                    data-testid={`next_week.${index}.planned_hours`}
                    onChange={(event) =>
                        updateItem(
                            'next_week',
                            index,
                            'planned_hours',
                            event.target.value === '' ? null : Number(event.target.value),
                        )
                    }
                />
                <InputError message={getError(`next_week.${index}.planned_hours`)} className="mt-1" />
            </td>
            <td className="px-4 py-3">
                <Input
                    value={item.issue_reference ?? ''}
                    placeholder="JIRA-2030"
                    className="min-w-[120px] border-border/60 focus:border-primary focus:ring-primary"
                    onChange={(event) => updateItem('next_week', index, 'issue_reference', event.target.value)}
                />
                <InputError message={getError(`next_week.${index}.issue_reference`)} className="mt-1" />
            </td>
            <td className="px-4 py-3">
                <Input
                    value={item.tagsText ?? ''}
                    placeholder="標籤"
                    className="min-w-[120px] border-border/60 focus:border-primary focus:ring-primary"
                    onChange={(event) => updateItem('next_week', index, 'tagsText', event.target.value)}
                />
                <InputError message={getError(`next_week.${index}.tags`)} className="mt-1" />
            </td>
            <td className="px-4 py-3 text-center">
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    onClick={() => removeItem('next_week', index)}
                    className="text-muted-foreground transition-colors hover:bg-destructive/10 hover:text-destructive"
                >
                    <Trash2 className="size-4" />
                </Button>
            </td>
        </tr>
    );
}

export default function WeeklyReportForm({
    mode,
    report,
    defaults,
    weekDateRange,
    nextWeekDateRange,
    prefill,
    company,
}: WeeklyReportFormProps) {
    const { tenant, flash } = usePage<
        SharedData & { flash: { success?: string; info?: string; warning?: string } }
    >().props;

    const sharedCompanySlug = (tenant?.company as { slug?: string } | undefined)?.slug;
    const companySlug = company?.slug ?? sharedCompanySlug ?? '';
    const canNavigate = companySlug.length > 0;
    const isCreate = mode === 'create';

    const form = useForm<FormData>({
        work_year: report?.workYear ?? defaults.year,
        work_week: report?.workWeek ?? defaults.week,
        summary: report?.summary ?? '',
        current_week: (report?.currentWeek ?? prefill.currentWeek ?? []).map((item, index) =>
            toFormItem(item, 'current', index),
        ),
        next_week: (report?.nextWeek ?? prefill.nextWeek ?? []).map((item, index) =>
            toFormItem(item, 'next', index, { planned_hours: item.planned_hours ?? null }),
        ),
    });

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    const handleCurrentWeekDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;

        if (over && active.id !== over.id) {
            const oldIndex = form.data.current_week.findIndex((item) => item.localKey === active.id);
            const newIndex = form.data.current_week.findIndex((item) => item.localKey === over.id);

            if (oldIndex !== -1 && newIndex !== -1) {
                form.setData('current_week', arrayMove(form.data.current_week, oldIndex, newIndex));
            }
        }
    };

    const handleNextWeekDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;

        if (over && active.id !== over.id) {
            const oldIndex = form.data.next_week.findIndex((item) => item.localKey === active.id);
            const newIndex = form.data.next_week.findIndex((item) => item.localKey === over.id);

            if (oldIndex !== -1 && newIndex !== -1) {
                form.setData('next_week', arrayMove(form.data.next_week, oldIndex, newIndex));
            }
        }
    };

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: '週報工作簿',
            href: canNavigate
                ? weeklyReports.url({ company: companySlug })
                : '#',
        },
        {
            title: isCreate ? '建立週報' : '編輯週報',
            href: '#',
        },
    ];

    const parseTags = (input?: string, fallback: string[] = []): string[] => {
        if (!input) {
            return fallback;
        }

        return input
            .split(',')
            .map((tag) => tag.trim())
            .filter((tag) => tag.length > 0);
    };

    const addCurrentItem = () => {
        form.setData('current_week', [
            ...form.data.current_week,
            {
                localKey: `current-new-${Date.now()}`,
                title: '',
                content: '',
                hours_spent: 0,
                planned_hours: null,
                issue_reference: '',
                is_billable: false,
                tags: [],
                tagsText: '',
            },
        ]);
    };

    const addNextItem = () => {
        form.setData('next_week', [
            ...form.data.next_week,
            {
                localKey: `next-new-${Date.now()}`,
                title: '',
                content: '',
                hours_spent: 0,
                planned_hours: null,
                issue_reference: '',
                tags: [],
                tagsText: '',
            },
        ]);
    };

    const removeItem = (type: 'current_week' | 'next_week', index: number) => {
        const items = [...form.data[type]];
        items.splice(index, 1);
        form.setData(type, items);
    };

    const updateItem = (
        type: 'current_week' | 'next_week',
        index: number,
        key: keyof WeeklyReportItemInput,
        value: unknown,
    ) => {
        const items = form.data[type].map((item, idx) =>
            idx === index ? { ...item, [key]: value } : item,
        );
        form.setData(type, items);
    };

    const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        form.transform((data) => ({
            work_year: data.work_year,
            work_week: data.work_week,
            summary: data.summary,
            current_week: data.current_week.map((item) => ({
                id: item.id,
                title: item.title.trim(),
                content: item.content?.trim() || null,
                hours_spent: Number(item.hours_spent) || 0,
                planned_hours:
                    item.planned_hours === null || item.planned_hours === undefined
                        ? null
                        : Number(item.planned_hours),
                issue_reference: item.issue_reference?.trim() || null,
                is_billable: Boolean(item.is_billable),
                tags: parseTags(item.tagsText, item.tags),
                started_at: item.started_at || null,
                ended_at: item.ended_at || null,
            })),
            next_week: data.next_week.map((item) => ({
                id: item.id,
                title: item.title.trim(),
                content: item.content?.trim() || null,
                planned_hours:
                    item.planned_hours === null || item.planned_hours === undefined
                        ? null
                        : Number(item.planned_hours),
                issue_reference: item.issue_reference?.trim() || null,
                tags: parseTags(item.tagsText, item.tags),
                started_at: item.started_at || null,
                ended_at: item.ended_at || null,
            })),
        }));

        const resetTransform = () => form.transform((data) => data);
        const options = {
            preserveScroll: true,
            onFinish: resetTransform,
        } as const;

        if (!canNavigate) {
            return;
        }

        if (isCreate) {
            form.post(weeklyReports.store.url({ company: companySlug }), options);
        } else if (report) {
            form.put(
                weeklyReports.update.url({ company: companySlug, weeklyReport: report.id }),
                options,
            );
        }
    };

    const getError = (path: string): string | undefined => form.errors[path] as string | undefined;

    // 計算工時合計
    // hours_spent 和 planned_hours 在表單狀態中已經是數字類型
    const totalCurrentWeekHours = form.data.current_week.reduce(
        (sum, item) => sum + (item.hours_spent || 0),
        0,
    );
    const totalNextWeekHours = form.data.next_week.reduce(
        (sum, item) => sum + (item.planned_hours || 0),
        0,
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={isCreate ? '建立週報' : '編輯週報'} />

            <form onSubmit={handleSubmit} className="space-y-6">
                <div className="flex items-center gap-4">
                    {canNavigate ? (
                        <Button asChild variant="ghost" size="sm" className="gap-2">
                            <Link href={weeklyReports.url({ company: companySlug })}>
                                <ArrowLeft className="size-4" />
                                返回列表
                            </Link>
                        </Button>
                    ) : (
                        <Button variant="ghost" size="sm" disabled className="gap-2">
                            <ArrowLeft className="size-4" />
                            返回列表
                        </Button>
                    )}
                    <div className="flex-1">
                        <h1 className="text-2xl font-bold text-foreground">
                            {isCreate ? '建立週報草稿' : '編輯週報'}
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {form.data.work_year} 年第 {form.data.work_week} 週
                            {weekDateRange && (
                                <span className="text-muted-foreground">
                                    {' '}
                                    ({weekDateRange.startDate} ~ {weekDateRange.endDate})
                                </span>
                            )}{' '}
                            ·{' '}
                            <span className="uppercase text-xs font-medium text-muted-foreground">
                                {STATUS_TEXT[report?.status ?? 'draft'] ?? '草稿'}
                            </span>
                        </p>
                    </div>
                </div>

                {(flash?.success || flash?.info || flash?.warning) && (
                    <div className="rounded-lg border border-border/60 bg-muted/40 p-4 shadow-sm">
                        {flash.success && (
                            <p className="text-sm font-medium text-emerald-600 dark:text-emerald-400">{flash.success}</p>
                        )}
                        {flash.info && (
                            <p className="text-sm font-medium text-sky-600 dark:text-sky-400">{flash.info}</p>
                        )}
                        {flash.warning && (
                            <p className="text-sm font-medium text-amber-600 dark:text-amber-400">{flash.warning}</p>
                        )}
                    </div>
                )}

                {isCreate && prefill.currentWeek.length > 0 && (
                    <div className="rounded-lg border-2 border-indigo-200 bg-gradient-to-r from-indigo-50 to-indigo-100/50 p-4 shadow-sm dark:border-indigo-500/30 dark:from-indigo-500/10 dark:to-indigo-500/5">
                        <p className="text-sm font-semibold text-indigo-900 dark:text-indigo-100">
                            已帶入上一週的「下週預計」項目，記得調整內容與工時。
                        </p>
                    </div>
                )}

                <Card className="border-border/60 shadow-sm">
                    <CardHeader className="border-b border-border/40 bg-muted/30 pb-4">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <CardTitle className="text-xl font-semibold">本週完成事項</CardTitle>
                            <Button type="button" size="sm" variant="default" onClick={addCurrentItem} className="gap-2">
                                <PlusCircle className="size-4" />
                                新增項目
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent className="pt-6">
                        {form.data.current_week.length === 0 ? (
                            <div className="rounded-lg border-2 border-dashed border-border/40 bg-muted/20 p-8 text-center">
                                <p className="text-sm font-medium text-muted-foreground">
                                    目前沒有任何項目，點擊「新增項目」開始紀錄。
                                </p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <DndContext
                                    sensors={sensors}
                                    collisionDetection={closestCenter}
                                    onDragEnd={handleCurrentWeekDragEnd}
                                >
                                    <table className="min-w-full divide-y divide-border/40 text-sm">
                                        <thead className="bg-muted/50">
                                            <tr>
                                                <th className="px-4 py-3 w-10"></th>
                                                <th className="px-4 py-3 text-left font-semibold text-foreground">標題</th>
                                                <th className="px-4 py-3 text-left font-semibold text-foreground">內容</th>
                                                <th className="px-4 py-3 text-left font-semibold text-foreground whitespace-nowrap">時間範圍</th>
                                                <th className="px-4 py-3 text-left font-semibold text-foreground whitespace-nowrap">預計工時</th>
                                                <th className="px-4 py-3 text-left font-semibold text-foreground whitespace-nowrap">實際工時</th>
                                                <th className="px-4 py-3 text-left font-semibold text-foreground">Issue</th>
                                                <th className="px-4 py-3 text-left font-semibold text-foreground">標籤</th>
                                                <th className="px-4 py-3 text-center font-semibold text-foreground">操作</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-border/30 bg-card">
                                            <SortableContext
                                                items={form.data.current_week.map((item) => item.localKey)}
                                                strategy={verticalListSortingStrategy}
                                            >
                                                {form.data.current_week.map((item, index) => (
                                                    <SortableCurrentWeekRow
                                                        key={item.localKey}
                                                        item={item}
                                                        index={index}
                                                        updateItem={updateItem}
                                                        removeItem={removeItem}
                                                        getError={getError}
                                                        weekDateRange={weekDateRange}
                                                    />
                                                ))}
                                            </SortableContext>
                                        </tbody>
                                        {form.data.current_week.length > 0 && (
                                            <tfoot className="bg-gradient-to-r from-muted/40 to-muted/20 border-t-2 border-border/60">
                                                <tr>
                                                    <td colSpan={4} className="px-4 py-3 text-right font-semibold text-foreground">
                                                        實際工時小計：
                                                    </td>
                                                    <td className="px-4 py-3"></td>
                                                    <td className="px-4 py-3 text-center text-lg font-bold text-blue-600 dark:text-blue-400">
                                                        {totalCurrentWeekHours.toFixed(1)} 小時
                                                    </td>
                                                    <td colSpan={3}></td>
                                                </tr>
                                            </tfoot>
                                        )}
                                    </table>
                                </DndContext>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card className="border-border/60 shadow-sm">
                    <CardHeader className="border-b border-border/40 bg-muted/30 pb-4">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <CardTitle className="text-xl font-semibold">下週預計事項</CardTitle>
                            <Button type="button" size="sm" variant="default" onClick={addNextItem} className="gap-2">
                                <PlusCircle className="size-4" />
                                新增項目
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent className="pt-6">
                        {form.data.next_week.length === 0 ? (
                            <div className="rounded-lg border-2 border-dashed border-border/40 bg-muted/20 p-8 text-center">
                                <p className="text-sm font-medium text-muted-foreground">
                                    目前沒有任何預計事項，點擊「新增項目」規劃下週工作。
                                </p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <DndContext
                                    sensors={sensors}
                                    collisionDetection={closestCenter}
                                    onDragEnd={handleNextWeekDragEnd}
                                >
                                    <table className="min-w-full divide-y divide-border/40 text-sm">
                                        <thead className="bg-muted/50">
                                            <tr>
                                                <th className="px-4 py-3 w-10"></th>
                                                <th className="px-4 py-3 text-left font-semibold text-foreground">標題</th>
                                                <th className="px-4 py-3 text-left font-semibold text-foreground">補充說明</th>
                                                <th className="px-4 py-3 text-left font-semibold text-foreground whitespace-nowrap">時間範圍</th>
                                                <th className="px-4 py-3 text-left font-semibold text-foreground whitespace-nowrap">預估工時</th>
                                                <th className="px-4 py-3 text-left font-semibold text-foreground">Issue</th>
                                                <th className="px-4 py-3 text-left font-semibold text-foreground">標籤</th>
                                                <th className="px-4 py-3 text-center font-semibold text-foreground">操作</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-border/30 bg-card">
                                            <SortableContext
                                                items={form.data.next_week.map((item) => item.localKey)}
                                                strategy={verticalListSortingStrategy}
                                            >
                                                {form.data.next_week.map((item, index) => (
                                                    <SortableNextWeekRow
                                                        key={item.localKey}
                                                        item={item}
                                                        index={index}
                                                        updateItem={updateItem}
                                                        removeItem={removeItem}
                                                        getError={getError}
                                                        nextWeekDateRange={nextWeekDateRange}
                                                    />
                                                ))}
                                            </SortableContext>
                                        </tbody>
                                    </table>
                                </DndContext>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* 工時合計顯示 */}
                <Card className="border-border/60 shadow-sm">
                    <CardHeader className="border-b border-border/40 bg-muted/30 pb-4">
                        <CardTitle className="text-xl font-semibold">工時統計</CardTitle>
                    </CardHeader>
                    <CardContent className="pt-6">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="rounded-lg border-2 border-blue-200 bg-gradient-to-br from-blue-50 to-blue-100/50 p-6 shadow-sm dark:border-blue-800 dark:from-blue-950/20 dark:to-blue-900/10">
                                <div className="text-sm font-medium text-muted-foreground">本週完成總工時</div>
                                <div className="mt-2 text-3xl font-bold text-blue-600 dark:text-blue-400">
                                    {totalCurrentWeekHours.toFixed(1)}
                                </div>
                                <div className="mt-1 text-xs text-muted-foreground">小時</div>
                                <div className="mt-3 text-sm text-muted-foreground">
                                    {form.data.current_week.length} 個項目
                                </div>
                            </div>
                            <div className="rounded-lg border-2 border-emerald-200 bg-gradient-to-br from-emerald-50 to-emerald-100/50 p-6 shadow-sm dark:border-emerald-800 dark:from-emerald-950/20 dark:to-emerald-900/10">
                                <div className="text-sm font-medium text-muted-foreground">下週預計總工時</div>
                                <div className="mt-2 text-3xl font-bold text-emerald-600 dark:text-emerald-400">
                                    {totalNextWeekHours.toFixed(1)}
                                </div>
                                <div className="mt-1 text-xs text-muted-foreground">小時</div>
                                <div className="mt-3 text-sm text-muted-foreground">
                                    {form.data.next_week.length} 個項目
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card className="border-border/60 shadow-sm">
                    <CardContent className="pt-6">
                        <div>
                            <Label htmlFor="summary" className="text-base font-medium">摘要</Label>
                            <Textarea
                                id="summary"
                                rows={4}
                                value={form.data.summary}
                                data-testid="summary"
                                onChange={(event) => form.setData('summary', event.target.value)}
                                placeholder="可輸入本週亮點、風險提醒或需要協助的事項（選填）"
                                className="mt-2"
                            />
                            <InputError message={form.errors.summary} className="mt-2" />
                        </div>
                    </CardContent>
                </Card>

                <div className="flex items-center justify-end gap-3 border-t border-border/60 pt-6">
                    {report && (
                        <Button
                            type="button"
                            variant="outline"
                            disabled={!canNavigate}
                            asChild
                            className="gap-2"
                        >
                            <Link
                                href={canNavigate ? `/app/${companySlug}/weekly-reports/${report.id}/preview` : '#'}
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                預覽
                            </Link>
                        </Button>
                    )}
                    {report?.status === 'draft' && (
                        <Button
                            type="button"
                            variant="default"
                            disabled={form.processing || !canNavigate}
                            className="gap-2"
                            onClick={() => {
                                if (!canNavigate || !report) {
                                    return;
                                }
                                router.post(
                                    `/app/${companySlug}/weekly-reports/${report.id}/submit`,
                                    {},
                                    {
                                        preserveScroll: true,
                                        onSuccess: () => {
                                            // 重新載入頁面以更新狀態
                                            router.reload({ only: ['report'] });
                                        },
                                    },
                                );
                            }}
                            data-testid="submit-weekly-report"
                        >
                            發佈週報
                        </Button>
                    )}
                    <Button
                        type="submit"
                        disabled={form.processing || !canNavigate}
                        className="gap-2"
                        data-testid="save-weekly-report"
                    >
                        {form.processing ? '儲存中...' : '儲存週報'}
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}
