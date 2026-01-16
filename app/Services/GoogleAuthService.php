<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Department;
use App\Models\Division;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class GoogleAuthService
{
    /**
     * 處理 Google OAuth 回調，建立或更新用戶
     */
    public function handleCallback(SocialiteUser $googleUser): User
    {
        $googleId = $googleUser->getId();
        $email = $googleUser->getEmail();
        $name = $googleUser->getName();
        $avatar = $googleUser->getAvatar();

        // 檢查是否已有 Google ID 的用戶
        $user = User::where('google_id', $googleId)->first();

        if ($user) {
            // 更新現有用戶的資訊
            $user->update([
                'name' => $name,
                'avatar' => $avatar,
                'email_verified_at' => now(),
            ]);

            return $user;
        }

        // 檢查是否為邀請情境（需要在檢查 email 之前處理）
        $context = $this->resolveRegistrationContext();
        if ($context['intent'] === 'invitation' && isset($context['invitation_token']) && isset($context['company_slug'])) {
            return $this->acceptInvitation($googleUser, $context['company_slug'], $context['invitation_token']);
        }

        // 檢查是否有相同 email 的用戶（自動連結）
        $existingUser = User::where('email', $email)->first();

        if ($existingUser) {
            // 自動連結 Google 帳號到現有用戶
            $existingUser->update([
                'google_id' => $googleId,
                'avatar' => $avatar,
                'email_verified_at' => $existingUser->email_verified_at ?? now(),
            ]);

            Log::info('Google account linked to existing user', [
                'user_id' => $existingUser->id,
                'email' => $email,
            ]);

            return $existingUser;
        }

        // 檢查是否為登入意圖
        if ($context['intent'] === 'login') {
            // 登入時找不到用戶，拋出錯誤
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => [__('No account found with this email address. Please register first.')],
            ]);
        }

        // 新用戶註冊
        return $this->createNewUser($googleUser);
    }

    /**
     * 建立新用戶（根據註冊上下文）
     */
    protected function createNewUser(SocialiteUser $googleUser): User
    {
        $context = $this->resolveRegistrationContext();

        return DB::transaction(function () use ($googleUser, $context): User {
            $googleId = $googleUser->getId();
            $email = $googleUser->getEmail();
            $name = $googleUser->getName();
            $avatar = $googleUser->getAvatar();

            // 處理租戶註冊
            if ($context['intent'] === 'tenant_register' && isset($context['company_slug'])) {
                return $this->createTenantUser($googleUser, $context['company_slug']);
            }

            // 處理組織層級邀請
            if ($context['intent'] === 'organization_invitation' && isset($context['organization_invitation'])) {
                return $this->registerByOrganizationInvitation($googleUser, $context['organization_invitation']);
            }

            // 一般註冊（建立新公司）
            return $this->createRegularUser($googleUser);
        });
    }

    /**
     * 建立租戶用戶（加入現有公司）
     */
    protected function createTenantUser(SocialiteUser $googleUser, string $companySlug): User
    {
        $company = Company::where('slug', $companySlug)->firstOrFail();

        // 檢查公司狀態
        if ($company->status !== 'active') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'company_slug' => [__('Company is not active.')],
            ]);
        }

        // 檢查人數上限
        if ($company->current_user_count >= $company->user_limit) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'company_slug' => [__('Company has reached the user limit.')],
            ]);
        }

        // 檢查 email 是否已存在於該公司
        if (User::where('company_id', $company->id)
            ->where('email', $googleUser->getEmail())
            ->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => [__('This email is already registered in this company.')],
            ]);
        }

        $company->increment('current_user_count');

        $user = User::create([
            'company_id' => $company->id,
            'name' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar(),
            'password' => Hash::make(\Illuminate\Support\Str::random(32)), // 隨機密碼，不會使用
            'role' => 'member',
            'registered_via' => 'google-tenant-register',
            'email_verified_at' => now(),
        ]);

        Session::put('tenant_registration_company_slug', $company->slug);

        return $user;
    }

    /**
     * 接受成員邀請
     */
    protected function acceptInvitation(SocialiteUser $googleUser, string $companySlug, string $token): User
    {
        $company = Company::where('slug', $companySlug)->firstOrFail();

        $user = User::query()
            ->where('company_id', $company->getKey())
            ->where('invitation_token', $token)
            ->whereNull('invitation_accepted_at')
            ->firstOrFail();

        // 驗證 email 是否匹配
        if ($user->email !== $googleUser->getEmail()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => [__('The email does not match the invitation.')],
            ]);
        }

        // 檢查邀請是否過期
        if ($user->invitation_sent_at && $user->invitation_sent_at->addDays(7)->isPast()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'token' => [__('This invitation has expired.')],
            ]);
        }

        $user->forceFill([
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar(),
            'invitation_token' => null,
            'invitation_accepted_at' => now(),
            'email_verified_at' => now(),
        ])->save();

        // 重新載入以確保取得最新資料
        return $user->fresh();
    }

    /**
     * 透過組織層級邀請註冊
     */
    protected function registerByOrganizationInvitation(SocialiteUser $googleUser, array $orgInvitation): User
    {
        $company = Company::where('slug', $orgInvitation['company_slug'])->firstOrFail();
        $token = $orgInvitation['token'];
        $type = $orgInvitation['type'];

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
            throw \Illuminate\Validation\ValidationException::withMessages([
                'token' => [__('Invalid or disabled invitation link.')],
            ]);
        }

        // 檢查 email 是否已存在於該公司
        if (User::where('company_id', $company->id)
            ->where('email', $googleUser->getEmail())
            ->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => [__('This email is already registered in this company.')],
            ]);
        }

        // 檢查人數上限
        if ($company->current_user_count >= $company->user_limit) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => [__('Company has reached the user limit.')],
            ]);
        }

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

        $company->increment('current_user_count');

        $user = User::create([
            'company_id' => $company->getKey(),
            'division_id' => $divisionId,
            'department_id' => $departmentId,
            'team_id' => $teamId,
            'name' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar(),
            'password' => Hash::make(\Illuminate\Support\Str::random(32)),
            'role' => 'member',
            'registered_via' => 'google-invitation-link',
            'email_verified_at' => now(),
        ]);

        return $user;
    }

    /**
     * 建立一般用戶（建立新公司）
     */
    protected function createRegularUser(SocialiteUser $googleUser): User
    {
        $company = Company::create([
            'name' => $googleUser->getName()."'s Company", // 預設公司名稱，用戶之後可以修改
            'slug' => $this->generateUniqueSlug(),
            'status' => 'active',
            'user_limit' => 50,
            'current_user_count' => 1,
            'timezone' => config('app.timezone', 'Asia/Taipei'),
        ]);

        $company->settings()->create();

        $user = User::create([
            'company_id' => $company->id,
            'name' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar(),
            'password' => Hash::make(\Illuminate\Support\Str::random(32)),
            'role' => 'company_admin',
            'registered_via' => 'google-self-register',
            'email_verified_at' => now(),
        ]);

        return $user;
    }

    /**
     * 解析註冊上下文（從 session）
     */
    public function resolveRegistrationContext(): array
    {
        return [
            'intent' => Session::get('google_auth_intent', 'register'),
            'company_slug' => Session::get('google_auth_company_slug'),
            'invitation_token' => Session::get('google_auth_invitation_token'),
            'organization_invitation' => Session::get('google_auth_organization_invitation'),
        ];
    }

    /**
     * 清除註冊上下文
     */
    public function clearRegistrationContext(): void
    {
        Session::forget([
            'google_auth_intent',
            'google_auth_company_slug',
            'google_auth_invitation_token',
            'google_auth_organization_invitation',
        ]);
    }

    /**
     * 產生唯一的 slug
     */
    protected function generateUniqueSlug(): string
    {
        do {
            $slug = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(10));
        } while (Company::where('slug', $slug)->exists());

        return $slug;
    }
}
