<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * NOTE: uses DB::table() instead of the Role/Permission Eloquent models.
     * Migrations run in historical order against whatever columns existed
     * at the time they were written — if they go through Eloquent models,
     * a later model change (e.g. adding a SoftDeletes global scope that
     * filters on `deleted_at`) breaks this migration on a fresh `migrate`
     * run, because `deleted_at` didn't exist yet at this point in history.
     * Querying the table directly avoids that coupling.
     */
    public function up(): void
    {
        $now = now();

        // 1. Create Finance Manager and Finance Employee roles
        $financeManagerId = DB::table('roles')->where('name', 'Finance Manager')->value('id');
        if (!$financeManagerId) {
            $financeManagerId = DB::table('roles')->insertGetId([
                'name' => 'Finance Manager',
                'description' => 'Finance department manager',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $financeEmployeeId = DB::table('roles')->where('name', 'Finance Employee')->value('id');
        if (!$financeEmployeeId) {
            $financeEmployeeId = DB::table('roles')->insertGetId([
                'name' => 'Finance Employee',
                'description' => 'Finance department employee',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 2. Sync default permissions to Finance Manager (matching standard Manager)
        $managerPermIds = DB::table('permissions')->whereIn('slug', [
            'cms.templates.use',
            'cms.ocr.upload',
            'cms.ocr.process',
            'cms.ocr.review',
            'cms.contracts.generate',
            'cms.risk.assess',
            'cms.risk.view',
            'cms.risk.approve',
            'cms.contracts.view',
            'cms.contracts.create',
            'cms.contracts.edit',
            'cms.users.view',
            'cms.partners.view',
            'cms.partners.create',
            'cms.partners.edit',
        ])->pluck('id');

        foreach ($managerPermIds as $permId) {
            $exists = DB::table('role_permission')
                ->where('role_id', $financeManagerId)
                ->where('permission_id', $permId)
                ->exists();
            if (!$exists) {
                DB::table('role_permission')->insert([
                    'role_id' => $financeManagerId,
                    'permission_id' => $permId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // 3. Migrate existing Finance users who have Manager/Employee roles to the new department-specific roles
        $financeDept = DB::table('departments')->where('name', 'Sales & Marketing')->first();
        if ($financeDept) {
            $managerRoleId = DB::table('roles')->where('name', 'Manager')->value('id');
            $employeeRoleId = DB::table('roles')->where('name', 'Employee')->value('id');

            if ($managerRoleId) {
                DB::table('user_profiles')
                    ->where('department_id', $financeDept->id)
                    ->where('role_id', $managerRoleId)
                    ->update(['role_id' => $financeManagerId]);
            }

            if ($employeeRoleId) {
                DB::table('user_profiles')
                    ->where('department_id', $financeDept->id)
                    ->where('role_id', $employeeRoleId)
                    ->update(['role_id' => $financeEmployeeId]);
            }
        }
    }

    public function down(): void
    {
        // No down migration is necessary for this custom logic in this context
    }
};
