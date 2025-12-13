<?php

namespace App\Http\Requests\Tenant;

use App\Http\Requests\UpdateWelcomePageRequest as BaseRequest;
use App\Models\Company;

class UpdateWelcomePageRequest extends BaseRequest
{
    public function authorize(): bool
    {
        /** @var Company|null $company */
        $company = $this->route('company');

        return $company
            ? ($this->user()?->can('update', $company) ?? false)
            : ($this->user()?->can('update', $this->user()?->company) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function welcomePagePayload(): array
    {
        return $this->validated();
    }
}
