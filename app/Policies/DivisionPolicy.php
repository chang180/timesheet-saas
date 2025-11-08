<?php

namespace App\Policies;

use App\Models\Division;
use App\Models\User;

class DivisionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null;
    }

    public function view(User $user, Division $division): bool
    {
        return $this->sameCompany($user, $division);
    }

    public function create(User $user): bool
    {
        return $user->isCompanyAdmin();
    }

    public function update(User $user, Division $division): bool
    {
        if (! $this->sameCompany($user, $division)) {
            return false;
        }

        return $user->isCompanyAdmin() || ($user->hasRole('division_lead') && $user->division_id === $division->getKey());
    }

    public function delete(User $user, Division $division): bool
    {
        if (! $this->sameCompany($user, $division)) {
            return false;
        }

        return $user->isCompanyAdmin();
    }

    public function restore(User $user, Division $division): bool
    {
        return $this->delete($user, $division);
    }

    public function forceDelete(User $user, Division $division): bool
    {
        return $user->isCompanyAdmin();
    }

    private function sameCompany(User $user, Division $division): bool
    {
        return $user->belongsToCompany((int) $division->company_id);
    }
}
