<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\GenerateInvitationRequest;
use App\Http\Requests\Tenant\ToggleInvitationRequest;
use App\Models\Company;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentInvitationController extends Controller
{
    /**
     * Get invitation link information for a department.
     */
    public function show(Request $request, Company $company, Department $department): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->belongsToCompany($company->getKey())) {
            abort(403, __('You are not allowed to access this tenant.'));
        }

        if (! $user->isCompanyAdmin() && ! ($user->hasRole('department_manager') && $user->department_id === $department->id)) {
            abort(403, __('You are not authorized to manage this department invitation.'));
        }

        return response()->json([
            'invitation_token' => $department->invitation_token,
            'invitation_enabled' => (bool) $department->invitation_enabled,
            'invitation_url' => $department->getInvitationUrl(),
        ]);
    }

    /**
     * Generate invitation token for a department.
     */
    public function generate(GenerateInvitationRequest $request, Company $company, Department $department): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->belongsToCompany($company->getKey())) {
            abort(403, __('You are not allowed to access this tenant.'));
        }

        if (! $user->isCompanyAdmin() && ! ($user->hasRole('department_manager') && $user->department_id === $department->id)) {
            abort(403, __('You are not authorized to manage this department invitation.'));
        }

        $token = $department->generateInvitationToken();

        return response()->json([
            'message' => '邀請連結已生成',
            'invitation_token' => $token,
            'invitation_url' => $department->fresh()->getInvitationUrl(),
        ]);
    }

    /**
     * Toggle invitation link for a department.
     */
    public function toggle(ToggleInvitationRequest $request, Company $company, Department $department): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->belongsToCompany($company->getKey())) {
            abort(403, __('You are not allowed to access this tenant.'));
        }

        if (! $user->isCompanyAdmin() && ! ($user->hasRole('department_manager') && $user->department_id === $department->id)) {
            abort(403, __('You are not authorized to manage this department invitation.'));
        }

        $enabled = $request->validated('enabled');

        if ($enabled) {
            $department->enableInvitation();
        } else {
            $department->disableInvitation();
        }

        return response()->json([
            'message' => $enabled ? '邀請連結已啟用' : '邀請連結已停用',
            'invitation_enabled' => (bool) $department->fresh()->invitation_enabled,
            'invitation_url' => $department->getInvitationUrl(),
        ]);
    }
}
