<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\GoogleAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function __construct(
        protected GoogleAuthService $googleAuthService
    ) {}

    /**
     * 重定向到 Google OAuth
     */
    public function redirect(Request $request): RedirectResponse
    {
        try {
            // 從請求參數或 session 中取得註冊上下文
            $intent = $request->input('intent', Session::get('google_auth_intent', 'register'));
            $companySlug = $request->input('company_slug') ?? Session::get('google_auth_company_slug');
            $invitationToken = $request->input('invitation_token') ?? Session::get('google_auth_invitation_token');
            $organizationInvitation = $request->input('organization_invitation') ?? Session::get('google_auth_organization_invitation');

            // 儲存註冊上下文到 session
            if ($intent) {
                Session::put('google_auth_intent', $intent);
            }
            if ($companySlug) {
                Session::put('google_auth_company_slug', $companySlug);
            }
            if ($invitationToken) {
                Session::put('google_auth_invitation_token', $invitationToken);
            }
            if ($organizationInvitation) {
                Session::put('google_auth_organization_invitation', $organizationInvitation);
            }

            Log::info('Redirecting to Google OAuth', [
                'intent' => $intent,
                'company_slug' => $companySlug,
                'has_invitation_token' => ! empty($invitationToken),
                'has_organization_invitation' => ! empty($organizationInvitation),
            ]);

            return Socialite::driver('google')
                ->scopes(['openid', 'profile', 'email'])
                ->redirect();
        } catch (\Exception $e) {
            Log::error('Google OAuth redirect failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('home')
                ->with('error', __('Failed to initiate Google authentication. Please try again.'));
        }
    }

    /**
     * 處理 Google OAuth 回調
     */
    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            Log::info('Google OAuth callback received', [
                'google_id' => $googleUser->getId(),
                'email' => $googleUser->getEmail(),
            ]);

            $user = $this->googleAuthService->handleCallback($googleUser);

            // 清除註冊上下文
            $this->googleAuthService->clearRegistrationContext();

            // 登入用戶
            Auth::login($user, true);

            // 根據註冊類型重定向
            return $this->redirectAfterAuth($user, $request);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Google OAuth validation failed', [
                'errors' => $e->errors(),
            ]);

            return redirect()->route('home')
                ->withErrors($e->errors());
        } catch (\Exception $e) {
            Log::error('Google OAuth callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('home')
                ->with('error', __('Google authentication failed. Please try again.'));
        }
    }

    /**
     * 根據註冊類型決定重定向位置
     */
    protected function redirectAfterAuth(User $user, Request $request): RedirectResponse
    {
        // 檢查是否為租戶註冊後的重定向
        $tenantSlug = Session::pull('tenant_registration_company_slug');
        if ($tenantSlug && $user->company && $user->company->slug === $tenantSlug) {
            $isManager = in_array($user->role, ['owner', 'admin', 'company_admin'], true);
            if ($isManager && $user->company->onboarded_at === null) {
                return redirect()->route('tenant.settings', $user->company)
                    ->with('success', __('Welcome! Your account has been created.'));
            }

            return redirect()->route('tenant.home', $user->company)
                ->with('success', __('Welcome! Your account has been created.'));
        }

        // 如果用戶有公司，重定向到公司首頁
        if ($user->company) {
            $isManager = in_array($user->role, ['owner', 'admin', 'company_admin'], true);
            if ($isManager && $user->company->onboarded_at === null) {
                return redirect()->route('tenant.settings', $user->company)
                    ->with('success', __('Welcome! Your account has been created.'));
            }

            return redirect()->route('tenant.weekly-reports', $user->company)
                ->with('success', __('Welcome! Your account has been created.'));
        }

        // 預設重定向
        return redirect()->route('app.home')
            ->with('success', __('Welcome! Your account has been created.'));
    }
}
