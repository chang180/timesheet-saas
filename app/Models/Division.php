<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Division extends Model
{
    use BelongsToTenant;
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'description',
        'sort_order',
        'is_active',
        'invitation_token',
        'invitation_enabled',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'invitation_enabled' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function weeklyReports(): HasMany
    {
        return $this->hasMany(WeeklyReport::class);
    }

    /**
     * Generate a unique invitation token for this division.
     */
    public function generateInvitationToken(): string
    {
        do {
            $token = bin2hex(random_bytes(32));
        } while (self::where('invitation_token', $token)->exists());

        $this->update([
            'invitation_token' => $token,
        ]);

        return $token;
    }

    /**
     * Get the full invitation URL for this division.
     */
    public function getInvitationUrl(): ?string
    {
        if (! $this->invitation_token) {
            return null;
        }

        return route('tenant.register-by-invitation', [
            'company' => $this->company->slug,
            'token' => $this->invitation_token,
            'type' => 'division',
        ]);
    }

    /**
     * Enable invitation link.
     */
    public function enableInvitation(): bool
    {
        if (! $this->invitation_token) {
            $this->generateInvitationToken();
        }

        return $this->update(['invitation_enabled' => true]);
    }

    /**
     * Disable invitation link.
     */
    public function disableInvitation(): bool
    {
        return $this->update(['invitation_enabled' => false]);
    }
}
