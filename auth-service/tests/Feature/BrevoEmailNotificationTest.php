<?php

namespace Tests\Feature;

use App\Jobs\SendTransactionalEmailJob;
use App\Models\Department;
use App\Models\EmailNotification;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class BrevoEmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_creation_queues_welcome_temp_password_notification(): void
    {
        Queue::fake();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(DepartmentSeeder::class);
        $this->seed(UserSeeder::class);

        $admin = User::where('email', 'admin@example.com')->first();
        $admin->is_password_changed = true;
        $admin->save();

        $sessionId = (string) Str::uuid();
        DB::table('user_sessions')->insert([
            'user_id' => $admin->id,
            'session_id' => $sessionId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Testing',
            'last_active_at' => now(),
            'is_active' => true,
            'created_at' => now(),
        ]);

        $role = Role::where('name', 'Manager')->first();
        $department = Department::where('name', 'IT')->first();

        $this->actingAs($admin, 'api')
            ->withHeader('X-Session-ID', $sessionId)
            ->postJson('/api/admin/users', [
                'first_name' => 'New',
                'last_name' => 'User',
                'email' => 'brevo-user@example.com',
                'role_id' => $role->id,
                'department_id' => $department->id,
            ])
            ->assertStatus(201);

        $createdUser = User::where('email', 'brevo-user@example.com')->first();

        $this->assertDatabaseHas('email_notifications', [
            'user_id' => $createdUser->id,
            'notification_type' => 'welcome_temp_password',
            'recipient_email' => 'brevo-user@example.com',
            'template_key' => 'welcome_temp_password',
            'status' => 'pending',
        ]);
        Queue::assertPushed(SendTransactionalEmailJob::class);
    }

    public function test_change_password_queues_confirmation_notification(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'email' => 'change-password@example.com',
            'is_password_changed' => false,
            'is_active' => true,
        ]);

        DB::table('user_credentials')->insert([
            'user_id' => $user->id,
            'password_hash' => Hash::make('CurrentPass123!'),
            'must_change_password' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('user_profiles')->insert([
            'user_id' => $user->id,
            'first_name' => 'Change',
            'last_name' => 'Password',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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

        $this->actingAs($user, 'api')
            ->withHeader('X-Session-ID', $sessionId)
            ->postJson('/api/me/password', [
                'current_password' => 'CurrentPass123!',
                'new_password' => 'UpdatedPass123!',
                'new_password_confirmation' => 'UpdatedPass123!',
            ])
            ->assertStatus(200);

        $credentials = DB::table('user_credentials')->where('user_id', $user->id)->first();
        $this->assertNotNull($credentials);
        $this->assertSame(0, (int) $credentials->must_change_password);
        $this->assertNotNull($credentials->password_changed_at);
        $this->assertTrue(Hash::check('UpdatedPass123!', $credentials->password_hash));

        $this->assertDatabaseHas('email_notifications', [
            'user_id' => $user->id,
            'notification_type' => 'password_changed_confirmation',
            'recipient_email' => 'change-password@example.com',
            'status' => 'pending',
        ]);
        Queue::assertPushed(SendTransactionalEmailJob::class);
    }

    public function test_change_password_fails_when_credentials_row_is_missing(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'email' => 'missing-credentials@example.com',
            'is_password_changed' => false,
            'is_active' => true,
        ]);

        DB::table('user_profiles')->insert([
            'user_id' => $user->id,
            'first_name' => 'Missing',
            'last_name' => 'Credentials',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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

        $this->actingAs($user, 'api')
            ->withHeader('X-Session-ID', $sessionId)
            ->postJson('/api/me/password', [
                'current_password' => 'CurrentPass123!',
                'new_password' => 'UpdatedPass123!',
                'new_password_confirmation' => 'UpdatedPass123!',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);

        Queue::assertNothingPushed();
    }

    public function test_brevo_webhook_updates_notification_status(): void
    {
        $notification = EmailNotification::create([
            'notification_type' => 'password_reset',
            'provider' => 'brevo',
            'recipient_email' => 'recipient@example.com',
            'template_key' => 'password_reset',
            'template_id' => '102',
            'provider_message_id' => 'provider-message-123',
            'status' => 'sent',
            'payload' => ['reset_url' => 'http://localhost/reset'],
        ]);

        $secret = (string) config('services.brevo.webhook_secret');
        $headerName = (string) config('services.brevo.webhook_secret_header', 'X-Brevo-Webhook-Secret');
        $headers = $secret !== '' ? [$headerName => $secret] : [];

        $response = $this->withHeaders($headers)->postJson('/api/webhooks/brevo', [
            'event' => 'delivered',
            'message-id' => 'provider-message-123',
            'uuid' => 'brevo-event-1',
            'date' => now()->toIso8601String(),
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('email_notifications', [
            'id' => $notification->id,
            'status' => 'delivered',
        ]);
        $this->assertDatabaseHas('email_notification_events', [
            'email_notification_id' => $notification->id,
            'provider_event_id' => 'brevo-event-1',
            'event_type' => 'delivered',
        ]);
    }

    public function test_transactional_email_job_records_sent_status_and_provider_message_id(): void
    {
        Http::fake([
            'https://api.brevo.com/v3/smtp/email' => Http::response([
                'messageId' => 'brevo-message-789',
            ], 201),
        ]);

        $notification = EmailNotification::create([
            'notification_type' => 'password_reset',
            'provider' => 'brevo',
            'recipient_email' => 'recipient@example.com',
            'subject' => 'Reset Your Password',
            'template_key' => 'password_reset',
            'template_id' => '102',
            'status' => 'pending',
            'payload' => ['reset_url' => 'http://localhost/reset'],
        ]);

        dispatch_sync(new SendTransactionalEmailJob($notification->id));

        $this->assertDatabaseHas('email_notifications', [
            'id' => $notification->id,
            'status' => 'sent',
            'provider_message_id' => 'brevo-message-789',
        ]);
        $this->assertDatabaseHas('email_notification_events', [
            'email_notification_id' => $notification->id,
            'provider_message_id' => 'brevo-message-789',
            'event_type' => 'request',
        ]);
    }
}
