<?php

namespace App\Http\Requests\Tenant;

use App\Http\Requests\UpdateIPWhitelistRequest as BaseRequest;
use App\Models\Company;

class UpdateIpWhitelistRequest extends BaseRequest
{
    public function authorize(): bool
    {
        /** @var Company|null $company */
        $company = $this->route('company');

        return $company
            ? ($this->user()?->can('update', $company) ?? false)
            : ($this->user()?->can('update', $this->user()?->company) ?? false);
    }

}
