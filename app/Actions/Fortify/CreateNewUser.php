<?php

namespace App\Actions\Fortify;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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
        Validator::make($input, [
            'company_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input): User {
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
