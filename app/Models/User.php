<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens;

    use HasFactory;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'division_id',
        'department_id',
        'team_id',
        'name',
        'email',
        'password',
        'role',
        'position',
        'phone',
        'timezone',
        'locale',
        'registered_via',
        'onboarded_at',
        'last_active_at',
        'invitation_token',
        'invitation_sent_at',
        'invitation_accepted_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'onboarded_at' => 'datetime',
            'last_active_at' => 'datetime',
            'invitation_sent_at' => 'datetime',
            'invitation_accepted_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function weeklyReports(): HasMany
    {
        return $this->hasMany(WeeklyReport::class);
    }

    public function submittedWeeklyReports(): HasMany
    {
        return $this->hasMany(WeeklyReport::class, 'submitted_by');
    }

    public function approvedWeeklyReports(): HasMany
    {
        return $this->hasMany(WeeklyReport::class, 'approved_by');
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isHqAdmin(): bool
    {
        return $this->role === 'hq_admin';
    }

    public function isCompanyAdmin(): bool
    {
        return $this->role === 'company_admin';
    }

    public function belongsToCompany(int $companyId): bool
    {
        return $this->company_id === $companyId;
    }

    public function canManageHierarchy(?int $divisionId, ?int $departmentId, ?int $teamId): bool
    {
        if ($this->isCompanyAdmin()) {
            return true;
        }

        if ($this->hasRole('division_lead') && $divisionId !== null && $this->division_id === $divisionId) {
            return true;
        }

        if ($this->hasRole('department_manager') && $departmentId !== null && $this->department_id === $departmentId) {
            return true;
        }

        if ($this->hasRole('team_lead') && $teamId !== null && $this->team_id === $teamId) {
            return true;
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public static function tenantAssignableRoles(): array
    {
        return [
            'member',
            'team_lead',
            'department_manager',
            'division_lead',
            'company_admin',
        ];
    }

    /**
     * @return list<string>
     */
    public static function allRoles(): array
    {
        return [
            ...self::tenantAssignableRoles(),
            'hq_admin',
        ];
    }
}
