<?php

namespace Tests\Feature;

use App\Models\Delegation;
use App\Models\Role;
use App\Models\User;
use App\Models\UserCredential;
use App\Models\UserProfile;
use App\Services\DelegationService;
use App\Services\RoleResolutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DelegationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\DepartmentSeeder::class);
    }

    private function makeUser(string $email, string $roleName, bool $isActive = true): User
    {
        $user = User::create([
            'email' => $email,
            'is_active' => $isActive,
            'is_password_changed' => true,
            'email_verified' => true,
            'email_verified_at' => now(),
        ]);

        UserCredential::create([
            'user_id' => $user->id,
            'password_hash' => Hash::make('password', ['rounds' => 4]),
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);

        $role = Role::where('name', $roleName)->first();

        UserProfile::create([
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => $email,
            'role_id' => $role?->id,
        ]);

        return $user->load('profile.role');
    }

    private function getSessionHeader(User $user): array
    {
        $sessionId = (string) \Illuminate\Support\Str::uuid();
        DB::table('user_sessions')->insert([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Testing',
            'last_active_at' => now(),
            'is_active' => true,
            'created_at' => now(),
        ]);

        return [$sessionId];
    }

    // ── Delegation validation rules (spec 5.2, scoped to whole-role) ──────

    public function test_delegator_can_create_a_valid_delegation()
    {
        $delegator = $this->makeUser('delegator@example.com', 'Sales');
        $delegate = $this->makeUser('delegate@example.com', 'Employee');

        $service = app(DelegationService::class);
        $delegation = $service->createDelegation([
            'role_id' => $delegator->profile->role_id,
            'delegate_user_id' => $delegate->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ], $delegator);

        $this->assertEquals('active', $delegation->status);
        $this->assertEquals($delegator->id, $delegation->delegator_user_id);
        $this->assertEquals($delegate->id, $delegation->delegate_user_id);
    }

    public function test_future_dated_delegation_is_scheduled_not_active()
    {
        $delegator = $this->makeUser('delegator2@example.com', 'Sales');
        $delegate = $this->makeUser('delegate2@example.com', 'Employee');

        $service = app(DelegationService::class);
        $delegation = $service->createDelegation([
            'role_id' => $delegator->profile->role_id,
            'delegate_user_id' => $delegate->id,
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
        ], $delegator);

        $this->assertEquals('scheduled', $delegation->status);
    }

    public function test_cannot_self_delegate()
    {
        $delegator = $this->makeUser('selfdeleg@example.com', 'Sales');

        $this->expectException(ValidationException::class);
        app(DelegationService::class)->createDelegation([
            'role_id' => $delegator->profile->role_id,
            'delegate_user_id' => $delegator->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ], $delegator);
    }

    public function test_cannot_delegate_a_role_you_do_not_hold()
    {
        $delegator = $this->makeUser('notholder@example.com', 'Sales');
        $delegate = $this->makeUser('delegate3@example.com', 'Employee');
        $managerRole = Role::where('name', 'Manager')->first();

        $this->expectException(ValidationException::class);
        app(DelegationService::class)->createDelegation([
            'role_id' => $managerRole->id,
            'delegate_user_id' => $delegate->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ], $delegator);
    }

    public function test_delegate_must_be_an_active_user()
    {
        $delegator = $this->makeUser('delegator4@example.com', 'Sales');
        $inactiveDelegate = $this->makeUser('inactive@example.com', 'Employee', isActive: false);

        $this->expectException(ValidationException::class);
        app(DelegationService::class)->createDelegation([
            'role_id' => $delegator->profile->role_id,
            'delegate_user_id' => $inactiveDelegate->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ], $delegator);
    }

    public function test_end_date_cannot_be_before_start_date()
    {
        $delegator = $this->makeUser('delegator5@example.com', 'Sales');
        $delegate = $this->makeUser('delegate5@example.com', 'Employee');

        $this->expectException(ValidationException::class);
        app(DelegationService::class)->createDelegation([
            'role_id' => $delegator->profile->role_id,
            'delegate_user_id' => $delegate->id,
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->toDateString(),
        ], $delegator);
    }

    public function test_end_date_cannot_be_in_the_past()
    {
        $delegator = $this->makeUser('delegator6@example.com', 'Sales');
        $delegate = $this->makeUser('delegate6@example.com', 'Employee');

        $this->expectException(ValidationException::class);
        app(DelegationService::class)->createDelegation([
            'role_id' => $delegator->profile->role_id,
            'delegate_user_id' => $delegate->id,
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->subDays(1)->toDateString(),
        ], $delegator);
    }

    public function test_delegate_cannot_re_delegate_a_role_received_via_delegation()
    {
        $primaryHolder = $this->makeUser('primary@example.com', 'Sales');
        $delegate = $this->makeUser('delegate7@example.com', 'Employee');
        $thirdParty = $this->makeUser('third@example.com', 'Finance');

        $service = app(DelegationService::class);
        $service->createDelegation([
            'role_id' => $primaryHolder->profile->role_id,
            'delegate_user_id' => $delegate->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
        ], $primaryHolder);

        // $delegate now holds the Sales role only via delegation — they
        // must not be able to delegate it onward to a third party.
        $this->expectException(ValidationException::class);
        $service->createDelegation([
            'role_id' => $primaryHolder->profile->role_id,
            'delegate_user_id' => $thirdParty->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ], $delegate);
    }

    public function test_cannot_create_circular_delegation()
    {
        $userA = $this->makeUser('usera@example.com', 'Sales');
        $userB = $this->makeUser('userb@example.com', 'Employee');

        $service = app(DelegationService::class);

        // A delegates the Sales role to B.
        $service->createDelegation([
            'role_id' => $userA->profile->role_id,
            'delegate_user_id' => $userB->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
        ], $userA);

        // B does not primarily hold Sales, so a straightforward "delegate
        // it back to A" attempt is already blocked by the primary-holder
        // check (not the circular check specifically) — this still proves
        // the net effect: no A -> B -> A loop can be formed.
        $this->expectException(ValidationException::class);
        $service->createDelegation([
            'role_id' => $userA->profile->role_id,
            'delegate_user_id' => $userA->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ], $userB);
    }

    // ── Revocation ──────────────────────────────────────────────────────

    public function test_delegator_can_revoke_their_own_delegation()
    {
        $delegator = $this->makeUser('revoker@example.com', 'Sales');
        $delegate = $this->makeUser('revokee@example.com', 'Employee');

        $delegation = app(DelegationService::class)->createDelegation([
            'role_id' => $delegator->profile->role_id,
            'delegate_user_id' => $delegate->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ], $delegator);

        $revoked = app(DelegationService::class)->revoke($delegation->id, $delegator);

        $this->assertEquals('revoked', $revoked->status);
        $this->assertNotNull($revoked->revoked_at);
    }

    public function test_non_delegator_cannot_revoke_without_admin_flag()
    {
        $delegator = $this->makeUser('revoker2@example.com', 'Sales');
        $delegate = $this->makeUser('revokee2@example.com', 'Employee');
        $stranger = $this->makeUser('stranger@example.com', 'Finance');

        $delegation = app(DelegationService::class)->createDelegation([
            'role_id' => $delegator->profile->role_id,
            'delegate_user_id' => $delegate->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ], $delegator);

        $this->expectException(\Illuminate\Http\Exceptions\HttpResponseException::class);
        app(DelegationService::class)->revoke($delegation->id, $stranger, isAdmin: false);
    }

    // ── Role resolution: active holders (primary + delegated) ─────────────

    public function test_active_holders_includes_primary_role_users()
    {
        $salesUser = $this->makeUser('sales1@example.com', 'Sales');
        $salesRoleId = $salesUser->profile->role_id;

        $holders = app(RoleResolutionService::class)->activeHoldersOf($salesRoleId);

        $this->assertTrue($holders->pluck('id')->contains($salesUser->id));
    }

    public function test_active_holders_includes_active_delegates()
    {
        $delegator = $this->makeUser('delegator8@example.com', 'Sales');
        $delegate = $this->makeUser('delegate8@example.com', 'Employee');

        app(DelegationService::class)->createDelegation([
            'role_id' => $delegator->profile->role_id,
            'delegate_user_id' => $delegate->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ], $delegator);

        $holders = app(RoleResolutionService::class)->activeHoldersOf($delegator->profile->role_id);

        $this->assertTrue($holders->pluck('id')->contains($delegator->id));
        $this->assertTrue($holders->pluck('id')->contains($delegate->id));
    }

    public function test_active_holders_excludes_expired_delegation()
    {
        $delegator = $this->makeUser('delegator9@example.com', 'Sales');
        $delegate = $this->makeUser('delegate9@example.com', 'Employee');

        // Create a delegation that already ended yesterday by inserting
        // directly (bypassing the "end date in the past" validation that
        // createDelegation enforces on NEW delegations), to simulate one
        // that naturally expired.
        Delegation::create([
            'delegator_user_id' => $delegator->id,
            'delegate_user_id' => $delegate->id,
            'role_id' => $delegator->profile->role_id,
            'start_date' => now()->subDays(10),
            'end_date' => now()->subDays(1),
            'status' => 'active',
        ]);

        $holders = app(RoleResolutionService::class)->activeHoldersOf($delegator->profile->role_id);

        $this->assertTrue($holders->pluck('id')->contains($delegator->id));
        $this->assertFalse($holders->pluck('id')->contains($delegate->id));
    }

    public function test_active_holders_excludes_revoked_delegation()
    {
        $delegator = $this->makeUser('delegator10@example.com', 'Sales');
        $delegate = $this->makeUser('delegate10@example.com', 'Employee');

        $delegation = app(DelegationService::class)->createDelegation([
            'role_id' => $delegator->profile->role_id,
            'delegate_user_id' => $delegate->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ], $delegator);

        app(DelegationService::class)->revoke($delegation->id, $delegator);

        $holders = app(RoleResolutionService::class)->activeHoldersOf($delegator->profile->role_id);

        $this->assertFalse($holders->pluck('id')->contains($delegate->id));
    }

    public function test_active_holders_excludes_inactive_delegate_account()
    {
        $delegator = $this->makeUser('delegator11@example.com', 'Sales');
        $delegate = $this->makeUser('delegate11@example.com', 'Employee');

        app(DelegationService::class)->createDelegation([
            'role_id' => $delegator->profile->role_id,
            'delegate_user_id' => $delegate->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ], $delegator);

        // Delegate's account becomes inactive after the delegation was made.
        $delegate->update(['is_active' => false]);

        $holders = app(RoleResolutionService::class)->activeHoldersOf($delegator->profile->role_id);

        $this->assertFalse($holders->pluck('id')->contains($delegate->id));
    }

    public function test_has_no_active_holders_true_when_role_empty()
    {
        $emptyRole = Role::create(['name' => 'Nobody Holds This', 'description' => 'test', 'nav_group' => 'staff']);

        $isEmpty = app(RoleResolutionService::class)->hasNoActiveHolders($emptyRole->id);

        $this->assertTrue($isEmpty);
    }

    // ── Role identity resolution: deleted / renamed ────────────────────────

    public function test_describe_role_for_existing_unrenamed_role()
    {
        $role = Role::create(['name' => 'Fresh Role', 'description' => 'test', 'nav_group' => 'staff']);

        $desc = app(RoleResolutionService::class)->describeRole($role->id);

        $this->assertTrue($desc['exists']);
        $this->assertFalse($desc['deleted']);
        $this->assertEquals('Fresh Role', $desc['current_name']);
        $this->assertFalse($desc['was_renamed']);
    }

    public function test_describe_role_reflects_soft_delete()
    {
        $role = Role::create(['name' => 'Will Be Deleted', 'description' => 'test', 'nav_group' => 'staff']);
        $role->delete();

        $desc = app(RoleResolutionService::class)->describeRole($role->id);

        $this->assertTrue($desc['exists']);
        $this->assertTrue($desc['deleted']);
        $this->assertEquals('Will Be Deleted', $desc['current_name']);
    }

    public function test_describe_role_tracks_rename_history_via_service()
    {
        [$admin, $sessionId] = $this->adminSession();
        $role = Role::create(['name' => 'Old Label', 'description' => 'test', 'nav_group' => 'staff']);

        app(\App\Services\RolePermissionService::class)->updateRole(
            $role->id,
            ['name' => 'New Label', 'description' => 'test'],
            $admin,
            '127.0.0.1',
            'Testing'
        );

        $desc = app(RoleResolutionService::class)->describeRole($role->id);

        $this->assertTrue($desc['was_renamed']);
        $this->assertEquals('New Label', $desc['current_name']);
        $this->assertEquals('Old Label', $desc['name_history'][0]['previous_name']);
    }

    private function adminSession(): array
    {
        $admin = $this->makeUser('admin-renamer@example.com', 'IT Admin');
        [$sessionId] = $this->getSessionHeader($admin);
        return [$admin, $sessionId];
    }
}
