<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyReportItem extends Model
{
    use HasFactory;

    public const TYPE_CURRENT_WEEK = 'current_week';

    public const TYPE_NEXT_WEEK = 'next_week';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'weekly_report_id',
        'type',
        'sort_order',
        'title',
        'content',
        'hours_spent',
        'planned_hours',
        'issue_reference',
        'is_billable',
        'tags',
        'started_at',
        'ended_at',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hours_spent' => 'float',
            'planned_hours' => 'float',
            'is_billable' => 'boolean',
            'tags' => 'array',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function weeklyReport(): BelongsTo
    {
        return $this->belongsTo(WeeklyReport::class);
    }

    protected static function booted(): void
    {
        static::creating(function (self $item): void {
            $item->type ??= self::TYPE_CURRENT_WEEK;
        });
    }
}
