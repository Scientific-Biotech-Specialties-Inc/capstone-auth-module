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
     * NOTE: uses DB::table() instead of the Role/UserProfile Eloquent
     * models — see the comment in 2026_05_22_030000_create_finance_
     * department_roles.php for why migrations should not depend on
     * Eloquent model state (e.g. a later SoftDeletes global scope) that
     * didn't exist at the point in history this migration represents.
     */
    public function up(): void
    {
        $managerRoleId = DB::table('roles')->where('name', 'Manager')->value('id');
        $employeeRoleId = DB::table('roles')->where('name', 'Employee')->value('id');

        $financeManagerId = DB::table('roles')->where('name', 'Finance Manager')->value('id');
        $financeEmployeeId = DB::table('roles')->where('name', 'Finance Employee')->value('id');

        if ($financeManagerId && $managerRoleId) {
            DB::table('user_profiles')
                ->where('role_id', $financeManagerId)
                ->update(['role_id' => $managerRoleId]);
        }

        if ($financeEmployeeId && $employeeRoleId) {
            DB::table('user_profiles')
                ->where('role_id', $financeEmployeeId)
                ->update(['role_id' => $employeeRoleId]);
        }

        // Delete the roles (cascades automatically to role_permission pivot table)
        DB::table('roles')->whereIn('name', ['Finance Manager', 'Finance Employee'])->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $now = now();

        // Re-create the roles
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
            DB::table('roles')->insertGetId([
                'name' => 'Finance Employee',
                'description' => 'Finance department employee',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Sync manager permissions back to Finance Manager
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
    }
};
