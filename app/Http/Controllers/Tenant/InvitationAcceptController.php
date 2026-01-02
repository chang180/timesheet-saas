<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\AcceptInvitationRequest;
use App\Models\Company;
use App\Models\Department;
use App\Models\Division;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class InvitationAcceptController extends Controller
{
    /**
     * Show the invitation accept page.
     */
    public function show(Request $request, Company $company, string $token): Response
    {
        $user = User::query()
            ->where('company_id', $company->getKey())
            ->where('invitation_token', $token)
            ->whereNull('invitation_accepted_at')
            ->first();

        if (! $user) {
            abort(404, __('Invalid or expired invitation token.'));
        }

        if ($user->invitation_sent_at && $user->invitation_sent_at->addDays(7)->isPast()) {
            abort(410, __('This invitation has expired.'));
        }

        return Inertia::render('tenant/invitations/accept', [
            'company' => [
                'name' => $company->name,
                'slug' => $company->slug,
            ],
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'token' => $token,
        ]);
    }

    /**
     * Accept the invitation and set password.
     */
    public function store(AcceptInvitationRequest $request, Company $company): RedirectResponse
    {
        $token = $request->validated('token');

        $user = User::query()
            ->where('company_id', $company->getKey())
            ->where('invitation_token', $token)
            ->whereNull('invitation_accepted_at')
            ->first();

        if (! $user) {
            abort(404, __('Invalid or expired invitation token.'));
        }

        if ($user->invitation_sent_at && $user->invitation_sent_at->addDays(7)->isPast()) {
            abort(410, __('This invitation has expired.'));
        }

        $user->forceFill([
            'password' => Hash::make($request->validated('password')),
            'invitation_token' => null,
            'invitation_accepted_at' => now(),
            'email_verified_at' => now(),
        ])->save();

        auth()->login($user);

        return redirect()->route('tenant.weekly-reports', $company)->with('success', __('Welcome! Your account has been activated.'));
    }

    /**
     * Show the register by invitation page (for organization-level invitations).
     */
    public function registerByInvitation(Request $request, Company $company, string $token, string $type): Response
    {
        $organization = match ($type) {
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

        if (! $organization) {
            abort(404, __('Invalid or disabled invitation link.'));
        }

        $organizationData = [
            'id' => $organization->id,
            'name' => $organization->name,
            'type' => $type,
        ];

        if ($type === 'department' && $organization instanceof Department) {
            $organizationData['division_id'] = $organization->division_id;
            $organizationData['division_name'] = $organization->division?->name;
        } elseif ($type === 'team' && $organization instanceof Team) {
            $organizationData['department_id'] = $organization->department_id;
            $organizationData['department_name'] = $organization->department?->name;
            $organizationData['division_id'] = $organization->division_id;
            $organizationData['division_name'] = $organization->division?->name;
        }

        return Inertia::render('tenant/register-by-invitation', [
            'company' => [
                'name' => $company->name,
                'slug' => $company->slug,
            ],
            'organization' => $organizationData,
            'token' => $token,
            'type' => $type,
        ]);
    }

    /**
     * Register a new user via organization-level invitation link.
     */
    public function storeRegisterByInvitation(Request $request, Company $company): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'type' => ['required', 'string', 'in:division,department,team'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:strict,rfc', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $token = $request->input('token');
        $type = $request->input('type');

        $organization = match ($type) {
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

        if (! $organization) {
            abort(404, __('Invalid or disabled invitation link.'));
        }

        // Check if email already exists in this company
        if (User::where('company_id', $company->id)
            ->where('email', $request->input('email'))
            ->exists()) {
            return back()->withErrors(['email' => '此電子郵件地址已經被使用。']);
        }

        // Check user limit
        if ($company->current_user_count >= $company->user_limit) {
            return back()->withErrors(['email' => '此公司的成員數已達上限。']);
        }

        $user = DB::transaction(function () use ($request, $company, $organization, $type): User {
            $tenant = Company::query()
                ->whereKey($company->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $divisionId = null;
            $departmentId = null;
            $teamId = null;

            if ($type === 'division' && $organization instanceof Division) {
                $divisionId = $organization->id;
            } elseif ($type === 'department' && $organization instanceof Department) {
                $departmentId = $organization->id;
                $divisionId = $organization->division_id;
            } elseif ($type === 'team' && $organization instanceof Team) {
                $teamId = $organization->id;
                $departmentId = $organization->department_id;
                $divisionId = $organization->division_id;
            }

            $user = User::create([
                'company_id' => $tenant->getKey(),
                'division_id' => $divisionId,
                'department_id' => $departmentId,
                'team_id' => $teamId,
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => Hash::make($request->input('password')),
                'role' => 'member',
                'registered_via' => 'invitation_link',
                'email_verified_at' => now(),
            ]);

            $tenant->increment('current_user_count');

            return $user;
        });

        auth()->login($user);

        return redirect()->route('tenant.weekly-reports', $company)->with('success', __('Welcome! Your account has been created.'));
    }
}
