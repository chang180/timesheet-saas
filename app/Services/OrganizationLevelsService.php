<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Department;
use App\Models\Division;
use App\Models\Team;
use App\Models\User;
use App\Models\WeeklyReport;

class OrganizationLevelsService
{
    /**
     * Clear data for the given levels (team -> department -> division order).
     *
     * @param  array<int, string>  $levels  ['division', 'department', 'team']
     */
    public function clearLevelData(Company $company, array $levels): void
    {
        $id = $company->getKey();

        if (in_array('team', $levels, true)) {
            User::where('company_id', $id)->update(['team_id' => null]);
            WeeklyReport::where('company_id', $id)->update(['team_id' => null]);
            Team::where('company_id', $id)->delete();
        }

        if (in_array('department', $levels, true)) {
            User::where('company_id', $id)->update(['department_id' => null, 'team_id' => null]);
            WeeklyReport::where('company_id', $id)->update(['department_id' => null, 'team_id' => null]);
            Team::where('company_id', $id)->delete();
            Department::where('company_id', $id)->delete();
        }

        if (in_array('division', $levels, true)) {
            User::where('company_id', $id)->update(['division_id' => null, 'department_id' => null, 'team_id' => null]);
            WeeklyReport::where('company_id', $id)->update(['division_id' => null, 'department_id' => null, 'team_id' => null]);
            Team::where('company_id', $id)->delete();
            Department::where('company_id', $id)->delete();
            Division::where('company_id', $id)->delete();
        }
    }
}
