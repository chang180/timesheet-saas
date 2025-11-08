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
import { ArrowLeft, PlusCircle, Trash2 } from 'lucide-react';

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
): WeeklyReportItemInput => ({
    id: item.id,
    localKey: `${prefix}-${item.id ?? `new-${index}`}-${Date.now()}`,
    title: item.title ?? '',
    content: item.content ?? '',
    hours_spent: item.hours_spent ?? defaults.hours_spent ?? 0,
    planned_hours: item.planned_hours ?? defaults.planned_hours ?? null,
    issue_reference: item.issue_reference ?? '',
    is_billable: item.is_billable ?? false,
    tags: item.tags ?? [],
    tagsText: (item.tags ?? []).join(', '),
});

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
                    <CardContent className="space-y-4">
                        {form.data.current_week.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                目前沒有任何項目，點擊「新增項目」開始紀錄。
                            </p>
                        ) : null}

                        {form.data.current_week.map((item, index) => (
                            <div key={item.localKey} className="rounded-lg border border-border/60 p-4 shadow-sm">
                                <div className="flex items-start justify-between gap-3">
                                    <div className="flex-1 space-y-4">
                                        <div>
                                            <Label>標題</Label>
                                            <Input
                                                value={item.title}
                                                placeholder="請輸入任務或專案名稱"
                                                onChange={(event) =>
                                                    updateItem('current_week', index, 'title', event.target.value)
                                                }
                                            />
                                            <InputError
                                                message={getError(`current_week.${index}.title`)}
                                                className="mt-2"
                                            />
                                        </div>

                                        <div className="grid gap-4 sm:grid-cols-[minmax(0,1fr)_160px]">
                                            <div>
                                                <Label>內容</Label>
                                                <Textarea
                                                    rows={3}
                                                    value={item.content ?? ''}
                                                    onChange={(event) =>
                                                        updateItem(
                                                            'current_week',
                                                            index,
                                                            'content',
                                                            event.target.value,
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={getError(`current_week.${index}.content`)}
                                                    className="mt-2"
                                                />
                                            </div>
                                            <div className="space-y-4">
                                                <div>
                                                    <Label>耗費工時</Label>
                                                    <Input
                                                        type="number"
                                                        step="0.5"
                                                        min="0"
                                                        value={item.hours_spent}
                                                        onChange={(event) =>
                                                            updateItem(
                                                                'current_week',
                                                                index,
                                                                'hours_spent',
                                                                event.target.value,
                                                            )
                                                        }
                                                    />
                                                    <InputError
                                                        message={getError(`current_week.${index}.hours_spent`)}
                                                        className="mt-2"
                                                    />
                                                </div>
                                                <div className="flex items-center gap-3">
                                                    <Checkbox
                                                        id={`is-billable-${item.localKey}`}
                                                        checked={Boolean(item.is_billable)}
                                                        onCheckedChange={(checked) =>
                                                            updateItem(
                                                                'current_week',
                                                                index,
                                                                'is_billable',
                                                                Boolean(checked),
                                                            )
                                                        }
                                                    />
                                                    <Label htmlFor={`is-billable-${item.localKey}`}>
                                                        計入可計費工時
                                                    </Label>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <div>
                                                <Label>關聯 Issue / 任務</Label>
                                                <Input
                                                    value={item.issue_reference ?? ''}
                                                    placeholder="例如：JIRA-1234"
                                                    onChange={(event) =>
                                                        updateItem(
                                                            'current_week',
                                                            index,
                                                            'issue_reference',
                                                            event.target.value,
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={getError(`current_week.${index}.issue_reference`)}
                                                    className="mt-2"
                                                />
                                            </div>
                                            <div>
                                                <Label>標籤（以逗號分隔）</Label>
                                                <Input
                                                    value={item.tagsText ?? ''}
                                                    placeholder="backend, code-review"
                                                    onChange={(event) =>
                                                        updateItem(
                                                            'current_week',
                                                            index,
                                                            'tagsText',
                                                            event.target.value,
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={getError(`current_week.${index}.tags`)}
                                                    className="mt-2"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => removeItem('current_week', index)}
                                        className="text-muted-foreground hover:text-destructive"
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                </div>
                            </div>
                        ))}
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
                    <CardContent className="space-y-4">
                        {form.data.next_week.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                目前沒有任何預計事項，點擊「新增項目」規劃下週工作。
                            </p>
                        ) : null}

                        {form.data.next_week.map((item, index) => (
                            <div key={item.localKey} className="rounded-lg border border-border/60 p-4 shadow-sm">
                                <div className="flex items-start justify-between gap-3">
                                    <div className="flex-1 space-y-4">
                                        <div>
                                            <Label>標題</Label>
                                            <Input
                                                value={item.title}
                                                placeholder="請輸入預計事項"
                                                onChange={(event) =>
                                                    updateItem('next_week', index, 'title', event.target.value)
                                                }
                                            />
                                            <InputError
                                                message={getError(`next_week.${index}.title`)}
                                                className="mt-2"
                                            />
                                        </div>

                                        <div className="grid gap-4 sm:grid-cols-[minmax(0,1fr)_160px]">
                                            <div>
                                                <Label>補充說明</Label>
                                                <Textarea
                                                    rows={3}
                                                    value={item.content ?? ''}
                                                    onChange={(event) =>
                                                        updateItem('next_week', index, 'content', event.target.value)
                                                    }
                                                />
                                                <InputError
                                                    message={getError(`next_week.${index}.content`)}
                                                    className="mt-2"
                                                />
                                            </div>
                                            <div>
                                                <Label>預估工時</Label>
                                                <Input
                                                    type="number"
                                                    step="0.5"
                                                    min="0"
                                                    value={item.planned_hours ?? ''}
                                                    onChange={(event) =>
                                                        updateItem(
                                                            'next_week',
                                                            index,
                                                            'planned_hours',
                                                            event.target.value === ''
                                                                ? null
                                                                : Number(event.target.value),
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={getError(`next_week.${index}.planned_hours`)}
                                                    className="mt-2"
                                                />
                                            </div>
                                        </div>

                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <div>
                                                <Label>關聯 Issue / 任務</Label>
                                                <Input
                                                    value={item.issue_reference ?? ''}
                                                    placeholder="例如：JIRA-2030"
                                                    onChange={(event) =>
                                                        updateItem(
                                                            'next_week',
                                                            index,
                                                            'issue_reference',
                                                            event.target.value,
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={getError(`next_week.${index}.issue_reference`)}
                                                    className="mt-2"
                                                />
                                            </div>
                                            <div>
                                                <Label>標籤（以逗號分隔）</Label>
                                                <Input
                                                    value={item.tagsText ?? ''}
                                                    placeholder="planning, research"
                                                    onChange={(event) =>
                                                        updateItem(
                                                            'next_week',
                                                            index,
                                                            'tagsText',
                                                            event.target.value,
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={getError(`next_week.${index}.tags`)}
                                                    className="mt-2"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => removeItem('next_week', index)}
                                        className="text-muted-foreground hover:text-destructive"
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                </div>
                            </div>
                        ))}
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
