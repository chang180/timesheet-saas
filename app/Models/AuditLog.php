<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use BelongsToTenant;
    use HasFactory;

    public const EVENT_CREATED = 'created';

    public const EVENT_UPDATED = 'updated';

    public const EVENT_DELETED = 'deleted';

    public const EVENT_EXPORTED = 'exported';

    public const EVENT_IMPORTED = 'imported';

    public const EVENT_LOGIN = 'login';

    public const EVENT_LOGOUT = 'logout';

    public const EVENT_REOPEN = 'reopen';

    public const EVENT_SUBMIT = 'submit';

    public const EVENT_IP_WHITELIST_REJECTED = 'auth.ip_whitelist.rejected';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'user_id',
        'event',
        'description',
        'auditable_type',
        'auditable_id',
        'properties',
        'ip_address',
        'user_agent',
        'occurred_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
