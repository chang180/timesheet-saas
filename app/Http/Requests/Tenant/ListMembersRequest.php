<?php

namespace App\Http\Requests\Tenant;

use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListMembersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        return $user->company_id !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->companyId();

        return [
            'role' => ['nullable', 'string', Rule::in(['member', 'team_lead', 'department_manager', 'division_lead', 'company_admin'])],
            'division_id' => [
                'nullable',
                'integer',
                Rule::exists('divisions', 'id')->where('company_id', $tenantId),
            ],
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where('company_id', $tenantId),
            ],
            'team_id' => [
                'nullable',
                'integer',
                Rule::exists('teams', 'id')->where('company_id', $tenantId),
            ],
            'keyword' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
