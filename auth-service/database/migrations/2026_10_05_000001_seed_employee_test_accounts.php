<?php

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Models\UserCredential;
use App\Models\UserProfile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Order-independent: the Role model carries the SoftDeletes global
        // scope, but the deleted_at column is added by a later migration
        // (10_08_000003). Bypass scopes so a fresh migrate works regardless
        // of migration order.
        $engineerRole = Role::withoutGlobalScopes()->firstOrCreate(
            ['name' => 'Engineer'],
            ['description' => 'Field and Service Engineer access']
        );

        $employees = [
            [
                'first_name' => 'Bernadette',
                'middle_name' => null,
                'last_name' => 'Tormo',
                'email' => 'employee1@gmail.com',
                'department' => 'Service',
                'role' => 'Engineer',
            ],
            [
                'first_name' => 'Bernadette',
                'middle_name' => null,
                'last_name' => 'Tormots',
                'email' => 'employee2@gmail.com',
                'department' => 'Service',
                'role' => 'Engineer',
            ],
            [
                'first_name' => 'Bernadette',
                'middle_name' => null,
                'last_name' => 'Badette',
                'email' => 'employee3@gmail.com',
                'department' => 'Service',
                'role' => 'Engineer',
            ],
        ];

        foreach ($employees as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
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
                    'password_hash' => Hash::make('password', ['rounds' => 12]),
                    'must_change_password' => false,
                    'password_changed_at' => now(),
                ]
            );

            $role = Role::withoutGlobalScopes()->where('name', $data['role'])->first() ?? $engineerRole;
            $department = Department::firstOrCreate(['name' => $data['department']]);

            UserProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'first_name' => $data['first_name'],
                    'middle_name' => $data['middle_name'],
                    'last_name' => $data['last_name'],
                    'role_id' => $role?->id,
                    'department_id' => $department?->id,
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $emails = [
            'employee1@gmail.com',
            'employee2@gmail.com',
            'employee3@gmail.com',
        ];

        $users = User::whereIn('email', $emails)->get();
        foreach ($users as $user) {
            $user->credentials()->delete();
            $user->profile()->delete();
            $user->delete();
        }
    }
};
