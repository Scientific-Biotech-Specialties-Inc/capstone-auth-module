<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Department;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class SalesMarketingAdminRolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\DepartmentSeeder::class);
        $this->seed(\Database\Seeders\UserSeeder::class);
        \Illuminate\Support\Facades\Mail::fake();
    }

    private function getSalesMarketingAdminUserWithSession()
    {
        $user = User::where('email', 'sales-marketing-admin@example.com')->first();
        $user->is_password_changed = true;
        $user->save();
        
        $sessionId = (string) Str::uuid();
        DB::table('user_sessions')->insert([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Testing',
            'last_active_at' => now(),
            'is_active' => true,
            'created_at' => now(),
        ]);

        return [$user, $sessionId];
    }

    private function getITAdminUserWithSession()
    {
        $user = User::where('email', 'admin@example.com')->first();
        $user->is_password_changed = true;
        $user->save();
        
        $sessionId = (string) Str::uuid();
        DB::table('user_sessions')->insert([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Testing',
            'last_active_at' => now(),
            'is_active' => true,
            'created_at' => now(),
        ]);

        return [$user, $sessionId];
    }

    public function test_sales_marketing_admin_can_only_list_sales_marketing_roles()
    {
        [$user, $sessionId] = $this->getSalesMarketingAdminUserWithSession();

        $response = $this->actingAs($user, 'api')
                         ->withHeader('X-Session-ID', $sessionId)
                         ->getJson('/api/admin/roles');

        $response->assertStatus(200);
        
        $roleNames = collect($response->json()['data'])->pluck('name')->toArray();

        // Base scoped roles plus any role granted CMS access (dynamic:
        // Sales and CEO hold cms permissions in seeds, so they surface).
        $this->assertContains('Manager', $roleNames);
        $this->assertContains('Employee', $roleNames);
        $this->assertContains('Sales', $roleNames);
        $this->assertContains('CEO', $roleNames);

        // Platform admin roles stay IT-governed; other departments excluded.
        $this->assertNotContains('IT Admin', $roleNames);
        $this->assertNotContains('Super Admin', $roleNames);
        $this->assertNotContains('Admin', $roleNames);
        $this->assertNotContains('Finance', $roleNames);
    }

    public function test_sales_marketing_admin_cannot_create_update_delete_roles()
    {
        [$user, $sessionId] = $this->getSalesMarketingAdminUserWithSession();
        $managerRole = Role::where('name', 'Manager')->first();

        // Create -> forbidden
        $responseCreate = $this->actingAs($user, 'api')
                               ->withHeader('X-Session-ID', $sessionId)
                               ->postJson('/api/admin/roles', [
                                   'name' => 'Should Fail Role',
                                   'description' => 'Will fail'
                                ]);
        $responseCreate->assertStatus(403);

        // Update -> forbidden
        $responseUpdate = $this->actingAs($user, 'api')
                               ->withHeader('X-Session-ID', $sessionId)
                               ->putJson("/api/admin/roles/{$managerRole->id}", [
                                   'name' => 'Manager Updated',
                                   'description' => 'Will fail'
                               ]);
        $responseUpdate->assertStatus(403);

        // Delete -> forbidden
        $responseDelete = $this->actingAs($user, 'api')
                               ->withHeader('X-Session-ID', $sessionId)
                               ->deleteJson("/api/admin/roles/{$managerRole->id}");
        $responseDelete->assertStatus(403);
    }

    public function test_sales_marketing_admin_can_view_sales_marketing_role_permissions_and_sync()
    {
        [$user, $sessionId] = $this->getSalesMarketingAdminUserWithSession();
        $managerRole = Role::where('name', 'Manager')->first();
        $itAdminRole = Role::where('name', 'IT Admin')->first();

        // View allowed role permissions -> allowed
        $responseView = $this->actingAs($user, 'api')
                             ->withHeader('X-Session-ID', $sessionId)
                             ->getJson("/api/admin/roles/{$managerRole->id}/permissions");
        $responseView->assertStatus(200);

        // View disallowed role permissions -> forbidden
        $responseViewDisallowed = $this->actingAs($user, 'api')
                                       ->withHeader('X-Session-ID', $sessionId)
                                       ->getJson("/api/admin/roles/{$itAdminRole->id}/permissions");
        $responseViewDisallowed->assertStatus(403);

        // Sync allowed role permissions -> allowed
        $permissions = Permission::limit(2)->pluck('id')->toArray();
        $responseSync = $this->actingAs($user, 'api')
                             ->withHeader('X-Session-ID', $sessionId)
                             ->postJson("/api/admin/roles/{$managerRole->id}/permissions", [
                                 'permissions' => $permissions
                             ]);
        $responseSync->assertStatus(200);

        // Sync disallowed role permissions -> forbidden
        $responseSyncDisallowed = $this->actingAs($user, 'api')
                                       ->withHeader('X-Session-ID', $sessionId)
                                       ->postJson("/api/admin/roles/{$itAdminRole->id}/permissions", [
                                           'permissions' => $permissions
                                       ]);
        $responseSyncDisallowed->assertStatus(403);
    }

    public function test_sales_marketing_admin_can_only_see_sales_marketing_users_for_sales_marketing_roles()
    {
        [$user, $sessionId] = $this->getSalesMarketingAdminUserWithSession();
        $managerRole = Role::where('name', 'Manager')->first();
        
        $salesMarketingDept = Department::where('name', 'Sales & Marketing')->first();
        $opsDept = Department::where('name', 'Operations')->first();

        // User in Sales & Marketing department with Manager role
        $salesMarketingUser = User::create(['email' => 'sales-marketing-user-test@example.com', 'is_active' => true]);
        UserProfile::create([
            'user_id' => $salesMarketingUser->id,
            'role_id' => $managerRole->id,
            'department_id' => $salesMarketingDept->id,
            'first_name' => 'Sales Marketing',
            'last_name' => 'User'
        ]);

        // User in Operations department with Manager role (simulated cross-boundary user)
        $opsUser = User::create(['email' => 'ops-user-test@example.com', 'is_active' => true]);
        UserProfile::create([
            'user_id' => $opsUser->id,
            'role_id' => $managerRole->id,
            'department_id' => $opsDept->id,
            'first_name' => 'Ops',
            'last_name' => 'User'
        ]);

        $response = $this->actingAs($user, 'api')
                         ->withHeader('X-Session-ID', $sessionId)
                         ->getJson("/api/admin/roles/{$managerRole->id}/users");

        $response->assertStatus(200);
        
        $emails = collect($response->json()['data'])->pluck('email')->toArray();
        $this->assertContains('sales-marketing-user-test@example.com', $emails);
        $this->assertNotContains('ops-user-test@example.com', $emails);
    }

    public function test_sales_marketing_admin_can_only_assign_sales_marketing_roles_to_sales_marketing_users()
    {
        [$user, $sessionId] = $this->getSalesMarketingAdminUserWithSession();
        $managerRole = Role::where('name', 'Manager')->first();
        $itAdminRole = Role::where('name', 'IT Admin')->first();

        $salesMarketingDept = Department::where('name', 'Sales & Marketing')->first();
        $opsDept = Department::where('name', 'Operations')->first();

        // User in Sales & Marketing department
        $salesMarketingUser = User::create(['email' => 'sales-marketing-user-assign@sbsi.com', 'is_active' => true]);
        UserProfile::create([
            'user_id' => $salesMarketingUser->id,
            'role_id' => Role::where('name', 'Employee')->first()->id,
            'department_id' => $salesMarketingDept->id,
            'first_name' => 'Sales Marketing',
            'last_name' => 'User'
        ]);

        // User in Operations department
        $opsUser = User::create(['email' => 'ops-user-assign@sbsi.com', 'is_active' => true]);
        UserProfile::create([
            'user_id' => $opsUser->id,
            'role_id' => Role::where('name', 'Employee')->first()->id,
            'department_id' => $opsDept->id,
            'first_name' => 'Ops',
            'last_name' => 'User'
        ]);

        // 1. Assign allowed role to Sales & Marketing user -> allowed
        $response1 = $this->actingAs($user, 'api')
                          ->withHeader('X-Session-ID', $sessionId)
                          ->patchJson("/api/admin/users/{$salesMarketingUser->id}/role", [
                              'role_id' => $managerRole->id
                          ]);
        $response1->assertStatus(200);
        $this->assertEquals($managerRole->id, $salesMarketingUser->fresh()->profile->role_id);

        // 2. Assign disallowed role to Sales & Marketing user -> forbidden
        $response2 = $this->actingAs($user, 'api')
                          ->withHeader('X-Session-ID', $sessionId)
                          ->patchJson("/api/admin/users/{$salesMarketingUser->id}/role", [
                              'role_id' => $itAdminRole->id
                          ]);
        $response2->assertStatus(403);

        // 3. Assign allowed role to Operations user -> forbidden
        $response3 = $this->actingAs($user, 'api')
                          ->withHeader('X-Session-ID', $sessionId)
                          ->patchJson("/api/admin/users/{$opsUser->id}/role", [
                              'role_id' => $managerRole->id
                          ]);
        $response3->assertStatus(403);
    }

    public function test_sales_marketing_admin_can_view_and_sync_permission_roles_for_sales_marketing_roles()
    {
        [$user, $sessionId] = $this->getSalesMarketingAdminUserWithSession();
        $permission = Permission::where('slug', 'cms.templates.use')->first();

        // 1. View roles for permission -> only shows Manager and Employee (when requested by Sales & Marketing Admin)
        $responseView = $this->actingAs($user, 'api')
                             ->withHeader('X-Session-ID', $sessionId)
                             ->getJson("/api/admin/permissions/{$permission->id}/roles");
        $responseView->assertStatus(200);
        
        $roleNames = collect($responseView->json())->pluck('name')->toArray();
        foreach ($roleNames as $name) {
            $this->assertContains($name, ['Manager', 'Employee']);
        }

        // 2. Sync allowed roles -> allowed
        $managerRole = Role::where('name', 'Manager')->first();
        $employeeRole = Role::where('name', 'Employee')->first();

        // Must preserve existing disallowed roles
        $currentRoleIds = $permission->roles()->pluck('roles.id')->toArray();
        $allowedRoles = Role::whereIn('name', ['Manager', 'Employee'])->pluck('id')->toArray();
        $disallowedRoles = array_diff($currentRoleIds, $allowedRoles);

        $responseSync = $this->actingAs($user, 'api')
                             ->withHeader('X-Session-ID', $sessionId)
                             ->postJson("/api/admin/permissions/{$permission->id}/roles", [
                                 'role_ids' => array_merge($disallowedRoles, [$managerRole->id, $employeeRole->id])
                             ]);
        $responseSync->assertStatus(200);

        // 3. Sync including disallowed roles (trying to add a new disallowed one) -> forbidden
        $itAdminRole = Role::where('name', 'IT Admin')->first();
        $responseSyncDisallowed = $this->actingAs($user, 'api')
                                       ->withHeader('X-Session-ID', $sessionId)
                                       ->postJson("/api/admin/permissions/{$permission->id}/roles", [
                                           'role_ids' => array_merge($disallowedRoles, [$managerRole->id, $itAdminRole->id])
                                        ]);
        $responseSyncDisallowed->assertStatus(403);
    }

    public function test_sales_marketing_admin_can_only_create_user_with_sales_marketing_roles_and_sales_marketing_dept()
    {
        [$user, $sessionId] = $this->getSalesMarketingAdminUserWithSession();
        $managerRole = Role::where('name', 'Manager')->first();
        $itAdminRole = Role::where('name', 'IT Admin')->first();
        
        $salesMarketingDept = Department::where('name', 'Sales & Marketing')->first();
        $opsDept = Department::where('name', 'Operations')->first();

        // 1. Create user with Sales & Marketing dept & Manager role -> allowed
        $response1 = $this->actingAs($user, 'api')
                          ->withHeader('X-Session-ID', $sessionId)
                          ->postJson('/api/admin/users', [
                              'email' => 'new-sales-marketing-mgr@sbsi.com',
                              'first_name' => 'New',
                              'last_name' => 'Manager',
                              'role_id' => $managerRole->id,
                              'department_id' => $salesMarketingDept->id,
                          ]);
        $response1->assertStatus(201);

        // 2. Create user with Sales & Marketing dept & Disallowed role (IT Admin) -> forbidden
        $response2 = $this->actingAs($user, 'api')
                          ->withHeader('X-Session-ID', $sessionId)
                          ->postJson('/api/admin/users', [
                              'email' => 'new-global-mgr@sbsi.com',
                              'first_name' => 'New',
                              'last_name' => 'Manager',
                              'role_id' => $itAdminRole->id,
                              'department_id' => $salesMarketingDept->id,
                          ]);
        $response2->assertStatus(403);

        // 3. Create user with Operations dept & Manager role -> forbidden
        $response3 = $this->actingAs($user, 'api')
                          ->withHeader('X-Session-ID', $sessionId)
                          ->postJson('/api/admin/users', [
                              'email' => 'new-ops-mgr@sbsi.com',
                              'first_name' => 'New',
                              'last_name' => 'Manager',
                              'role_id' => $managerRole->id,
                              'department_id' => $opsDept->id,
                          ]);
        $response3->assertStatus(403);
    }
}
