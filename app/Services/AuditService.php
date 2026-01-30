<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Log an audit event.
     *
     * @param  array<string, mixed>|null  $properties
     */
    public static function log(
        string $event,
        Model $auditable,
        ?string $description = null,
        ?array $properties = null,
    ): AuditLog {
        $user = auth()->user();

        return AuditLog::create([
            'company_id' => $auditable->company_id ?? $user?->company_id,
            'user_id' => $user?->id,
            'event' => $event,
            'description' => $description,
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => $auditable->getKey(),
            'properties' => $properties,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'occurred_at' => now(),
        ]);
    }

    /**
     * Log a create event.
     *
     * @param  array<string, mixed>|null  $properties
     */
    public static function created(Model $model, ?string $description = null, ?array $properties = null): AuditLog
    {
        return self::log(AuditLog::EVENT_CREATED, $model, $description, $properties);
    }

    /**
     * Log an update event.
     *
     * @param  array<string, mixed>|null  $properties
     */
    public static function updated(Model $model, ?string $description = null, ?array $properties = null): AuditLog
    {
        return self::log(AuditLog::EVENT_UPDATED, $model, $description, $properties);
    }

    /**
     * Log a delete event.
     *
     * @param  array<string, mixed>|null  $properties
     */
    public static function deleted(Model $model, ?string $description = null, ?array $properties = null): AuditLog
    {
        return self::log(AuditLog::EVENT_DELETED, $model, $description, $properties);
    }

    /**
     * Log an export event.
     *
     * @param  array<string, mixed>|null  $properties
     */
    public static function exported(Model $model, ?string $description = null, ?array $properties = null): AuditLog
    {
        return self::log(AuditLog::EVENT_EXPORTED, $model, $description, $properties);
    }

    /**
     * Log a submit event.
     *
     * @param  array<string, mixed>|null  $properties
     */
    public static function submitted(Model $model, ?string $description = null, ?array $properties = null): AuditLog
    {
        return self::log(AuditLog::EVENT_SUBMIT, $model, $description, $properties);
    }

    /**
     * Log a reopen event.
     *
     * @param  array<string, mixed>|null  $properties
     */
    public static function reopened(Model $model, ?string $description = null, ?array $properties = null): AuditLog
    {
        return self::log(AuditLog::EVENT_REOPEN, $model, $description, $properties);
    }
}
