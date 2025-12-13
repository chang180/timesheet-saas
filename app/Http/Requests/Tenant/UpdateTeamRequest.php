<?php

namespace App\Http\Requests\Tenant;

use App\Models\Team;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $team = $this->route('team');

        if (! $team instanceof Team) {
            return false;
        }

        return $this->user()?->can('update', $team) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->companyId();
        $team = $this->route('team');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'department_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where('company_id', $tenantId),
            ],
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
                Rule::unique('teams')
                    ->where('company_id', $tenantId)
                    ->ignore($team?->id),
            ],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'sort_order' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ];
    }
}
