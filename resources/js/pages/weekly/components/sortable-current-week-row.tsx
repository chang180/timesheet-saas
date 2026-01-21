import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { isWeekend } from '@/lib/date-utils';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { ChevronUp, ChevronDown, Clock, GripVertical, Trash2 } from 'lucide-react';
import type { DateRange, GetErrorFn, MoveItemFn, RemoveItemFn, UpdateItemFn, WeeklyReportItemInput } from './types';

type SortableCurrentWeekRowProps = {
    item: WeeklyReportItemInput;
    index: number;
    totalItems: number;
    updateItem: UpdateItemFn;
    removeItem: RemoveItemFn;
    moveItem: MoveItemFn;
    getError: GetErrorFn;
    weekDateRange?: DateRange;
};

export function SortableCurrentWeekRow({
    item,
    index,
    totalItems,
    updateItem,
    removeItem,
    moveItem,
    getError,
    weekDateRange,
}: SortableCurrentWeekRowProps) {
    const startedAtIsWeekend = isWeekend(item.started_at);
    const endedAtIsWeekend = isWeekend(item.ended_at);
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
        id: item.localKey,
    });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition: transition || 'transform 350ms cubic-bezier(0.34, 1.56, 0.64, 1), opacity 200ms ease',
        opacity: isDragging ? 0.5 : 1,
        zIndex: isDragging ? 999 : 'auto',
    };

    const isFirst = index === 0;
    const isLast = index === totalItems - 1;

    return (
        <div
            ref={setNodeRef}
            style={style}
            className="group relative mb-4 rounded-xl border border-border/60 bg-card shadow-sm transition-all duration-300 hover:border-border hover:shadow-md will-change-transform"
        >
            {/* 卡片頭部 */}
            <div className="flex items-start gap-3 border-b border-border/40 bg-gradient-to-r from-primary/5 via-primary/3 to-transparent px-4 py-3 md:px-5">
                {/* 拖拉手柄 + 序號 */}
                <div className="flex items-center gap-2">
                    <div
                        {...attributes}
                        {...listeners}
                        className="flex cursor-move items-center gap-1 rounded px-1.5 py-1 text-muted-foreground transition-colors hover:bg-primary/10 hover:text-primary touch-none"
                        title="拖曳調整順序"
                    >
                        <GripVertical className="size-5" />
                    </div>
                    <div className="flex size-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-primary to-primary/80 text-sm font-bold text-primary-foreground shadow-sm">
                        {index + 1}
                    </div>
                </div>

                {/* 標題 */}
                <div className="min-w-0 flex-1">
                    <Input
                        value={item.title}
                        placeholder="輸入任務名稱..."
                        className="border-0 bg-transparent px-0 text-lg font-bold text-foreground shadow-none focus-visible:ring-0 focus-visible:ring-offset-0 placeholder:text-muted-foreground/50"
                        onChange={(event) => updateItem('current_week', index, 'title', event.target.value)}
                    />
                    <InputError message={getError(`current_week.${index}.title`)} className="mt-1" />
                </div>

                {/* 操作按鈕 */}
                <div className="flex shrink-0 items-center gap-1">
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        onClick={() => moveItem('current_week', index, 'up')}
                        disabled={isFirst}
                        className="size-8 text-muted-foreground hover:text-foreground disabled:opacity-30"
                        title="上移"
                    >
                        <ChevronUp className="size-4" />
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        onClick={() => moveItem('current_week', index, 'down')}
                        disabled={isLast}
                        className="size-8 text-muted-foreground hover:text-foreground disabled:opacity-30"
                        title="下移"
                    >
                        <ChevronDown className="size-4" />
                    </Button>
                    <div className="mx-1 h-5 w-px bg-border" />
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        onClick={() => removeItem('current_week', index)}
                        className="size-8 text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                        title="刪除"
                    >
                        <Trash2 className="size-4" />
                    </Button>
                </div>
            </div>

            {/* 卡片主體 */}
            <div className="space-y-4 p-4 md:p-5">
                {/* 詳細說明 */}
                <div>
                    <Label htmlFor={`card-content-${item.localKey}`} className="mb-1.5 flex items-center gap-1.5 text-sm font-semibold text-foreground">
                        <span className="inline-block size-2 rounded-full bg-blue-500" />
                        詳細說明
                    </Label>
                    <Textarea
                        id={`card-content-${item.localKey}`}
                        rows={3}
                        value={item.content ?? ''}
                        placeholder="描述任務的具體內容、完成狀況或遇到的問題..."
                        className="resize-none transition-colors focus:border-blue-500/50"
                        onChange={(event) => updateItem('current_week', index, 'content', event.target.value)}
                    />
                    <InputError message={getError(`current_week.${index}.content`)} className="mt-1" />
                </div>

                {/* 日期和工時區塊 */}
                <div className="rounded-lg border border-primary/20 bg-gradient-to-br from-primary/5 via-primary/3 to-transparent p-3.5">
                    <div className="mb-3 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-primary">
                        <Clock className="size-3.5" />
                        時間與工時
                    </div>
                    <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                        <div>
                            <Label htmlFor={`card-started-${item.localKey}`} className="mb-1.5 text-xs font-medium text-muted-foreground">
                                開始日期
                            </Label>
                            <DatePicker
                                id={`card-started-${item.localKey}`}
                                value={item.started_at ?? null}
                                onChange={(date) => updateItem('current_week', index, 'started_at', date)}
                                minDate={weekDateRange?.startDate}
                                maxDate={item.ended_at ?? weekDateRange?.endDate}
                                weekRange={weekDateRange}
                                placeholder="開始日"
                                className={`w-full transition-colors ${startedAtIsWeekend ? 'border-yellow-400 bg-yellow-50 dark:border-yellow-600 dark:bg-yellow-950/20' : ''}`}
                            />
                            {startedAtIsWeekend && <p className="mt-1 text-xs font-medium text-yellow-600 dark:text-yellow-400">⚠️ 週末</p>}
                            <InputError message={getError(`current_week.${index}.started_at`)} className="mt-1" />
                        </div>
                        <div>
                            <Label htmlFor={`card-ended-${item.localKey}`} className="mb-1.5 text-xs font-medium text-muted-foreground">
                                結束日期
                            </Label>
                            <DatePicker
                                id={`card-ended-${item.localKey}`}
                                value={item.ended_at ?? null}
                                onChange={(date) => updateItem('current_week', index, 'ended_at', date)}
                                minDate={item.started_at ?? weekDateRange?.startDate}
                                maxDate={weekDateRange?.endDate}
                                weekRange={weekDateRange}
                                placeholder="結束日"
                                className={`w-full transition-colors ${endedAtIsWeekend ? 'border-yellow-400 bg-yellow-50 dark:border-yellow-600 dark:bg-yellow-950/20' : ''}`}
                            />
                            {endedAtIsWeekend && <p className="mt-1 text-xs font-medium text-yellow-600 dark:text-yellow-400">⚠️ 週末</p>}
                            <InputError message={getError(`current_week.${index}.ended_at`)} className="mt-1" />
                        </div>
                        <div>
                            <Label htmlFor={`card-planned-${item.localKey}`} className="mb-1.5 text-xs font-medium text-muted-foreground">
                                預計工時
                            </Label>
                            <Input
                                id={`card-planned-${item.localKey}`}
                                type="number"
                                step="0.5"
                                min="0"
                                value={item.planned_hours ?? ''}
                                placeholder="0"
                                className="font-semibold text-blue-600 transition-colors dark:text-blue-400"
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
                        </div>
                        <div>
                            <Label htmlFor={`card-hours-${item.localKey}`} className="mb-1.5 text-xs font-semibold text-muted-foreground">
                                實際工時 <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id={`card-hours-${item.localKey}`}
                                type="number"
                                step="0.5"
                                min="0"
                                value={item.hours_spent}
                                placeholder="0"
                                className="border-primary/30 bg-primary/5 font-bold text-primary transition-colors focus:border-primary/50 dark:bg-primary/10"
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
                        </div>
                    </div>
                </div>

                {/* Issue 和標籤 */}
                <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div>
                        <Label htmlFor={`card-issue-${item.localKey}`} className="mb-1.5 flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                            <span className="inline-block size-1.5 rounded-full bg-purple-500" />
                            Issue 編號
                        </Label>
                        <Input
                            id={`card-issue-${item.localKey}`}
                            value={item.issue_reference ?? ''}
                            placeholder="例如：JIRA-1234"
                            className="font-mono text-sm transition-colors"
                            onChange={(event) => updateItem('current_week', index, 'issue_reference', event.target.value)}
                        />
                        <InputError message={getError(`current_week.${index}.issue_reference`)} className="mt-1" />
                    </div>
                    <div>
                        <Label htmlFor={`card-tags-${item.localKey}`} className="mb-1.5 flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                            <span className="inline-block size-1.5 rounded-full bg-green-500" />
                            標籤
                        </Label>
                        <Input
                            id={`card-tags-${item.localKey}`}
                            value={item.tagsText ?? ''}
                            placeholder="前端, 緊急, 重要..."
                            className="transition-colors"
                            onChange={(event) => updateItem('current_week', index, 'tagsText', event.target.value)}
                        />
                        <InputError message={getError(`current_week.${index}.tags`)} className="mt-1" />
                    </div>
                </div>
            </div>
        </div>
    );
}
