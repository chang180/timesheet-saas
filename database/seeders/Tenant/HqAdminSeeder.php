<?php

namespace Database\Seeders\Tenant;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class HqAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::query()->firstOrNew(['email' => 'hq.admin@example.com']);

        $user->forceFill([
            'name' => 'HQ Super Admin',
            'password' => Hash::make('Password!234'),
            'role' => 'hq_admin',
            'timezone' => 'Asia/Taipei',
            'locale' => 'zh_TW',
            'registered_via' => 'seed',
            'email_verified_at' => now(),
        ])->save();
    }
}
