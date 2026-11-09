<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Dynamic CMS role visibility: granting a role any cms-system permission
 * surfaces it in listings, detail/sync guards, and the new
 * access-systems endpoint consumed by CMS for dynamic role tabs.
 * Session helper duplicated from SalesMarketingAdminRolePermissionTest
 * (small, avoids cross-file coupling).
 */
class RoleAccessSystemsTest extends TestCase
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

    private function userWithSession(string $email): array
    {
        $user = User::where('email', $email)->first();
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

    private function ceoRole(): Role
    {
        return Role::where('name', 'CEO')->firstOrFail();
    }

    /** Strip every cms-system permission from CEO (proves the tab disappears). */
    private function revokeCeoCmsAccess(): void
    {
        $cmsIds = Permission::where('system', 'cms')->pluck('id')->all();
        DB::table('role_permission')
            ->where('role_id', $this->ceoRole()->id)
            ->whereIn('permission_id', $cmsIds)
            ->delete();
    }

    /** Grant CEO exactly one cms permission (proves the tab reappears). */
    private function grantCeoCmsView(): void
    {
        $permId = Permission::where('slug', 'cms.contracts.view')->firstOrFail()->id;
        DB::table('role_permission')->insertOrIgnore([
            'role_id' => $this->ceoRole()->id,
            'permission_id' => $permId,
        ]);
    }

    private function accessSystems(User $user, string $sessionId)
    {
        return $this->actingAs($user, 'api')
            ->withHeader('X-Session-ID', $sessionId)
            ->getJson('/api/admin/roles/access-systems');
    }

    public function test_it_admin_sees_all_roles_with_systems()
    {
        [$user, $sessionId] = $this->userWithSession('admin@example.com');

        $response = $this->accessSystems($user, $sessionId);
        $response->assertStatus(200);

        $rows = collect($response->json());
        $this->assertTrue($rows->count() >= 8);
        $manager = $rows->firstWhere('name', 'Manager');
        $this->assertContains('cms', $manager['systems']);
        $itAdmin = $rows->firstWhere('name', 'IT Admin');
        $this->assertNotContains('cms', $itAdmin['systems']);
    }

    public function test_sales_admin_sees_only_cms_scoped_roles_until_ceo_granted()
    {
        [$user, $sessionId] = $this->userWithSession('sales-marketing-admin@example.com');

        // CEO starts with full CMS access from the seeder — revoke it to
        // prove the tab disappears, then re-grant to prove it reappears.
        $this->revokeCeoCmsAccess();

        $names = collect($this->accessSystems($user, $sessionId)->json())->pluck('name')->all();
        $this->assertContains('Manager', $names);
        $this->assertContains('Employee', $names);
        $this->assertNotContains('CEO', $names);
        $this->assertNotContains('IT Admin', $names);

        $this->grantCeoCmsView();

        $rows = collect($this->accessSystems($user, $sessionId)->json());
        $ceo = $rows->firstWhere('name', 'CEO');
        $this->assertNotNull($ceo);
        $this->assertContains('cms', $ceo['systems']);
    }

    public function test_sales_admin_listings_include_newly_granted_role()
    {
        [$user, $sessionId] = $this->userWithSession('sales-marketing-admin@example.com');

        $response = $this->actingAs($user, 'api')
            ->withHeader('X-Session-ID', $sessionId)
            ->getJson('/api/admin/roles?per_page=100');

        $response->assertStatus(200);
        $names = collect($response->json()['data'])->pluck('name')->all();
        $this->assertContains('CEO', $names);
        // Platform admin roles stay IT-governed: visible nowhere here.
        $this->assertNotContains('Admin', $names);
        $this->assertNotContains('IT Admin', $names);
        $this->assertNotContains('Super Admin', $names);
    }

    public function test_sales_admin_can_view_and_sync_newly_granted_role()
    {
        [$user, $sessionId] = $this->userWithSession('sales-marketing-admin@example.com');
        $ceo = $this->ceoRole();

        $this->actingAs($user, 'api')
            ->withHeader('X-Session-ID', $sessionId)
            ->getJson("/api/admin/roles/{$ceo->id}")
            ->assertStatus(200);

        $permIds = Permission::whereIn('slug', ['cms.contracts.view', 'cms.contracts.edit'])->pluck('id')->all();
        $this->actingAs($user, 'api')
            ->withHeader('X-Session-ID', $sessionId)
            ->postJson("/api/admin/roles/{$ceo->id}/permissions", ['permissions' => $permIds])
            ->assertStatus(200);
    }

    public function test_manager_cannot_access_systems_endpoint()
    {
        [$user, $sessionId] = $this->userWithSession('sales-marketing-manager@example.com');

        $this->accessSystems($user, $sessionId)->assertStatus(403);
    }

    public function test_platform_roles_stay_it_governed()
    {
        [$user, $sessionId] = $this->userWithSession('sales-marketing-admin@example.com');
        $admin = Role::where('name', 'Admin')->firstOrFail();

        // Admin tab exists for filtering users (endpoint parity)...
        $rows = collect($this->accessSystems($user, $sessionId)->json());
        $this->assertNotNull($rows->firstWhere('name', 'Admin'));

        // ...its detail/permissions are readable (needed e.g. to validate
        // workflows containing an Admin step)...
        $this->actingAs($user, 'api')
            ->withHeader('X-Session-ID', $sessionId)
            ->getJson("/api/admin/roles/{$admin->id}")
            ->assertStatus(200);

        // ...but the role itself can never be modified (no self-escalation).
        $this->actingAs($user, 'api')
            ->withHeader('X-Session-ID', $sessionId)
            ->postJson("/api/admin/roles/{$admin->id}/permissions", ['permissions' => []])
            ->assertStatus(403);
    }

    public function test_deleted_roles_are_excluded()
    {
        [$user, $sessionId] = $this->userWithSession('admin@example.com');

        $ceo = $this->ceoRole();
        $ceo->delete();

        $names = collect($this->accessSystems($user, $sessionId)->json())->pluck('name')->all();
        $this->assertNotContains('CEO', $names);
    }
}
