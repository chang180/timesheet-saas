<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null;
    }

    public function view(User $user, Team $team): bool
    {
        return $this->sameCompany($user, $team);
    }

    public function create(User $user): bool
    {
        return $user->isCompanyAdmin();
    }

    public function update(User $user, Team $team): bool
    {
        if (! $this->sameCompany($user, $team)) {
            return false;
        }

        if ($user->isCompanyAdmin()) {
            return true;
        }

        if ($user->hasRole('division_lead') && $team->division_id !== null) {
            return $user->division_id === $team->division_id;
        }

        if ($user->hasRole('department_manager') && $team->department_id !== null) {
            return $user->department_id === $team->department_id;
        }

        if ($user->hasRole('team_lead')) {
            return $user->team_id === $team->getKey();
        }

        return false;
    }

    public function delete(User $user, Team $team): bool
    {
        if (! $this->sameCompany($user, $team)) {
            return false;
        }

        if ($user->isCompanyAdmin()) {
            return true;
        }

        if ($user->hasRole('division_lead') && $team->division_id !== null) {
            return $user->division_id === $team->division_id;
        }

        if ($user->hasRole('department_manager') && $team->department_id !== null) {
            return $user->department_id === $team->department_id;
        }

        return false;
    }

    public function restore(User $user, Team $team): bool
    {
        return $this->delete($user, $team);
    }

    public function forceDelete(User $user, Team $team): bool
    {
        return $user->isCompanyAdmin();
    }

    private function sameCompany(User $user, Team $team): bool
    {
        return $user->belongsToCompany((int) $team->company_id);
    }
}
