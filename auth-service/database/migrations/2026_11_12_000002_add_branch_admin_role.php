<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the "Branch Admin" role and turns the seeded superadmin@example.com account into
 * branchadmin@example.com (Branch Admin, SBSI Makati Branch).
 *
 * Backend only: the account email lives in this migration and UserSeeder, never in a frontend.
 * The Super Admin role is kept (superadmin@sbsi.com and the Auth Module policies use it).
 * The account's sessions are revoked and the change is written to audit_logs.
 */
return new class extends Migration
{
    private const ROLE_NAME = 'Branch Admin';

    private const PREVIOUS_ROLE_NAME = 'Super Admin';

    private const OLD_EMAIL = 'superadmin@example.com';

    private const NEW_EMAIL = 'branchadmin@example.com';

    private const OLD_NAME = ['Super', 'Admin'];

    private const NEW_NAME = ['Branch', 'Admin'];

    private const BRANCH_NAMES = ['SBSI Makati Branch'];

    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('user_profiles')) {
            return;
        }

        $now = now();

        // 1. Create (or restore) the role. withTrashed semantics
        $role = DB::table('roles')->where('name', self::ROLE_NAME)->first();
        if ($role) {
            if (Schema::hasColumn('roles', 'deleted_at') && $role->deleted_at !== null) {
                DB::table('roles')->where('id', $role->id)->update(['deleted_at' => null, 'updated_at' => $now]);
            }
            $roleId = $role->id;
        } else {
            $roleId = DB::table('roles')->insertGetId([
                'name' => self::ROLE_NAME,
                'description' => 'Branch-scoped administrator for the Ticketing System',
                'nav_group' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 2. Move the seeded account, only while it is still the untouched Super Admin seed
        //    and the new email is free (never overwrite an account someone created by hand).
        $user = DB::table('users')->where('email', self::OLD_EMAIL)->first();
        if (! $user || DB::table('users')->where('email', self::NEW_EMAIL)->exists()) {
            return;
        }

        $superAdminRoleId = DB::table('roles')->where('name', self::PREVIOUS_ROLE_NAME)->value('id');
        $profile = DB::table('user_profiles')->where('user_id', $user->id)->first();
        if (! $profile || ! $superAdminRoleId || (int) $profile->role_id !== (int) $superAdminRoleId) {
            return;
        }

        $branchId = null;
        if (Schema::hasTable('branches')) {
            foreach (self::BRANCH_NAMES as $branchName) {
                $branchId = DB::table('branches')->where('name', $branchName)->value('id');
                if ($branchId) {
                    break;
                }
            }
        }

        DB::table('users')->where('id', $user->id)->update(['email' => self::NEW_EMAIL, 'updated_at' => $now]);

        $profileUpdate = ['role_id' => $roleId, 'updated_at' => $now];
        if ([$profile->first_name, $profile->last_name] === self::OLD_NAME) {
            [$profileUpdate['first_name'], $profileUpdate['last_name']] = self::NEW_NAME;
        }
        if ($branchId && Schema::hasColumn('user_profiles', 'branch_id')) {
            $profileUpdate['branch_id'] = $branchId;
        }
        DB::table('user_profiles')->where('id', $profile->id)->update($profileUpdate);

        // 3. Privilege dropped and login changed: end every live session
        $this->revokeSessions((int) $user->id);

        $this->audit((int) $user->id, 'ROLE_CHANGED', sprintf(
            'Migration: %s renamed to %s and moved from %s to %s (branch_id %s).',
            self::OLD_EMAIL,
            self::NEW_EMAIL,
            self::PREVIOUS_ROLE_NAME,
            self::ROLE_NAME,
            $branchId ?? 'unchanged'
        ));
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('user_profiles')) {
            return;
        }

        $now = now();
        $roleId = DB::table('roles')->where('name', self::ROLE_NAME)->value('id');
        $superAdminRoleId = DB::table('roles')->where('name', self::PREVIOUS_ROLE_NAME)->value('id');

        $user = DB::table('users')->where('email', self::NEW_EMAIL)->first();
        $oldEmailFree = ! DB::table('users')->where('email', self::OLD_EMAIL)->exists();
        if ($user && $oldEmailFree && $roleId && $superAdminRoleId) {
            $profile = DB::table('user_profiles')->where('user_id', $user->id)->where('role_id', $roleId)->first();
            if ($profile) {
                DB::table('users')->where('id', $user->id)->update(['email' => self::OLD_EMAIL, 'updated_at' => $now]);

                $profileUpdate = ['role_id' => $superAdminRoleId, 'updated_at' => $now];
                if ([$profile->first_name, $profile->last_name] === self::NEW_NAME) {
                    [$profileUpdate['first_name'], $profileUpdate['last_name']] = self::OLD_NAME;
                }
                DB::table('user_profiles')->where('id', $profile->id)->update($profileUpdate);

                $this->revokeSessions((int) $user->id);
                $this->audit((int) $user->id, 'ROLE_CHANGED', sprintf(
                    'Migration rollback: %s restored to %s with role %s.',
                    self::NEW_EMAIL,
                    self::OLD_EMAIL,
                    self::PREVIOUS_ROLE_NAME
                ));
            }
        }

        // Drop the role only if nobody else was given it in the meantime.
        if ($roleId && ! DB::table('user_profiles')->where('role_id', $roleId)->exists()) {
            if (Schema::hasTable('role_permission')) {
                DB::table('role_permission')->where('role_id', $roleId)->delete();
            }
            DB::table('roles')->where('id', $roleId)->delete();
        }
    }

    private function revokeSessions(int $userId): void
    {
        if (Schema::hasTable('oauth_access_tokens')) {
            DB::table('oauth_access_tokens')->where('user_id', $userId)->update(['revoked' => true]);
        }
        if (Schema::hasTable('refresh_tokens')) {
            DB::table('refresh_tokens')->where('user_id', $userId)->update(['is_revoked' => true]);
        }
        if (Schema::hasTable('user_sessions')) {
            DB::table('user_sessions')->where('user_id', $userId)->update(['is_active' => false]);
        }
    }

    private function audit(int $userId, string $action, string $description): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        $now = now();
        DB::table('audit_logs')->insert([
            'actor_id' => $userId,
            'action' => $action,
            'description' => $description,
            'ip_address' => null,
            'user_agent' => 'migration',
            'action_date' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
};
