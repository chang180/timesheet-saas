<?php

namespace App\Http\Requests\Tenant;

use App\Models\Department;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $department = $this->route('department');

        if (! $department instanceof Department) {
            return false;
        }

        return $this->user()?->can('update', $department) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->companyId();
        $department = $this->route('department');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'division_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('divisions', 'id')->where('company_id', $tenantId),
            ],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('departments')
                    ->where('company_id', $tenantId)
                    ->ignore($department?->id),
            ],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'sort_order' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ];
    }
}
