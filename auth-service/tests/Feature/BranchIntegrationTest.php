<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class BranchIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $branchUser;
    protected User $noBranchUser;
    protected Branch $makatiBranch;
    protected string $internalSecret = 'testing_secret_key_123';
    protected array $sessions = [];

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.internal_service_secret' => $this->internalSecret]);
        putenv("INTERNAL_SERVICE_SECRET={$this->internalSecret}");
        $_ENV['INTERNAL_SERVICE_SECRET'] = $this->internalSecret;
        $_SERVER['INTERNAL_SERVICE_SECRET'] = $this->internalSecret;

        // Roles & Permissions
        $itAdminRole = Role::firstOrCreate(['name' => 'IT Admin'], ['description' => 'IT Admin Role']);
        $manageUsersPerm = Permission::firstOrCreate(['slug' => 'manage-users'], ['name' => 'Manage Users']);
        $manageDeptsPerm = Permission::firstOrCreate(['slug' => 'manage-departments'], ['name' => 'Manage Departments']);

        DB::table('role_permission')->insertOrIgnore([
            ['role_id' => $itAdminRole->id, 'permission_id' => $manageUsersPerm->id],
            ['role_id' => $itAdminRole->id, 'permission_id' => $manageDeptsPerm->id],
        ]);

        $employeeRole = Role::firstOrCreate(['name' => 'Technician'], ['description' => 'Ticketing Technician']);
        $dept = Department::firstOrCreate(['name' => 'IT Support'], ['description' => 'IT Support Dept']);

        $this->makatiBranch = Branch::firstOrCreate(
            ['name' => 'Makati Commercial Branch'],
            ['address' => 'Ayala Ave, Makati City']
        );

        // 1. Admin User
        $this->adminUser = User::factory()->create(['email' => 'admin@test.com', 'is_active' => true]);
        UserProfile::create([
            'user_id' => $this->adminUser->id,
            'first_name' => 'Admin',
            'last_name' => 'User',
            'role_id' => $itAdminRole->id,
            'department_id' => $dept->id,
            'branch_id' => $this->makatiBranch->id,
        ]);

        // 2. User with Branch (e.g. for Ticketing)
        $this->branchUser = User::factory()->create(['email' => 'tech@test.com', 'is_active' => true]);
        UserProfile::create([
            'user_id' => $this->branchUser->id,
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'role_id' => $employeeRole->id,
            'department_id' => $dept->id,
            'branch_id' => $this->makatiBranch->id,
        ]);

        // 3. User WITHOUT Branch (e.g. standard CRMS/SERMS/PRS user)
        $this->noBranchUser = User::factory()->create(['email' => 'nobranch@test.com', 'is_active' => true]);
        UserProfile::create([
            'user_id' => $this->noBranchUser->id,
            'first_name' => 'Maria',
            'last_name' => 'Clara',
            'role_id' => $employeeRole->id,
            'department_id' => $dept->id,
            'branch_id' => null,
        ]);

        $this->setupSessionFor($this->adminUser);
        $this->setupSessionFor($this->branchUser);
        $this->setupSessionFor($this->noBranchUser);
    }

    protected function setupSessionFor(User $user): void
    {
        $sessionId = Str::uuid()->toString();
        DB::table('user_sessions')->insert([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Testing',
            'is_active' => true,
            'last_active_at' => now(),
            'created_at' => now(),
        ]);

        $this->sessions[$user->id] = $sessionId;
    }

    protected function actingAsAdmin(): self
    {
        $this->adminUser->is_password_changed = true;
        $this->adminUser->save();

        return $this->actingAs($this->adminUser, 'api')
            ->withHeader('X-Session-ID', $this->sessions[$this->adminUser->id] ?? '');
    }

    public function test_internal_verify_token_returns_branch_data_when_assigned(): void
    {
        $token = $this->branchUser->createToken('TestToken')->accessToken;

        $response = $this->withHeaders([
            'X-Internal-Secret' => $this->internalSecret,
        ])->postJson('/api/internal/verify-token', [
            'token' => $token,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'valid' => true,
            'user' => [
                'id' => $this->branchUser->id,
                'email' => 'tech@test.com',
                'name' => 'Juan Dela Cruz',
                'role' => 'Technician',
                'department' => 'IT Support',
                'branch_id' => $this->makatiBranch->id,
                'branch' => 'Makati Commercial Branch',
                'branch_details' => [
                    'id' => $this->makatiBranch->id,
                    'name' => 'Makati Commercial Branch',
                    'address' => 'Ayala Ave, Makati City',
                ],
            ],
        ]);
    }

    public function test_internal_verify_token_returns_null_branch_safely_when_unassigned(): void
    {
        $token = $this->noBranchUser->createToken('TestToken')->accessToken;

        $response = $this->withHeaders([
            'X-Internal-Secret' => $this->internalSecret,
        ])->postJson('/api/internal/verify-token', [
            'token' => $token,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'valid' => true,
            'user' => [
                'id' => $this->noBranchUser->id,
                'email' => 'nobranch@test.com',
                'name' => 'Maria Clara',
                'role' => 'Technician',
                'department' => 'IT Support',
                'branch_id' => null,
                'branch' => null,
                'branch_details' => null,
            ],
        ]);
    }

    public function test_internal_verify_token_rejects_unauthorized_internal_secret(): void
    {
        $token = $this->branchUser->createToken('TestToken')->accessToken;

        $response = $this->withHeaders([
            'X-Internal-Secret' => 'wrong_secret',
        ])->postJson('/api/internal/verify-token', [
            'token' => $token,
        ]);

        $response->assertStatus(403);
        $response->assertJson(['valid' => false, 'message' => 'Forbidden.']);
    }

    public function test_internal_branches_catalog_endpoint_success_and_forbidden(): void
    {
        // 1. Success with valid secret
        $response = $this->withHeaders([
            'X-Internal-Secret' => $this->internalSecret,
        ])->getJson('/api/internal/branches');

        $response->assertStatus(200)
            ->assertJson(['valid' => true])
            ->assertJsonFragment(['name' => 'Makati Commercial Branch']);

        // 2. Forbidden with invalid secret
        $forbiddenResponse = $this->withHeaders([
            'X-Internal-Secret' => 'bad_secret',
        ])->getJson('/api/internal/branches');

        $forbiddenResponse->assertStatus(403);
    }

    public function test_internal_user_endpoint_includes_branch_safely(): void
    {
        $response = $this->withHeaders([
            'X-Internal-Secret' => $this->internalSecret,
        ])->getJson("/api/internal/users/{$this->branchUser->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $this->branchUser->id,
                'email' => 'tech@test.com',
                'branch_id' => $this->makatiBranch->id,
                'branch' => 'Makati Commercial Branch',
            ]);
    }

    public function test_admin_can_assign_and_unassign_branch_to_user_with_audit_log(): void
    {
        // Start: user has branch
        $this->assertEquals($this->makatiBranch->id, $this->branchUser->fresh()->profile->branch_id);

        // 1. Unassign branch (branch_id = null)
        $unassignResponse = $this->actingAsAdmin()->patchJson("/api/admin/users/{$this->branchUser->id}/branch", [
            'branch_id' => null,
        ]);

        $unassignResponse->assertStatus(200)
            ->assertJson(['message' => 'Branch assigned successfully.']);

        $this->assertNull($this->branchUser->fresh()->profile->branch_id);

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'USER_BRANCH_CHANGED',
        ]);

        // 2. Re-assign branch
        $reassignResponse = $this->actingAsAdmin()->patchJson("/api/admin/users/{$this->branchUser->id}/branch", [
            'branch_id' => $this->makatiBranch->id,
        ]);

        $reassignResponse->assertStatus(200);
        $this->assertEquals($this->makatiBranch->id, $this->branchUser->fresh()->profile->branch_id);
    }

    public function test_admin_user_filter_by_branch(): void
    {
        $response = $this->actingAsAdmin()->getJson("/api/admin/users?branch_id={$this->makatiBranch->id}");

        $response->assertStatus(200);
        $data = $response->json('data');

        // Both adminUser and branchUser belong to makatiBranch, noBranchUser does not
        $emails = array_column($data, 'email');
        $this->assertContains('tech@test.com', $emails);
        $this->assertNotContains('nobranch@test.com', $emails);
    }
}
