<?php

declare(strict_types=1);

namespace App\Tenancy;

use App\Models\Company;
use App\Models\CompanySetting;

class TenantContext
{
    public function __construct(
        protected Company $company,
        protected ?CompanySetting $settings = null,
    ) {
        $this->settings ??= $company->settings;
    }

    public static function fromCompany(Company $company): self
    {
        return new self($company, $company->settings);
    }

    public function company(): Company
    {
        return $this->company;
    }

    public function settings(): ?CompanySetting
    {
        return $this->settings;
    }

    public function companyId(): int
    {
        return $this->company->getKey();
    }

    public function slug(): string
    {
        return $this->company->slug;
    }

    public function timezone(): string
    {
        return $this->company->timezone;
    }

    public function userLimit(): int
    {
        return $this->company->user_limit;
    }

    public function currentUserCount(): int
    {
        return $this->company->current_user_count;
    }

    public function isActive(): bool
    {
        return $this->company->status === 'active';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'company' => $this->company->toArray(),
            'settings' => $this->settings?->toArray(),
        ];
    }
}
