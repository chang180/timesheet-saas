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
import { Head, Link, useForm, usePage } from '@inertiajs/react';
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
};

function SortableCurrentWeekRow({ item, index, updateItem, removeItem, getError }: SortableCurrentWeekRowProps) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
        id: item.localKey,
    });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.5 : 1,
    };

    return (
        <tr ref={setNodeRef} style={style} className="hover:bg-muted/20">
            <td className="px-3 py-2 cursor-move" {...attributes} {...listeners}>
                <div className="flex items-center justify-center text-muted-foreground">
                    <GripVertical className="size-4" />
                </div>
            </td>
            <td className="px-3 py-2">
                <Input
                    value={item.title}
                    placeholder="任務名稱"
                    className="min-w-[180px]"
                    data-testid={`current_week.${index}.title`}
                    onChange={(event) => updateItem('current_week', index, 'title', event.target.value)}
                />
                <InputError message={getError(`current_week.${index}.title`)} className="mt-1" />
            </td>
            <td className="px-3 py-2">
                <Textarea
                    rows={2}
                    value={item.content ?? ''}
                    placeholder="詳細說明"
                    className="min-w-[200px]"
                    data-testid={`current_week.${index}.content`}
                    onChange={(event) => updateItem('current_week', index, 'content', event.target.value)}
                />
                <InputError message={getError(`current_week.${index}.content`)} className="mt-1" />
            </td>
            <td className="px-3 py-2">
                <Input
                    type="number"
                    step="0.5"
                    min="0"
                    value={item.hours_spent}
                    className="w-20"
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
            <td className="px-3 py-2">
                <div className="flex items-center justify-center">
                    <Checkbox
                        id={`is-billable-${item.localKey}`}
                        checked={Boolean(item.is_billable)}
                        onCheckedChange={(checked) =>
                            updateItem('current_week', index, 'is_billable', Boolean(checked))
                        }
                    />
                </div>
            </td>
            <td className="px-3 py-2">
                <Input
                    value={item.issue_reference ?? ''}
                    placeholder="JIRA-1234"
                    className="min-w-[120px]"
                    onChange={(event) => updateItem('current_week', index, 'issue_reference', event.target.value)}
                />
                <InputError message={getError(`current_week.${index}.issue_reference`)} className="mt-1" />
            </td>
            <td className="px-3 py-2">
                <Input
                    value={item.tagsText ?? ''}
                    placeholder="標籤"
                    className="min-w-[120px]"
                    onChange={(event) => updateItem('current_week', index, 'tagsText', event.target.value)}
                />
                <InputError message={getError(`current_week.${index}.tags`)} className="mt-1" />
            </td>
            <td className="px-3 py-2 text-center">
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    onClick={() => removeItem('current_week', index)}
                    className="text-muted-foreground hover:text-destructive"
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
};

function SortableNextWeekRow({ item, index, updateItem, removeItem, getError }: SortableNextWeekRowProps) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
        id: item.localKey,
    });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.5 : 1,
    };

    return (
        <tr ref={setNodeRef} style={style} className="hover:bg-muted/20">
            <td className="px-3 py-2 cursor-move" {...attributes} {...listeners}>
                <div className="flex items-center justify-center text-muted-foreground">
                    <GripVertical className="size-4" />
                </div>
            </td>
            <td className="px-3 py-2">
                <Input
                    value={item.title}
                    placeholder="預計事項"
                    className="min-w-[180px]"
                    data-testid={`next_week.${index}.title`}
                    onChange={(event) => updateItem('next_week', index, 'title', event.target.value)}
                />
                <InputError message={getError(`next_week.${index}.title`)} className="mt-1" />
            </td>
            <td className="px-3 py-2">
                <Textarea
                    rows={2}
                    value={item.content ?? ''}
                    placeholder="說明"
                    className="min-w-[200px]"
                    data-testid={`next_week.${index}.content`}
                    onChange={(event) => updateItem('next_week', index, 'content', event.target.value)}
                />
                <InputError message={getError(`next_week.${index}.content`)} className="mt-1" />
            </td>
            <td className="px-3 py-2">
                <Input
                    type="number"
                    step="0.5"
                    min="0"
                    value={item.planned_hours ?? ''}
                    className="w-20"
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
            <td className="px-3 py-2">
                <Input
                    value={item.issue_reference ?? ''}
                    placeholder="JIRA-2030"
                    className="min-w-[120px]"
                    onChange={(event) => updateItem('next_week', index, 'issue_reference', event.target.value)}
                />
                <InputError message={getError(`next_week.${index}.issue_reference`)} className="mt-1" />
            </td>
            <td className="px-3 py-2">
                <Input
                    value={item.tagsText ?? ''}
                    placeholder="標籤"
                    className="min-w-[120px]"
                    onChange={(event) => updateItem('next_week', index, 'tagsText', event.target.value)}
                />
                <InputError message={getError(`next_week.${index}.tags`)} className="mt-1" />
            </td>
            <td className="px-3 py-2 text-center">
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    onClick={() => removeItem('next_week', index)}
                    className="text-muted-foreground hover:text-destructive"
                >
                    <Trash2 className="size-4" />
                </Button>
            </td>
        </tr>
    );
}

export default function WeeklyReportForm({ mode, report, defaults, prefill, company }: WeeklyReportFormProps) {
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
                issue_reference: item.issue_reference?.trim() || null,
                is_billable: Boolean(item.is_billable),
                tags: parseTags(item.tagsText, item.tags),
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
                <div className="flex items-center gap-3">
                    {canNavigate ? (
                        <Button asChild variant="ghost" size="sm">
                            <Link href={weeklyReports.url({ company: companySlug })}>
                                <ArrowLeft className="mr-2 size-4" />
                                返回列表
                            </Link>
                        </Button>
                    ) : (
                        <Button variant="ghost" size="sm" disabled>
                            <ArrowLeft className="mr-2 size-4" />
                            返回列表
                        </Button>
                    )}
                    <div>
                        <h1 className="text-xl font-semibold text-foreground">
                            {isCreate ? '建立週報草稿' : '編輯週報'}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {form.data.work_year} 年第 {form.data.work_week} 週 ·{' '}
                            <span className="uppercase text-xs text-muted-foreground">
                                {STATUS_TEXT[report?.status ?? 'draft'] ?? '草稿'}
                            </span>
                        </p>
                    </div>
                </div>

                {(flash?.success || flash?.info || flash?.warning) && (
                    <div className="rounded-md border border-border/60 bg-muted/40 p-4 text-sm text-muted-foreground">
                        {flash.success && <p className="text-emerald-600">{flash.success}</p>}
                        {flash.info && <p className="text-sky-600">{flash.info}</p>}
                        {flash.warning && <p className="text-amber-600">{flash.warning}</p>}
                    </div>
                )}

                {isCreate && prefill.currentWeek.length > 0 && (
                    <div className="rounded-md border border-indigo-200 bg-indigo-50/70 p-4 text-sm text-indigo-900 shadow-sm dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-100">
                        <p className="font-medium">
                            已帶入上一週的「下週預計」項目，記得調整內容與工時。
                        </p>
                    </div>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle className="text-lg">週報摘要</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <Label htmlFor="work-year">年度</Label>
                                <Input
                                    id="work-year"
                                    type="number"
                                    min={2000}
                                    max={2100}
                                    value={form.data.work_year}
                                    onChange={(event) =>
                                        form.setData('work_year', Number(event.target.value) || defaults.year)
                                    }
                                />
                                <InputError message={form.errors.work_year} className="mt-2" />
                            </div>
                            <div>
                                <Label htmlFor="work-week">週次</Label>
                                <Input
                                    id="work-week"
                                    type="number"
                                    min={1}
                                    max={53}
                                    value={form.data.work_week}
                                    onChange={(event) =>
                                        form.setData('work_week', Number(event.target.value) || defaults.week)
                                    }
                                />
                                <InputError message={form.errors.work_week} className="mt-2" />
                            </div>
                        </div>
                        <div>
                            <Label htmlFor="summary">摘要</Label>
                            <Textarea
                                id="summary"
                                rows={3}
                                value={form.data.summary}
                                data-testid="summary"
                                onChange={(event) => form.setData('summary', event.target.value)}
                                placeholder="可輸入本週亮點、風險提醒或需要協助的事項"
                            />
                            <InputError message={form.errors.summary} className="mt-2" />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <CardTitle className="text-lg">本週完成事項</CardTitle>
                        <Button type="button" size="sm" variant="outline" onClick={addCurrentItem}>
                            <PlusCircle className="mr-2 size-4" />
                            新增項目
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {form.data.current_week.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                目前沒有任何項目，點擊「新增項目」開始紀錄。
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <DndContext
                                    sensors={sensors}
                                    collisionDetection={closestCenter}
                                    onDragEnd={handleCurrentWeekDragEnd}
                                >
                                    <table className="min-w-full divide-y divide-border/60 text-sm">
                                        <thead className="bg-muted/40 text-muted-foreground">
                                            <tr>
                                                <th className="px-3 py-2 w-10"></th>
                                                <th className="px-3 py-2 text-left font-medium">標題</th>
                                                <th className="px-3 py-2 text-left font-medium">內容</th>
                                                <th className="px-3 py-2 text-left font-medium whitespace-nowrap">工時</th>
                                                <th className="px-3 py-2 text-left font-medium whitespace-nowrap">計費</th>
                                                <th className="px-3 py-2 text-left font-medium">Issue</th>
                                                <th className="px-3 py-2 text-left font-medium">標籤</th>
                                                <th className="px-3 py-2 text-center font-medium">操作</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-border/40 bg-card">
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

                <Card>
                    <CardHeader className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <CardTitle className="text-lg">下週預計事項</CardTitle>
                        <Button type="button" size="sm" variant="outline" onClick={addNextItem}>
                            <PlusCircle className="mr-2 size-4" />
                            新增項目
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {form.data.next_week.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                目前沒有任何預計事項，點擊「新增項目」規劃下週工作。
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <DndContext
                                    sensors={sensors}
                                    collisionDetection={closestCenter}
                                    onDragEnd={handleNextWeekDragEnd}
                                >
                                    <table className="min-w-full divide-y divide-border/60 text-sm">
                                        <thead className="bg-muted/40 text-muted-foreground">
                                            <tr>
                                                <th className="px-3 py-2 w-10"></th>
                                                <th className="px-3 py-2 text-left font-medium">標題</th>
                                                <th className="px-3 py-2 text-left font-medium">補充說明</th>
                                                <th className="px-3 py-2 text-left font-medium whitespace-nowrap">預估工時</th>
                                                <th className="px-3 py-2 text-left font-medium">Issue</th>
                                                <th className="px-3 py-2 text-left font-medium">標籤</th>
                                                <th className="px-3 py-2 text-center font-medium">操作</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-border/40 bg-card">
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
                <Card>
                    <CardHeader>
                        <CardTitle className="text-lg">工時統計</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="rounded-lg border border-border/60 bg-muted/40 p-4">
                                <div className="text-sm text-muted-foreground">本週完成總工時</div>
                                <div className="mt-1 text-2xl font-semibold text-foreground">
                                    {totalCurrentWeekHours.toFixed(1)} 小時
                                </div>
                                <div className="mt-2 text-xs text-muted-foreground">
                                    {form.data.current_week.length} 個項目
                                </div>
                            </div>
                            <div className="rounded-lg border border-border/60 bg-muted/40 p-4">
                                <div className="text-sm text-muted-foreground">下週預計總工時</div>
                                <div className="mt-1 text-2xl font-semibold text-foreground">
                                    {totalNextWeekHours.toFixed(1)} 小時
                                </div>
                                <div className="mt-2 text-xs text-muted-foreground">
                                    {form.data.next_week.length} 個項目
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="flex items-center justify-end gap-3">
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
