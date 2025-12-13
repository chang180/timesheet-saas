<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class TenantAuthController extends Controller
{
    /**
     * Show the tenant login/register page.
     */
    public function show(Request $request, Company $company): Response|RedirectResponse
    {
        // 如果用戶已經登入，重定向到適當的頁面
        if ($request->user()) {
            $user = $request->user();

            // 如果用戶已經屬於該公司，重定向到租戶首頁
            if ($user->company_id === $company->id) {
                return redirect()->route('tenant.home', $company);
            }

            // 如果用戶屬於其他公司，重定向到用戶自己的公司首頁
            if ($user->company) {
                return redirect()->route('tenant.home', $user->company);
            }

            // 如果用戶沒有公司，重定向到應用首頁
            return redirect()->route('app.home');
        }

        // 檢查公司是否允許註冊
        if ($company->status !== 'active') {
            abort(404, __('Company not found or inactive.'));
        }

        // 檢查是否已達人數上限
        $canRegister = $company->current_user_count < $company->user_limit;

        return Inertia::render('auth/tenant-auth', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'slug' => $company->slug,
            ],
            'canRegister' => $canRegister,
            'userLimit' => $company->user_limit,
            'currentUserCount' => $company->current_user_count,
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => $request->session()->get('status'),
        ]);
    }
}
