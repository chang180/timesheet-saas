<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WeeklyReportPageController extends Controller
{
    public function __invoke(Request $request, Company $company): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->company_id !== $company->id) {
            abort(403, '無權存取此用戶。');
        }

        $isManager = in_array($user->role, ['owner', 'admin', 'company_admin'], true);

        if ($isManager && $company->onboarded_at === null) {
            return redirect()->route('tenant.settings', $company);
        }

        return Inertia::render('weekly/index', [
            'isManager' => $isManager,
            'tenant' => [
                'name' => $company->name,
                'slug' => $company->slug,
                'onboarded' => $company->onboarded_at !== null,
            ],
        ]);
    }
}
