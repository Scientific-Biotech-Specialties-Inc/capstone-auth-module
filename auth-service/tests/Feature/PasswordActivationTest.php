<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Department;
use App\Models\User;
use App\Models\UserCredential;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Self-activation deadlock fix: UI-created accounts start inactive and
 * activate inside changePassword(). That endpoint must therefore be
 * reachable WITHOUT passing the active.session check (which 401s
 * inactive users before the controller ever runs). RSA-encrypts fields
 * in-test exactly like real clients (no reliance on APP_ENV bypasses).
 */
class PasswordActivationTest extends TestCase
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

    private function makeInactiveUser(): User
    {
        $role = Role::where('name', 'Employee')->firstOrFail();
        $dept = Department::where('name', 'Operations')->firstOrFail();

        $user = User::updateOrCreate(
            ['email' => 'inactive.probe@example.com'],
            ['is_active' => false, 'is_password_changed' => false, 'email_verified' => true, 'email_verified_at' => now()]
        );
        UserCredential::updateOrCreate(
            ['user_id' => $user->id],
            ['password_hash' => Hash::make('TempPass123!', ['rounds' => 4]), 'must_change_password' => true, 'password_changed_at' => now()]
        );
        UserProfile::updateOrCreate(
            ['user_id' => $user->id],
            ['first_name' => 'Inactive', 'middle_name' => null, 'last_name' => 'Probe', 'role_id' => $role->id, 'department_id' => $dept->id]
        );

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
        $user->sessionId = $sessionId;

        return $user;
    }

    /** Encrypt fields the way browser clients do (PKCS#1 v1.5 + base64).
     * Uses ONE key for all fields per call (fewer moving parts). */
    private function rsaSeal(string $plain, string $publicKey): string
    {
        openssl_public_encrypt($plain, $encrypted, $publicKey);

        return base64_encode($encrypted);
    }

    private function changePassword(User $user, string $current, string $next)
    {
        $key = $this->getJson('/api/encryption-key')->assertOk()->json();

        return $this->actingAs($user, 'api')
            ->withHeaders([
                'X-Session-ID' => $user->sessionId,
                'X-Key-Id' => $key['key_id'],
            ])
            ->postJson('/api/me/password', [
                'current_password' => $this->rsaSeal($current, $key['public_key']),
                'new_password' => $this->rsaSeal($next, $key['public_key']),
                'new_password_confirmation' => $this->rsaSeal($next, $key['public_key']),
            ]);
    }

    public function test_inactive_user_can_change_password_and_activates()
    {
        $user = $this->makeInactiveUser();
        $this->assertFalse((bool) $user->is_active);

        $response = $this->changePassword($user, 'TempPass123!', 'NewSecurePass456@');

        $response->assertStatus(200);
        $user->refresh();
        $this->assertTrue((bool) $user->is_active);
        $this->assertTrue((bool) $user->is_password_changed);
        $freshHash = DB::table('user_credentials')->where('user_id', $user->id)->value('password_hash');
        $this->assertTrue(Hash::check('NewSecurePass456@', (string) $freshHash));
    }

    public function test_change_password_still_rejects_wrong_current_password()
    {
        $user = $this->makeInactiveUser();

        $response = $this->changePassword($user, 'WrongPass999!', 'NewSecurePass456@');

        $response->assertStatus(422);
        $this->assertFalse((bool) $user->refresh()->is_active);
    }
}
