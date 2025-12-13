<?php

namespace App\Http\Requests\Tenant;

use App\Models\Division;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDivisionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $division = $this->route('division');

        if (! $division instanceof Division) {
            return false;
        }

        return $this->user()?->can('update', $division) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->companyId();
        $division = $this->route('division');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('divisions')
                    ->where('company_id', $tenantId)
                    ->ignore($division?->id),
            ],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'sort_order' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ];
    }
}
