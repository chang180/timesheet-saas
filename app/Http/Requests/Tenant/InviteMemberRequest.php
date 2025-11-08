<?php

namespace App\Http\Requests\Tenant;

use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InviteMemberRequest extends FormRequest
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
            'email' => ['required', 'email:strict,rfc', 'max:255'],
            'name' => ['required', 'string', 'max:120'],
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

    public function invitedRole(): string
    {
        return $this->validated('role');
    }

    public function invitedEmail(): string
    {
        return $this->validated('email');
    }

    public function invitedName(): string
    {
        return $this->validated('name');
    }
}
