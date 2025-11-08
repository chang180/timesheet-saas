<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WeeklyReport;

class WeeklyReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null;
    }

    public function view(User $user, WeeklyReport $weeklyReport): bool
    {
        if (! $this->sameCompany($user, $weeklyReport)) {
            return false;
        }

        if ($user->id === $weeklyReport->user_id) {
            return true;
        }

        return $user->canManageHierarchy(
            $weeklyReport->division_id,
            $weeklyReport->department_id,
            $weeklyReport->team_id
        );
    }

    public function create(User $user): bool
    {
        return $user->company_id !== null;
    }

    public function update(User $user, WeeklyReport $weeklyReport): bool
    {
        if (! $this->sameCompany($user, $weeklyReport)) {
            return false;
        }

        if ($user->id === $weeklyReport->user_id && $weeklyReport->status === WeeklyReport::STATUS_DRAFT) {
            return true;
        }

        if ($weeklyReport->status === WeeklyReport::STATUS_LOCKED) {
            return false;
        }

        return $user->canManageHierarchy(
            $weeklyReport->division_id,
            $weeklyReport->department_id,
            $weeklyReport->team_id
        );
    }

    public function submit(User $user, WeeklyReport $weeklyReport): bool
    {
        if (! $this->sameCompany($user, $weeklyReport)) {
            return false;
        }

        if ($user->id === $weeklyReport->user_id && $weeklyReport->status === WeeklyReport::STATUS_DRAFT) {
            return true;
        }

        return $user->canManageHierarchy(
            $weeklyReport->division_id,
            $weeklyReport->department_id,
            $weeklyReport->team_id
        );
    }

    public function reopen(User $user, WeeklyReport $weeklyReport): bool
    {
        if (! $this->sameCompany($user, $weeklyReport)) {
            return false;
        }

        if ($weeklyReport->status === WeeklyReport::STATUS_DRAFT) {
            return false;
        }

        return $user->canManageHierarchy(
            $weeklyReport->division_id,
            $weeklyReport->department_id,
            $weeklyReport->team_id
        );
    }

    public function delete(User $user, WeeklyReport $weeklyReport): bool
    {
        if (! $this->sameCompany($user, $weeklyReport)) {
            return false;
        }

        if ($user->id === $weeklyReport->user_id && $weeklyReport->status === WeeklyReport::STATUS_DRAFT) {
            return true;
        }

        return $user->canManageHierarchy(
            $weeklyReport->division_id,
            $weeklyReport->department_id,
            $weeklyReport->team_id
        );
    }

    public function export(User $user, ?WeeklyReport $weeklyReport = null): bool
    {
        if ($user->isCompanyAdmin()) {
            return true;
        }

        if (! $weeklyReport) {
            return $user->hasRole('division_lead', 'department_manager', 'team_lead');
        }

        return $this->sameCompany($user, $weeklyReport) && $user->canManageHierarchy(
            $weeklyReport->division_id,
            $weeklyReport->department_id,
            $weeklyReport->team_id
        );
    }

    public function restore(User $user, WeeklyReport $weeklyReport): bool
    {
        return $this->delete($user, $weeklyReport);
    }

    public function forceDelete(User $user, WeeklyReport $weeklyReport): bool
    {
        return $user->isCompanyAdmin();
    }

    private function sameCompany(User $user, WeeklyReport $weeklyReport): bool
    {
        return $user->belongsToCompany((int) $weeklyReport->company_id);
    }
}
