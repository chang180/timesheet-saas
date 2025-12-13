<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\AcceptInvitationRequest;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
}
