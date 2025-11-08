<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySetting extends Model
{
    use BelongsToTenant;
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'welcome_page',
        'login_ip_whitelist',
        'notification_preferences',
        'default_weekly_report_modules',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'welcome_page' => 'array',
            'login_ip_whitelist' => 'array',
            'notification_preferences' => 'array',
            'default_weekly_report_modules' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
