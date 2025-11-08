<?php

namespace App\Http\Requests;

use App\Rules\IpAddressOrCidr;
use Illuminate\Foundation\Http\FormRequest;

class UpdateIPWhitelistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->user()->company);
    }

    public function rules(): array
    {
        return [
            'ipAddresses' => ['nullable', 'array', 'max:5'],
            'ipAddresses.*' => ['string', new IpAddressOrCidr()],
        ];
    }

    public function messages(): array
    {
        return [
            'ipAddresses.max' => 'IP 白名單最多 5 組',
            'ipAddresses.*' => '請輸入有效的 IP 位址或 CIDR 範圍',
        ];
    }

    /**
     * @return list<string>
     */
    public function whitelist(): array
    {
        return array_values($this->validated('ipAddresses', []));
    }
}
