<?php

namespace App\Services;

use App\Contracts\EmailNotificationServiceInterface;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\SessionRepositoryInterface;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Services\InternalAuditService;


class AuthService
{
    protected UserRepositoryInterface $userRepo;
    protected SessionRepositoryInterface $sessionRepo;
    protected AuditLogRepositoryInterface $auditLogRepo;
    protected InternalAuditService $internalAuditService;
    protected EmailNotificationServiceInterface $emailNotificationService;

    public function __construct(
        UserRepositoryInterface $userRepo,
        SessionRepositoryInterface $sessionRepo,
        AuditLogRepositoryInterface $auditLogRepo,
        InternalAuditService $internalAuditService,
        EmailNotificationServiceInterface $emailNotificationService
    ) {
        $this->userRepo = $userRepo;
        $this->sessionRepo = $sessionRepo;
        $this->auditLogRepo = $auditLogRepo;
        $this->internalAuditService = $internalAuditService;
        $this->emailNotificationService = $emailNotificationService;
    }

    public function attemptLogin(string $email, string $password, string $ip, string $userAgent): array
    {
        $throttleKey = 'login:' . Str::lower($email) . '|' . $ip;

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw new HttpResponseException(
                response()->json([
                    'message' => 'Too many login attempts. Please try again in ' . ceil($seconds / 60) . ' minutes.',
                    'errors' => ['email' => ['Account temporarily locked.']]
                ], 429)
            );
        }

        $user = $this->userRepo->findByEmail($email, ['profile.role', 'profile.department', 'credentials']);

        if (!$user || !Hash::check($password, $user->credentials->password_hash)) {
            RateLimiter::hit($throttleKey, 900); // 15 minutes lockout

            $this->auditLogRepo->log(
                $user ? $user->id : null,
                'LOGIN_FAILED',
                'Failed login attempt for email: ' . $email,
                $ip,
                $userAgent
            );

            throw ValidationException::withMessages([
                'email' => ['Invalid email or password.'],
            ]);
        }

        RateLimiter::clear($throttleKey);

        $shouldIssueTokens = (bool) $user->is_password_changed;
        $accessToken = null;
        $refreshTokenPlain = null;
        $sessionId = (string) Str::uuid();

        $this->sessionRepo->createSession($user->id, $sessionId, $ip, $userAgent);

        if ($shouldIssueTokens) {
            $accessToken = $user->createToken('auth_token')->accessToken;
            $refreshTokenPlain = Str::random(128);
            $refreshTokenHash = hash('sha256', $refreshTokenPlain);
            $this->sessionRepo->createRefreshToken($user->id, $refreshTokenHash, $ip, $userAgent);
        }

        // Log login success synchronously — defer() does not fire reliably under
        // the PHP built-in CLI server (php artisan serve) used in development.
        $this->auditLogRepo->log(
            $user->id,
            'LOGIN_SUCCESS',
            'Successful login for email: ' . $email,
            $ip,
            $userAgent
        );

        if ($user->profile?->department?->name === 'Sales & Marketing') {
            $this->internalAuditService->pushEvent(
                'Login Success',
                'Session',
                $user->id,
                [
                    'email' => $email,
                    'ip_address' => $ip,
                    'user_agent' => $userAgent,
                ],
                $user
            );
        }

        $permissions = $user->profile?->role?->permissions()
            ?->pluck('slug') ?? collect();
            
        \Illuminate\Support\Facades\Cache::put("user_permissions:{$user->id}", $permissions->toArray(), 86400);

        $user->unsetRelation('credentials');

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshTokenPlain,
            'session_id' => $sessionId,
            'password_change_required' => !$shouldIssueTokens,
            'user' => $this->formatUserForFrontend($user),
            'user_model' => $user,
            'permissions' => $permissions
        ];
    }

    public function refreshSession(string $refreshTokenPlain, string $ip, string $userAgent): array
    {
        $tokenHash = hash('sha256', $refreshTokenPlain);
        $tokenRecord = $this->sessionRepo->findRefreshToken($tokenHash);

        if (!$tokenRecord || now()->greaterThan($tokenRecord->expires_at)) {
            throw ValidationException::withMessages([
                'refresh_token' => ['Invalid or expired refresh token.']
            ]);
        }

        // Reuse Detection
        if ($tokenRecord->is_revoked) {
            $this->sessionRepo->revokeAllRefreshTokens($tokenRecord->user_id);
            throw ValidationException::withMessages([
                'refresh_token' => ['Token compromise detected. All sessions revoked.']
            ]);
        }

        // Rotation
        $this->sessionRepo->revokeRefreshToken($tokenHash);

        $newRefreshTokenPlain = Str::random(128);
        $newRefreshTokenHash = hash('sha256', $newRefreshTokenPlain);

        $this->sessionRepo->createRefreshToken($tokenRecord->user_id, $newRefreshTokenHash, $ip, $userAgent);

        $user = $this->userRepo->findById($tokenRecord->user_id);

        if (!$user || !$user->is_active) {
            throw ValidationException::withMessages([
                'refresh_token' => ['User is inactive or not found.']
            ]);
        }

        $accessToken = $user->createToken('auth_token')->accessToken;

        return [
            'access_token' => $accessToken,
            'refresh_token' => $newRefreshTokenPlain,
            'user' => $this->formatUserForFrontend($user),
            'user_model' => $user
        ];
    }

    public function formatUserForFrontend(User $user): array
    {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'is_active' => (bool) $user->is_active,
            'is_password_changed' => (bool) $user->is_password_changed,
            'profile' => $user->profile ? [
                'first_name' => $user->profile->first_name,
                'middle_name' => $user->profile->middle_name,
                'last_name' => $user->profile->last_name,
                'phone' => $user->profile->phone,
                'address' => $user->profile->address,
                'role' => $user->profile->role ? [
                    'name' => $user->profile->role->name,
                    // nav_group drives CMS's route gating (admin/manager/
                    // staff) — must be included here or a freshly logged-in
                    // user has no nav_group until refreshPermissions() runs,
                    // which blocks every route and can loop (see CMS
                    // router's not-found fallback).
                    'nav_group' => $user->profile->role->nav_group,
                ] : null,
                'department' => $user->profile->department ? [
                    'name' => $user->profile->department->name,
                ] : null,
                'branch' => $user->profile->branch ? [
                    'id' => $user->profile->branch->id,
                    'name' => $user->profile->branch->name,
                    'address' => $user->profile->branch->address,
                ] : null,
            ] : null,
        ];
    }

    public function logout(?string $refreshTokenPlain, ?string $sessionId, ?User $user, ?string $ip = null, ?string $userAgent = null): void
    {
        if ($refreshTokenPlain) {
            $tokenHash = hash('sha256', $refreshTokenPlain);
            $this->sessionRepo->revokeRefreshToken($tokenHash);
        }

        if ($user && $user->token()) {
            $tokenId = $user->token()->id;
            \Illuminate\Support\Facades\Cache::put("jwt_blacklist:{$tokenId}", true, 120 * 60);
            \Illuminate\Support\Facades\Cache::forget("user_permissions:{$user->id}");
            $user->token()->revoke();
        }

        if ($sessionId) {
            $this->sessionRepo->invalidateSession($sessionId);
        }

        if ($user) {
            $ipAddress = $ip ?? request()->ip() ?? '';
            $ua = $userAgent ?? request()->userAgent() ?? '';

            $this->auditLogRepo->log(
                $user->id,
                'Logout',
                'User logged out: ' . $user->email,
                $ipAddress,
                $ua
            );

            $user->loadMissing(['profile.department', 'profile.role']);
            if ($user->profile?->department?->name === 'Sales & Marketing') {
                $this->internalAuditService->pushEvent(
                    'Logout',
                    'Session',
                    $user->id,
                    [
                        'email' => $user->email,
                        'ip_address' => $ipAddress,
                        'user_agent' => $ua,
                    ],
                    $user
                );
            }
        }
    }

    public function sendPasswordReset(string $email, string $ip): void
    {
        $rateLimitKey = "pwd_reset:{$ip}";
        $windowStart = now()->subHour();

        $hits = $this->sessionRepo->getRateLimitCount($rateLimitKey, $windowStart);

        if ($hits >= 3) {
            throw new HttpResponseException(
                response()->json(['message' => 'Too many password reset attempts. Please try again later.'], 429)
                    ->header('Retry-After', 3600)
            );
        }

        $this->sessionRepo->logRateLimit($rateLimitKey);

        $user = $this->userRepo->findByEmail($email);

        if ($user) {
            $tokenPlain = Str::random(64);
            $tokenHash = hash('sha256', $tokenPlain);

            $this->sessionRepo->createPasswordResetToken($user->id, $tokenHash);

            $this->emailNotificationService->queueNotification(
                'password_reset',
                $user->email,
                [
                    'email' => $user->email,
                    'reset_url' => rtrim((string) config('app.frontend_url', 'http://localhost:5173'), '/') . '/reset-password?token=' . urlencode($tokenPlain),
                    'app_name' => config('app.name'),
                ],
                $user->id,
                'Reset Your Password'
            );
        }
    }

    public function resetPassword(string $tokenPlain, string $password): void
    {
        $tokenHash = hash('sha256', $tokenPlain);
        $tokenRecord = $this->sessionRepo->findPasswordResetToken($tokenHash);

        if (!$tokenRecord) {
            throw ValidationException::withMessages([
                'token' => ['Invalid or expired password reset token.']
            ]);
        }

        $user = $this->userRepo->findById($tokenRecord->user_id);

        if (!$user) {
            throw ValidationException::withMessages([
                'token' => ['User not found.']
            ]);
        }

        DB::transaction(function () use ($user, $tokenRecord, $password) {
            $updated = $this->userRepo->savePasswordCredentials(
                $user->id,
                Hash::make($password, ['rounds' => 12]),
                false
            );

            if (!$updated) {
                throw ValidationException::withMessages([
                    'password' => ['Unable to update password credentials for this account.']
                ]);
            }

            $this->userRepo->update($user->id, [
                'is_password_changed' => true,
            ]);

            $this->sessionRepo->usePasswordResetToken($tokenRecord->id);
            $this->sessionRepo->invalidateAllSessions($user->id);
            $this->sessionRepo->revokeAllRefreshTokens($user->id);
        });
    }

    public function sendVerificationEmail(User $user): void
    {
        if ($user->email_verified) {
            throw ValidationException::withMessages([
                'email' => ['Email already verified.']
            ]);
        }

        $rateLimitKey = "email_verify:{$user->id}";
        $windowStart = now()->subHours(24);

        $hits = $this->sessionRepo->getRateLimitCount($rateLimitKey, $windowStart);

        if ($hits >= 3) {
            throw new HttpResponseException(
                response()->json(['message' => 'Too many verification attempts. Please try again later.'], 429)
            );
        }

        $this->sessionRepo->logRateLimit($rateLimitKey);

        $tokenPlain = Str::random(64);
        $tokenHash = hash('sha256', $tokenPlain);

        $this->sessionRepo->createEmailVerificationToken($user->id, $tokenHash);

        $url = config('app.frontend_url', 'http://localhost:5173') . '/verify-email?token=' . $tokenPlain;
        $this->emailNotificationService->queueNotification(
            'email_verification',
            $user->email,
            [
                'email' => $user->email,
                'verification_url' => $url,
                'app_name' => config('app.name'),
            ],
            $user->id,
            'Verify Your Email Address'
        );
    }

    public function verifyEmail(string $tokenPlain): void
    {
        $tokenHash = hash('sha256', $tokenPlain);
        $tokenRecord = $this->sessionRepo->findEmailVerificationToken($tokenHash);

        if (!$tokenRecord) {
            throw ValidationException::withMessages([
                'token' => ['Invalid or expired verification token.']
            ]);
        }

        $user = $this->userRepo->findById($tokenRecord->user_id);

        if (!$user) {
            throw ValidationException::withMessages([
                'token' => ['User not found.']
            ]);
        }

        if ($user->email_verified) {
            return;
        }

        DB::transaction(function () use ($user, $tokenRecord) {
            $this->userRepo->update($user->id, [
                'email_verified' => true,
                'email_verified_at' => now(),
            ]);

            $this->sessionRepo->useEmailVerificationToken($tokenRecord->id);
        });
    }

    public function verifyAccessToken(string $token): array
    {
        if (str_contains($token, '|')) {
            $token = explode('|', $token)[1];
        }

        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken || ($accessToken->expires_at && $accessToken->expires_at->isPast())) {
            throw new HttpResponseException(
                response()->json(['valid' => false, 'message' => 'Invalid or expired token.'], 401)
            );
        }

        $user = $accessToken->tokenable->load(['profile.role.permissions', 'profile.department']);

        return [
            'valid' => true,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->profile->first_name,
                'last_name' => $user->profile->last_name,
                'role' => $user->profile->role->name,
                'department' => $user->profile->department->name,
                'permissions' => $user->profile->role->permissions->pluck('slug')
            ]
        ];
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword, string $ip, string $userAgent): void
    {
        $user->load('credentials');
        if (!$user->credentials) {
            throw ValidationException::withMessages([
                'current_password' => ['Password credentials are missing for this account.']
            ]);
        }

        if (!Hash::check($currentPassword, $user->credentials->password_hash)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided current password is incorrect.']
            ]);
        }

        $updated = $this->userRepo->savePasswordCredentials(
            $user->id,
            Hash::make($newPassword),
            false
        );

        if (!$updated) {
            throw ValidationException::withMessages([
                'new_password' => ['Unable to update password credentials for this account.']
            ]);
        }

        $updates = ['is_password_changed' => true];
        if (!$user->is_active) {
            $updates['is_active'] = true;
        }

        $this->userRepo->update($user->id, $updates);

        $this->auditLogRepo->log(
            $user->id,
            'PASSWORD_CHANGED',
            'User changed password: ' . $user->email,
            $ip,
            $userAgent
        );

        $this->internalAuditService->pushEvent(
            'password_changed',
            'User',
            $user->id,
            [
                'email' => $user->email,
            ],
            $user
        );

        $this->emailNotificationService->queueNotification(
            'password_changed_confirmation',
            $user->email,
            [
                'email' => $user->email,
                'first_name' => $user->profile?->first_name,
                'app_name' => config('app.name'),
                'changed_at' => now()->toDateTimeString(),
            ],
            $user->id,
            'Your Password Was Changed'
        );
    }
}
