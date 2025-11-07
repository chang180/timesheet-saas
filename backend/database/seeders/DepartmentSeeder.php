<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Departments for Acme Corporation (company_id: 1)
        Department::create([
            'company_id' => 1,
            'name' => 'Engineering',
            'description' => 'Software development and technical operations',
            'is_active' => true,
        ]);

        Department::create([
            'company_id' => 1,
            'name' => 'Product Management',
            'description' => 'Product strategy and roadmap',
            'is_active' => true,
        ]);

        Department::create([
            'company_id' => 1,
            'name' => 'Marketing',
            'description' => 'Marketing and communications',
            'is_active' => true,
        ]);

        // Departments for Tech Solutions Inc (company_id: 2)
        Department::create([
            'company_id' => 2,
            'name' => 'Development',
            'description' => 'Software development',
            'is_active' => true,
        ]);

        Department::create([
            'company_id' => 2,
            'name' => 'QA',
            'description' => 'Quality assurance and testing',
            'is_active' => true,
        ]);
    }
}
