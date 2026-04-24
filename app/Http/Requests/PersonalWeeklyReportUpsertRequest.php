<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class PersonalWeeklyReportUpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && $this->user()->isPersonal();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'work_year' => ['required', 'integer', 'between:2000,2100'],
            'work_week' => ['required', 'integer', 'between:1,53'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'current_week' => ['array'],
            'current_week.*.id' => ['nullable', 'integer', 'exists:weekly_report_items,id'],
            'current_week.*.title' => ['required', 'string', 'max:255'],
            'current_week.*.content' => ['nullable', 'string'],
            'current_week.*.hours_spent' => ['nullable', 'numeric', 'between:0,200'],
            'current_week.*.planned_hours' => ['nullable', 'numeric', 'between:0,200'],
            'current_week.*.issue_reference' => ['nullable', 'string', 'max:191'],
            'current_week.*.is_billable' => ['nullable', 'boolean'],
            'current_week.*.tags' => ['nullable', 'array'],
            'current_week.*.tags.*' => ['string', 'max:50'],
            'current_week.*.started_at' => ['nullable', 'date'],
            'current_week.*.ended_at' => ['nullable', 'date'],
            'next_week' => ['array'],
            'next_week.*.id' => ['nullable', 'integer', 'exists:weekly_report_items,id'],
            'next_week.*.title' => ['required', 'string', 'max:255'],
            'next_week.*.content' => ['nullable', 'string'],
            'next_week.*.planned_hours' => ['nullable', 'numeric', 'between:0,200'],
            'next_week.*.issue_reference' => ['nullable', 'string', 'max:191'],
            'next_week.*.tags' => ['nullable', 'array'],
            'next_week.*.tags.*' => ['string', 'max:50'],
            'next_week.*.started_at' => ['nullable', 'date'],
            'next_week.*.ended_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ];

        foreach ((array) $this->input('current_week', []) as $index => $item) {
            if (! empty($item['started_at']) && ! empty($item['ended_at'])) {
                $rules["current_week.{$index}.ended_at"][] = function (string $attribute, mixed $value, Closure $fail) use ($item): void {
                    if (strtotime((string) $value) < strtotime((string) $item['started_at'])) {
                        $fail('結束日期不能早於開始日期。');
                    }
                };
            }
        }

        foreach ((array) $this->input('next_week', []) as $index => $item) {
            if (! empty($item['started_at']) && ! empty($item['ended_at'])) {
                $rules["next_week.{$index}.ended_at"][] = function (string $attribute, mixed $value, Closure $fail) use ($item): void {
                    if (strtotime((string) $value) < strtotime((string) $item['started_at'])) {
                        $fail('結束日期不能早於開始日期。');
                    }
                };
            }
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'work_year' => (int) $this->input('work_year'),
            'work_week' => (int) $this->input('work_week'),
            'summary' => $this->input('summary'),
            'metadata' => $this->input('metadata', []),
            'current_week' => $this->normalizeCurrentWeekItems(),
            'next_week' => $this->normalizeNextWeekItems(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function normalizeCurrentWeekItems(): array
    {
        return collect((array) $this->input('current_week', []))
            ->map(fn (array $item): array => [
                'id' => $item['id'] ?? null,
                'title' => trim((string) $item['title']),
                'content' => $item['content'] ?? null,
                'hours_spent' => isset($item['hours_spent']) ? (float) $item['hours_spent'] : 0.0,
                'planned_hours' => isset($item['planned_hours']) ? (float) $item['planned_hours'] : null,
                'issue_reference' => $item['issue_reference'] ?? null,
                'is_billable' => (bool) ($item['is_billable'] ?? false),
                'tags' => $this->sanitizeTags((array) ($item['tags'] ?? [])),
                'started_at' => $item['started_at'] ?? null,
                'ended_at' => $item['ended_at'] ?? null,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function normalizeNextWeekItems(): array
    {
        return collect((array) $this->input('next_week', []))
            ->map(fn (array $item): array => [
                'id' => $item['id'] ?? null,
                'title' => trim((string) $item['title']),
                'content' => $item['content'] ?? null,
                'planned_hours' => isset($item['planned_hours']) ? (float) $item['planned_hours'] : null,
                'issue_reference' => $item['issue_reference'] ?? null,
                'tags' => $this->sanitizeTags((array) ($item['tags'] ?? [])),
                'started_at' => $item['started_at'] ?? null,
                'ended_at' => $item['ended_at'] ?? null,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>  $tags
     * @return array<int, string>
     */
    private function sanitizeTags(array $tags): array
    {
        return collect($tags)
            ->map(fn ($tag) => trim((string) $tag))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
