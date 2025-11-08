<?php

namespace App\Http\Requests;

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
            'ipAddresses' => ['required', 'array', 'max:5'],
            'ipAddresses.*' => ['required', 'string', 'ip'],
        ];
    }

    public function messages(): array
    {
        return [
            'ipAddresses.required' => 'IP 位址清單為必填',
            'ipAddresses.max' => 'IP 白名單最多 5 組',
            'ipAddresses.*.ip' => '請輸入有效的 IP 位址',
        ];
    }
}
