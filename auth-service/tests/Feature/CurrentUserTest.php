<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrentUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->disableCookieEncryption();
        $this->seed();
    }

    /**
     * Login with the given seeded account and return the request headers
     * needed to authenticate subsequent API calls (Passport bearer token
     * plus the active session ID).
     *
     * @return array{Accept: string, Authorization: string, X-Session-ID: string}
     */
    private function loginAndGetAuthHeaders(string $email): array
    {
        $loginResponse = $this->postJson('/api/login', [
            'email' => $email,
            'password' => 'password',
        ]);

        $loginResponse->assertStatus(200);

        $cookies = $loginResponse->headers->getCookies();
        $accessToken = null;
        $sessionId = null;

        foreach ($cookies as $cookie) {
            if ($cookie->getName() === 'access_token') {
                $accessToken = $cookie->getValue();
            }
            if ($cookie->getName() === 'session_id') {
                $sessionId = $cookie->getValue();
            }
        }

        $this->assertNotNull($accessToken);
        $this->assertNotNull($sessionId);

        return [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
            'X-Session-ID' => $sessionId,
        ];
    }

    public function test_authenticated_it_admin_gets_role_from_user_endpoint(): void
    {
        $user = User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($user);

        $headers = $this->loginAndGetAuthHeaders('admin@example.com');

        $response = $this->withHeaders($headers)->getJson('/api/user');

        $response->assertStatus(200);
        $response->assertJsonPath('user.email', 'admin@example.com');
        $response->assertJsonPath('user.profile.role.name', 'IT Admin');
    }

    public function test_authenticated_non_admin_gets_their_role_from_user_endpoint(): void
    {
        $user = User::where('email', 'sales@example.com')->first();
        $this->assertNotNull($user);

        $headers = $this->loginAndGetAuthHeaders('sales@example.com');

        $response = $this->withHeaders($headers)->getJson('/api/user');

        $response->assertStatus(200);
        $response->assertJsonPath('user.email', 'sales@example.com');
        $response->assertJsonPath('user.profile.role.name', 'Sales');
    }

    public function test_unauthenticated_user_endpoint_returns_401(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_with_is_password_changed_false_can_access_user_endpoint(): void
    {
        $user = User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($user);

        $headers = $this->loginAndGetAuthHeaders('admin@example.com');

        $user->update(['is_password_changed' => false]);

        $response = $this->withHeaders($headers)->getJson('/api/user');

        $response->assertStatus(200);
        $response->assertJsonPath('user.email', 'admin@example.com');
        $response->assertJsonPath('user.is_password_changed', false);
        $response->assertJsonPath('user.profile.role.name', 'IT Admin');
    }
}
