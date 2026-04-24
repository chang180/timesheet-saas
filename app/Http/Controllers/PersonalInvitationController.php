<?php

namespace App\Http\Controllers;

use App\Actions\Tenant\AcceptInvitationAsPersonalUserAction;
use App\Models\Company;
use App\Models\Department;
use App\Models\Division;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PersonalInvitationController extends Controller
{
    public function __construct(
        private readonly AcceptInvitationAsPersonalUserAction $acceptInvitation,
    ) {}

    public function showEmailInvitation(Request $request, string $token): Response|RedirectResponse
    {
        $user = $request->user();

        $invite = User::query()
            ->where('email', $user->email)
            ->where('invitation_token', $token)
            ->whereNull('invitation_accepted_at')
            ->with('company')
            ->first();

        if (! $invite) {
            abort(404, '邀請連結無效或已過期。');
        }

        if ($invite->invitation_sent_at && $invite->invitation_sent_at->addDays(7)->isPast()) {
            abort(410, '此邀請連結已逾期。');
        }

        return Inertia::render('personal/invitations/accept-email', [
            'company' => [
                'name' => $invite->company->name,
                'slug' => $invite->company->slug,
            ],
            'token' => $token,
            'inviteUserId' => $invite->id,
        ]);
    }

    public function acceptEmailInvitation(Request $request, string $token): RedirectResponse
    {
        $personalUser = $request->user();

        $invite = User::query()
            ->where('email', $personalUser->email)
            ->where('invitation_token', $token)
            ->whereNull('invitation_accepted_at')
            ->with('company')
            ->first();

        if (! $invite) {
            abort(404, '邀請連結無效或已過期。');
        }

        if ($invite->invitation_sent_at && $invite->invitation_sent_at->addDays(7)->isPast()) {
            abort(410, '此邀請連結已逾期。');
        }

        $company = $invite->company;

        $role = $invite->role;

        $invite->forceFill([
            'invitation_token' => null,
            'invitation_accepted_at' => now(),
        ])->save();

        $invite->delete();

        $this->acceptInvitation->execute(
            personalUser: $personalUser,
            company: $company,
            org: null,
            role: $role,
        );

        return redirect()
            ->route('tenant.weekly-reports', $company)
            ->with('success', '歡迎加入 '.$company->name.'！');
    }

    public function showOrgInvitation(Request $request, Company $company, string $token, string $type): Response
    {
        $organization = $this->resolveOrgByToken($company, $token, $type);

        if (! $organization) {
            abort(404, '邀請連結無效或已停用。');
        }

        $orgData = [
            'id' => $organization->id,
            'name' => $organization->name,
            'type' => $type,
        ];

        if ($type === 'department' && $organization instanceof Department) {
            $orgData['division_name'] = $organization->division?->name;
        } elseif ($type === 'team' && $organization instanceof Team) {
            $orgData['department_name'] = $organization->department?->name;
            $orgData['division_name'] = $organization->division?->name;
        }

        return Inertia::render('personal/invitations/accept-org', [
            'company' => [
                'name' => $company->name,
                'slug' => $company->slug,
            ],
            'organization' => $orgData,
            'token' => $token,
            'type' => $type,
        ]);
    }

    public function acceptOrgInvitation(Request $request, Company $company, string $token, string $type): RedirectResponse
    {
        $personalUser = $request->user();

        $organization = $this->resolveOrgByToken($company, $token, $type);

        if (! $organization) {
            abort(404, '邀請連結無效或已停用。');
        }

        $this->acceptInvitation->execute(
            personalUser: $personalUser,
            company: $company,
            org: $organization,
        );

        return redirect()
            ->route('tenant.weekly-reports', $company)
            ->with('success', '歡迎加入 '.$company->name.'！');
    }

    private function resolveOrgByToken(Company $company, string $token, string $type): Division|Department|Team|null
    {
        return match ($type) {
            'division' => Division::where('company_id', $company->id)
                ->where('invitation_token', $token)
                ->where('invitation_enabled', true)
                ->first(),
            'department' => Department::where('company_id', $company->id)
                ->where('invitation_token', $token)
                ->where('invitation_enabled', true)
                ->first(),
            'team' => Team::where('company_id', $company->id)
                ->where('invitation_token', $token)
                ->where('invitation_enabled', true)
                ->first(),
            default => null,
        };
    }
}
