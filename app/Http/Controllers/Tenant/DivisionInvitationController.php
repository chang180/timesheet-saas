<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\GenerateInvitationRequest;
use App\Http\Requests\Tenant\ToggleInvitationRequest;
use App\Models\Company;
use App\Models\Division;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DivisionInvitationController extends Controller
{
    /**
     * Get invitation link information for a division.
     */
    public function show(Request $request, Company $company, Division $division): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->belongsToCompany($company->getKey())) {
            abort(403, __('You are not allowed to access this tenant.'));
        }

        if (! $user->isCompanyAdmin() && ! ($user->hasRole('division_lead') && $user->division_id === $division->id)) {
            abort(403, __('You are not authorized to manage this division invitation.'));
        }

        return response()->json([
            'invitation_token' => $division->invitation_token,
            'invitation_enabled' => (bool) $division->invitation_enabled,
            'invitation_url' => $division->getInvitationUrl(),
        ]);
    }

    /**
     * Generate invitation token for a division.
     */
    public function generate(GenerateInvitationRequest $request, Company $company, Division $division): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->belongsToCompany($company->getKey())) {
            abort(403, __('You are not allowed to access this tenant.'));
        }

        if (! $user->isCompanyAdmin() && ! ($user->hasRole('division_lead') && $user->division_id === $division->id)) {
            abort(403, __('You are not authorized to manage this division invitation.'));
        }

        $token = $division->generateInvitationToken();

        return response()->json([
            'message' => '邀請連結已生成',
            'invitation_token' => $token,
            'invitation_url' => $division->fresh()->getInvitationUrl(),
        ]);
    }

    /**
     * Toggle invitation link for a division.
     */
    public function toggle(ToggleInvitationRequest $request, Company $company, Division $division): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->belongsToCompany($company->getKey())) {
            abort(403, __('You are not allowed to access this tenant.'));
        }

        if (! $user->isCompanyAdmin() && ! ($user->hasRole('division_lead') && $user->division_id === $division->id)) {
            abort(403, __('You are not authorized to manage this division invitation.'));
        }

        $enabled = $request->validated('enabled');

        if ($enabled) {
            $division->enableInvitation();
        } else {
            $division->disableInvitation();
        }

        return response()->json([
            'message' => $enabled ? '邀請連結已啟用' : '邀請連結已停用',
            'invitation_enabled' => (bool) $division->fresh()->invitation_enabled,
            'invitation_url' => $division->getInvitationUrl(),
        ]);
    }
}
