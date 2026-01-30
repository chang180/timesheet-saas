<?php

namespace App\Http\Requests\Hq;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isHqAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'suspended', 'onboarding'])],
            'user_limit' => ['sometimes', 'integer', 'min:1', 'max:65535'],
            'timezone' => ['sometimes', 'string', 'timezone:all'],
            'branding' => ['sometimes', 'nullable', 'array'],
            'branding.logo_url' => ['sometimes', 'nullable', 'string', 'url', 'max:500'],
            'branding.primary_color' => ['sometimes', 'nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'branding.company_name_display' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'branding.primary_color.regex' => 'The primary color must be a valid hex color (e.g., #FF5733).',
        ];
    }
}
