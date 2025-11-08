<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIpWhitelistRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isCompanyAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'entries' => ['nullable', 'array', 'max:5'],
            'entries.*' => ['string', new \App\Rules\IpAddressOrCidr],
        ];
    }

    /**
     * @return list<string>
     */
    public function whitelist(): array
    {
        return array_values($this->validated('entries', []));
    }
}
