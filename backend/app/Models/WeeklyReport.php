<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WeeklyReport extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'department_id',
        'week_start_date',
        'week_end_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'week_start_date' => 'date',
            'week_end_date' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function items()
    {
        return $this->hasMany(WeeklyReportItem::class)->orderBy('sort_order');
    }

    public function currentWeekItems()
    {
        return $this->hasMany(WeeklyReportItem::class)->where('type', 'current_week')->orderBy('sort_order');
    }

    public function nextWeekItems()
    {
        return $this->hasMany(WeeklyReportItem::class)->where('type', 'next_week')->orderBy('sort_order');
    }
}

