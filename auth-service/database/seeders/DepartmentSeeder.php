<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Accounting'],
            ['name' => 'Admin'],
            ['name' => 'Executive'],
            ['name' => 'Finance'],
            ['name' => 'HR'],
            ['name' => 'Human Resources'],
            ['name' => 'IT'],
            ['name' => 'Operations'],
            ['name' => 'Customer Service'],
            ['name' => 'RA/QA'],
            ['name' => 'Sales & Marketing'],
            ['name' => 'Service'],
        ];

        foreach ($departments as $department) {
            Department::firstOrCreate(['name' => $department['name']], $department);
        }
    }
}