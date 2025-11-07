<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin for Acme Corporation
        User::create([
            'name' => 'John Admin',
            'email' => 'admin@acme.com',
            'password' => Hash::make('password'),
            'company_id' => 1,
            'department_id' => 1,
            'role' => 'admin',
        ]);

        // Manager for Engineering dept
        User::create([
            'name' => 'Jane Manager',
            'email' => 'manager@acme.com',
            'password' => Hash::make('password'),
            'company_id' => 1,
            'department_id' => 1,
            'role' => 'manager',
        ]);

        // Members
        User::create([
            'name' => 'Bob Developer',
            'email' => 'bob@acme.com',
            'password' => Hash::make('password'),
            'company_id' => 1,
            'department_id' => 1,
            'role' => 'member',
        ]);

        User::create([
            'name' => 'Alice Engineer',
            'email' => 'alice@acme.com',
            'password' => Hash::make('password'),
            'company_id' => 1,
            'department_id' => 1,
            'role' => 'member',
        ]);
    }
}
