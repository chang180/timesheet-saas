<?php

namespace App\Http\Requests\Tenant;

use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCompanyAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->companyId();

        return [
            'role' => ['required', Rule::in(User::tenantAssignableRoles())],
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
        ];
    }

    /**
     * @return array{role: string, division_id: int|null, department_id: int|null, team_id: int|null}
     */
    public function payload(): array
    {
        $data = $this->validated();

        return [
            'role' => $data['role'],
            'division_id' => $data['division_id'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'team_id' => $data['team_id'] ?? null,
        ];
    }
}
