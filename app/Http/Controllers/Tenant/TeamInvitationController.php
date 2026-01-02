<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\GenerateInvitationRequest;
use App\Http\Requests\Tenant\ToggleInvitationRequest;
use App\Models\Company;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamInvitationController extends Controller
{
    /**
     * Get invitation link information for a team.
     */
    public function show(Request $request, Company $company, Team $team): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->belongsToCompany($company->getKey())) {
            abort(403, __('You are not allowed to access this tenant.'));
        }

        if (! $user->isCompanyAdmin() && ! ($user->hasRole('team_lead') && $user->team_id === $team->id)) {
            abort(403, __('You are not authorized to manage this team invitation.'));
        }

        return response()->json([
            'invitation_token' => $team->invitation_token,
            'invitation_enabled' => (bool) $team->invitation_enabled,
            'invitation_url' => $team->getInvitationUrl(),
        ]);
    }

    /**
     * Generate invitation token for a team.
     */
    public function generate(GenerateInvitationRequest $request, Company $company, Team $team): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->belongsToCompany($company->getKey())) {
            abort(403, __('You are not allowed to access this tenant.'));
        }

        if (! $user->isCompanyAdmin() && ! ($user->hasRole('team_lead') && $user->team_id === $team->id)) {
            abort(403, __('You are not authorized to manage this team invitation.'));
        }

        $token = $team->generateInvitationToken();

        return response()->json([
            'message' => '邀請連結已生成',
            'invitation_token' => $token,
            'invitation_url' => $team->fresh()->getInvitationUrl(),
        ]);
    }

    /**
     * Toggle invitation link for a team.
     */
    public function toggle(ToggleInvitationRequest $request, Company $company, Team $team): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->belongsToCompany($company->getKey())) {
            abort(403, __('You are not allowed to access this tenant.'));
        }

        if (! $user->isCompanyAdmin() && ! ($user->hasRole('team_lead') && $user->team_id === $team->id)) {
            abort(403, __('You are not authorized to manage this team invitation.'));
        }

        $enabled = $request->validated('enabled');

        if ($enabled) {
            $team->enableInvitation();
        } else {
            $team->disableInvitation();
        }

        return response()->json([
            'message' => $enabled ? '邀請連結已啟用' : '邀請連結已停用',
            'invitation_enabled' => (bool) $team->fresh()->invitation_enabled,
            'invitation_url' => $team->getInvitationUrl(),
        ]);
    }
}
