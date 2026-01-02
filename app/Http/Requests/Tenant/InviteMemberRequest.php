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
        $user = $this->user();

        if (! $user) {
            return false;
        }

        // Company admin can always invite
        if ($user->isCompanyAdmin()) {
            return true;
        }

        // Level managers can invite to their level or below
        $divisionId = $this->input('division_id');
        $departmentId = $this->input('department_id');
        $teamId = $this->input('team_id');

        return $user->canManageHierarchy($divisionId, $departmentId, $teamId);
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
