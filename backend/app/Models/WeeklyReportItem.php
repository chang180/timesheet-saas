<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeeklyReportItem extends Model
{
    protected $fillable = [
        'weekly_report_id',
        'type',
        'task_description',
        'hours_spent',
        'sort_order',
        'redmine_issue_id',
    ];

    protected function casts(): array
    {
        return [
            'hours_spent' => 'decimal:2',
        ];
    }

    public function weeklyReport()
    {
        return $this->belongsTo(WeeklyReport::class);
    }
}

