<?php

namespace App\Actions\Fortify;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        // 檢查是否為租戶註冊（有 company_slug）
        $isTenantRegistration = isset($input['company_slug']) && $input['company_slug'] !== '';

        Log::info('CreateNewUser::create called', [
            'is_tenant_registration' => $isTenantRegistration,
            'has_company_slug' => isset($input['company_slug']),
            'company_slug' => $input['company_slug'] ?? null,
            'email' => $input['email'] ?? null,
        ]);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ];

        if ($isTenantRegistration) {
            // 租戶註冊：驗證 company_slug 是否存在且允許註冊
            $rules['company_slug'] = ['required', 'string', 'exists:companies,slug'];
        } else {
            // 一般註冊：需要公司名稱
            $rules['company_name'] = ['required', 'string', 'max:255'];
        }

        try {
            Validator::make($input, $rules)->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('CreateNewUser validation failed', [
                'errors' => $e->errors(),
                'input' => array_merge($input, ['password' => '***']),
            ]);
            throw $e;
        }

        return DB::transaction(function () use ($input, $isTenantRegistration): User {
            if ($isTenantRegistration) {
                // 加入現有公司
                $company = Company::where('slug', $input['company_slug'])->firstOrFail();

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

                // 更新公司成員數
                $company->increment('current_user_count');

                $user = User::create([
                    'company_id' => $company->id,
                    'name' => $input['name'],
                    'email' => $input['email'],
                    'password' => $input['password'],
                    'role' => 'member',
                    'registered_via' => 'tenant-register',
                ]);

                // 在 session 中存儲 company_slug，用於註冊後重定向
                Session::put('tenant_registration_company_slug', $company->slug);

                Log::info('Tenant registration successful', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'company_id' => $company->id,
                    'company_slug' => $company->slug,
                ]);
            } else {
                // 創建新公司
                $company = Company::create([
                    'name' => $input['company_name'],
                    'slug' => $this->generateUniqueSlug(),
                    'status' => 'active',
                    'user_limit' => 50,
                    'current_user_count' => 1,
                    'timezone' => config('app.timezone', 'Asia/Taipei'),
                ]);

                $company->settings()->create();

                $user = User::create([
                    'company_id' => $company->id,
                    'name' => $input['name'],
                    'email' => $input['email'],
                    'password' => $input['password'],
                    'role' => 'company_admin',
                    'registered_via' => 'self-register',
                ]);
            }

            return $user;
        });
    }

    protected function generateUniqueSlug(): string
    {
        do {
            $slug = Str::lower(Str::random(10));
        } while (Company::where('slug', $slug)->exists());

        return $slug;
    }
}
