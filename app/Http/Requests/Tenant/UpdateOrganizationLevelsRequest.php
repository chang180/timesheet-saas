<?php

namespace App\Http\Requests\Tenant;

use App\Models\Company;
use App\Models\Department;
use App\Models\Division;
use App\Models\Team;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationLevelsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isCompanyAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'organization_levels' => [
                'required',
                'array',
            ],
            'organization_levels.*' => [
                'string',
                Rule::in(['division', 'department', 'team']),
            ],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $tenantId = app(TenantContext::class)->companyId();
            $newLevels = $this->input('organization_levels', []);
            $settings = Company::find($tenantId)?->settings;
            $currentLevels = $settings?->getEnabledLevels() ?? ['department'];

            // Check if removing a level that has data
            $removedLevels = array_diff($currentLevels, $newLevels);

            foreach ($removedLevels as $level) {
                if ($level === 'division' && Division::where('company_id', $tenantId)->exists()) {
                    $validator->errors()->add('organization_levels', '無法移除「事業群」層級，因為已有事業群資料。');
                } elseif ($level === 'department' && Department::where('company_id', $tenantId)->exists()) {
                    $validator->errors()->add('organization_levels', '無法移除「部門」層級，因為已有部門資料。');
                } elseif ($level === 'team' && Team::where('company_id', $tenantId)->exists()) {
                    $validator->errors()->add('organization_levels', '無法移除「小組」層級，因為已有小組資料。');
                }
            }
        });
    }
}
