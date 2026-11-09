<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Models\UserCredential;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $usersToSeed = [
            ['Super', null, 'Admin', 'superadmin@example.com', 'IT', 'Super Admin'],
            ['System', null, 'Administrator', 'admin@example.com', 'IT', 'IT Admin'],
            ['Department', null, 'Manager', 'manager@example.com', 'Operations', 'Manager'],
            ['Sales', null, 'Representative', 'sales@example.com', 'Operations', 'Sales'],
            ['Finance', null, 'Officer', 'finance@example.com', 'Finance', 'Employee'],
            ['Finance', null, 'Administrator', 'finance-admin@example.com', 'Finance', 'Admin'],
            ['Finance', null, 'Manager', 'finance-manager@example.com', 'Finance', 'Manager'],
            ['Sales Marketing', null, 'Administrator', 'sales-marketing-admin@example.com', 'Sales & Marketing', 'Admin'],
            ['Sales Marketing', null, 'Manager', 'sales-marketing-manager@example.com', 'Sales & Marketing', 'Manager'],
            ['Sales Marketing', null, 'Officer', 'sales-marketing-officer@example.com', 'Sales & Marketing', 'Employee'],
            ['General', null, 'Employee', 'employee@example.com', 'Operations', 'Employee'],
            ['Chief', null, 'Executive', 'ceo@example.com', 'Executive', 'CEO'],
            ['Support', null, 'Engineer', 'engineer@example.com', 'Service', 'Employee'],
            ['Customer', null, 'Support', 'cs@example.com', 'Customer Service', 'Employee'],
            ['Bernadette', null, 'Tormo', 'employee1@gmail.com', 'Service', 'Engineer'],
            ['Bernadette', null, 'Tormots', 'employee2@gmail.com', 'Service', 'Engineer'],
            ['Bernadette', null, 'Badette', 'employee3@gmail.com', 'Service', 'Engineer'],

            // PRS seed accounts. Auth Module is the login source of truth; PRS projects these locally after login.
            ['Sofia', null, 'Montenegro', 'superadmin@sbsi.com', 'Executive', 'Super Admin'],
            ['System', null, 'Administrator', 'admin1@sbsi.com', 'IT', 'Admin'],
            ['Alicia', null, 'Reyes', 'admin2@sbsi.com', 'Admin', 'Admin'],
            ['Marco', null, 'Santos', 'admin3@sbsi.com', 'Executive', 'Admin'],
            ['Patricia', null, 'Lim', 'admin4@sbsi.com', 'Finance', 'Admin'],
            ['Daniel', null, 'Cruz', 'admin5@sbsi.com', 'IT', 'Admin'],
            ['Maya', null, 'Dela Cruz', 'manager.sales@sbsi.com', 'Sales & Marketing', 'Manager'],
            ['Rafael', null, 'Garcia', 'manager.operations@sbsi.com', 'Operations', 'Manager'],
            ['Bianca', null, 'Navarro', 'manager.hr@sbsi.com', 'HR', 'Manager'],
            ['Enrico', null, 'Villanueva', 'manager.finance@sbsi.com', 'Finance', 'Manager'],
            ['Camille', null, 'Torres', 'manager.service@sbsi.com', 'Service', 'Manager'],
            ['Rina', null, 'Velasco', 'supervisor.sales@sbsi.com', 'Sales & Marketing', 'Supervisor'],
            ['Oliver', null, 'Reyes', 'supervisor.operations@sbsi.com', 'Operations', 'Supervisor'],
            ['Grace', null, 'Uy', 'supervisor.service@sbsi.com', 'Service', 'Supervisor'],
            ['John', null, 'Smith', 'sales.employee1@sbsi.com', 'Sales & Marketing', 'Employee'],
            ['Jane', null, 'Doe', 'sales.employee2@sbsi.com', 'Sales & Marketing', 'Employee'],
            ['Carlo', null, 'Mendoza', 'sales.employee3@sbsi.com', 'Sales & Marketing', 'Employee'],
            ['Lara', null, 'Aquino', 'sales.employee4@sbsi.com', 'Sales & Marketing', 'Employee'],
            ['Nico', null, 'Ramos', 'sales.employee5@sbsi.com', 'Sales & Marketing', 'Employee'],
            ['Hannah', null, 'Flores', 'employee.accounting@sbsi.com', 'Accounting', 'Accountant'],
            ['Victor', null, 'Ocampo', 'employee.admin@sbsi.com', 'Admin', 'Employee'],
            ['Sofia', null, 'Bautista', 'employee.executive@sbsi.com', 'Executive', 'Employee'],
            ['Gabriel', null, 'Pascual', 'employee.finance@sbsi.com', 'Finance', 'Employee'],
            ['Isabel', null, 'Mercado', 'employee.hr@sbsi.com', 'HR', 'Employee'],
            ['Leo', null, 'Castillo', 'employee.it@sbsi.com', 'IT', 'Employee'],
            ['Andrea', null, 'Morales', 'employee.operations@sbsi.com', 'Operations', 'Employee'],
            ['Paolo', null, 'Rivera', 'employee.raqa@sbsi.com', 'RA/QA', 'Employee'],
            ['Elaine', null, 'Tan', 'employee.service@sbsi.com', 'Service', 'Employee'],
            ['Miguel', null, 'Chua', 'employee.support@sbsi.com', 'Operations', 'Employee'],
        ];

        foreach ($usersToSeed as [$firstName, $middleName, $lastName, $email, $departmentName, $roleName]) {
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'is_active' => true,
                    'is_password_changed' => true,
                    'email_verified' => true,
                    'email_verified_at' => now(),
                ]
            );

            UserCredential::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'password_hash' => Hash::make('password', ['rounds' => config('hashing.bcrypt.rounds', 12)]),
                    'must_change_password' => false,
                    'password_changed_at' => now(),
                ]
            );

            $role = Role::where('name', $roleName)->first();
            $department = Department::where('name', $departmentName)->first();

            UserProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'first_name' => $firstName,
                    'middle_name' => $middleName,
                    'last_name' => $lastName,
                    'role_id' => $role?->id,
                    'department_id' => $department?->id,
                ]
            );
        }
    }
}
