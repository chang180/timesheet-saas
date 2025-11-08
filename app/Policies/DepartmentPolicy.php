<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null;
    }

    public function view(User $user, Department $department): bool
    {
        return $this->sameCompany($user, $department);
    }

    public function create(User $user): bool
    {
        return $user->isCompanyAdmin();
    }

    public function update(User $user, Department $department): bool
    {
        if (! $this->sameCompany($user, $department)) {
            return false;
        }

        if ($user->isCompanyAdmin()) {
            return true;
        }

        if ($user->hasRole('division_lead') && $department->division_id !== null) {
            return $user->division_id === $department->division_id;
        }

        if ($user->hasRole('department_manager')) {
            return $user->department_id === $department->getKey();
        }

        return false;
    }

    public function delete(User $user, Department $department): bool
    {
        if (! $this->sameCompany($user, $department)) {
            return false;
        }

        return $user->isCompanyAdmin();
    }

    public function restore(User $user, Department $department): bool
    {
        return $this->delete($user, $department);
    }

    public function forceDelete(User $user, Department $department): bool
    {
        return $user->isCompanyAdmin();
    }

    private function sameCompany(User $user, Department $department): bool
    {
        return $user->belongsToCompany((int) $department->company_id);
    }
}
