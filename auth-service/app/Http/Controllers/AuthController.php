<?php

namespace App\Http\Controllers;

use App\Helpers\CookieHelper;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use App\Services\AuthService;
use App\Services\RolePermissionService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected AuthService $authService;

    protected RolePermissionService $rolePermissionService;

    protected UserService $userService;

    public function __construct(
        AuthService $authService,
        RolePermissionService $rolePermissionService,
        UserService $userService
    ) {
        $this->authService = $authService;
        $this->rolePermissionService = $rolePermissionService;
        $this->userService = $userService;
    }

    public function getEncryptionKey()
    {
        $config = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        // Create the keypair
        $res = openssl_pkey_new($config);

        if (! $res) {
            $candidates = array_filter([
                getenv('OPENSSL_CONF'),
                dirname(PHP_BINARY).DIRECTORY_SEPARATOR.'extras'.DIRECTORY_SEPARATOR.'ssl'.DIRECTORY_SEPARATOR.'openssl.cnf',
                dirname(PHP_BINARY).DIRECTORY_SEPARATOR.'openssl.cnf',
            ], fn ($path) => is_string($path) && file_exists($path));

            foreach ($candidates as $candidate) {
                $config['config'] = $candidate;
                $res = openssl_pkey_new($config);
                if ($res) {
                    break;
                }
            }
        }

        if (! $res) {
            return response()->json(['message' => 'Failed to generate encryption key'], 500);
        }

        // Extract private key
        openssl_pkey_export($res, $privateKey, null, $config);

        // Extract public key
        $publicKey = openssl_pkey_get_details($res)['key'];

        $keyId = (string) Str::uuid();

        // Store private key in cache for 5 minutes
        Cache::put("rsa_key_{$keyId}", $privateKey, now()->addMinutes(5));

        return response()->json([
            'public_key' => $publicKey,
            'key_id' => $keyId,
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->loadMissing(['profile.role', 'profile.department', 'profile.branch']);

        return response()->json([
            'user' => $this->authService->formatUserForFrontend($user),
        ]);
    }

    public function login(Request $request)
    {
        Log::info('Login Attempt Data:', $request->all());

        $request->merge([
            'email' => trim($request->input('email', '')),
        ]);

        $request->validate([
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|max:255',
        ]);

        $result = $this->authService->attemptLogin(
            $request->email,
            $request->password,
            $request->ip() ?? '127.0.0.1',
            $request->userAgent() ?? ''
        );

        $response = response()->json([
            'token' => $result['access_token'],
            'access_token' => $result['access_token'],
            'refresh_token' => $result['refresh_token'],
            'session_id' => $result['session_id'],
            'user' => $result['user'],
            'permissions' => $result['permissions'],
            'password_change_required' => $result['password_change_required'],
        ])->withCookie(
            CookieHelper::makeAuthCookie('session_id', $result['session_id'], 60 * 24 * 30)
        )->withCookie(
            CookieHelper::makeStatusCookie('is_authenticated', 'true', 60 * 24 * 30)
        );

        if ($result['access_token']) {
            $response->withCookie(
                CookieHelper::makeAuthCookie('access_token', $result['access_token'], 60 * 24)
            );
        } else {
            $response->withCookie(CookieHelper::forgetAuthCookie('access_token'));
        }

        if ($result['refresh_token']) {
            $response->withCookie(
                CookieHelper::makeAuthCookie('refresh_token', $result['refresh_token'], 60 * 24 * 30)
            );
        } else {
            $response->withCookie(CookieHelper::forgetAuthCookie('refresh_token'));
        }

        return $response;
    }

    public function refresh(Request $request)
    {
        $refreshTokenPlain = $request->cookie('refresh_token') ?: $request->input('refresh_token');

        if (! $refreshTokenPlain) {
            return response()->json(['message' => 'Refresh token missing.'], 401);
        }

        try {
            $result = $this->authService->refreshSession(
                $refreshTokenPlain,
                $request->ip() ?? '127.0.0.1',
                $request->userAgent() ?? ''
            );

            return response()->json([
                'token' => $result['access_token'],
                'access_token' => $result['access_token'],
                'refresh_token' => $result['refresh_token'],
                'user' => $result['user'],
            ])->withCookie(
                CookieHelper::makeAuthCookie('access_token', $result['access_token'], 60 * 24)
            )->withCookie(
                CookieHelper::makeAuthCookie('refresh_token', $result['refresh_token'], 60 * 24 * 30)
            )->withCookie(
                CookieHelper::makeStatusCookie('is_authenticated', 'true', 60 * 24 * 30)
            );
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }

    public function logout(Request $request)
    {
        $refreshTokenPlain = $request->cookie('refresh_token');
        $user = $request->user('api') ?? $request->user();
        $sessionId = $request->cookie('session_id') ?: $request->header('X-Session-ID');

        $this->authService->logout(
            $refreshTokenPlain,
            $sessionId,
            $user,
            $request->ip() ?? '127.0.0.1',
            $request->userAgent() ?? ''
        );

        return response()->json(['message' => 'Successfully logged out.'])
            ->withCookie(CookieHelper::forgetAuthCookie('access_token'))
            ->withCookie(CookieHelper::forgetAuthCookie('refresh_token'))
            ->withCookie(CookieHelper::forgetAuthCookie('session_id'))
            ->withCookie(CookieHelper::forgetAuthCookie('sb-pebzdnartcxnbskjhnhk-auth-token'))
            ->withCookie(CookieHelper::forgetStatusCookie('is_authenticated'));
    }

    public function forgotPassword(Request $request)
    {
        $request->merge([
            'email' => trim($request->input('email', '')),
        ]);

        $request->validate([
            'email' => 'required|string|email|max:255',
        ]);

        $this->authService->sendPasswordReset($request->email, $request->ip());

        // Always return 200 for anti-enumeration
        return response()->json(['message' => 'If an account with that email exists, a password reset link has been sent.']);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        try {
            $this->authService->resetPassword($request->token, $request->password);

            return response()->json(['message' => 'Password has been successfully reset.']);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function sendVerification(Request $request)
    {
        $this->authService->sendVerificationEmail($request->user());

        return response()->json(['message' => 'Verification email sent.']);
    }

    public function verifyEmail(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        try {
            $this->authService->verifyEmail($request->token);

            return response()->json(['message' => 'Email verified successfully.']);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Get permissions for the authenticated user, optionally filtered by system.
     */
    public function permissions(Request $request)
    {
        $system = $request->query('system');
        $permissions = $this->rolePermissionService->getUserPermissionsBySystem($request->user()->id, $system);

        return response()->json([
            'permissions' => $permissions,
        ]);
    }

    public function verifyPassword(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();
        $user->load('credentials');

        if (Hash::check($request->password, $user->credentials->password_hash)) {
            return response()->json(['valid' => true, 'message' => 'Password verified.']);
        }

        return response()->json(['valid' => false, 'message' => 'Invalid password.'], 400);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string', 'max:255'],
            'new_password' => [
                'required',
                'string',
                'min:12',
                'max:255',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[!@#$%^&*(),.?":{}|<>]/',
            ],
        ], [
            'new_password.regex' => 'The password must contain at least one uppercase letter, one number, and one special character.',
            'new_password.min' => 'The password must be at least 12 characters.',
        ]);

        $this->authService->changePassword(
            $request->user(),
            $request->current_password,
            $request->new_password,
            $request->ip(),
            $request->userAgent()
        );

        return response()->json(['message' => 'Password has been successfully updated.']);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->merge([
            'email' => trim($request->input('email', '')),
            'first_name' => trim($request->input('first_name', '')),
            'last_name' => trim($request->input('last_name', '')),
            'middle_name' => $request->has('middle_name') ? trim($request->input('middle_name', '')) : null,
            'phone' => trim($request->input('phone', '')),
        ]);

        $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'middle_name' => 'nullable|string|max:50',
            'phone' => 'required|string|max:20',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
        ]);

        $updatedUser = $this->userService->updateProfile(
            $request->user(),
            $request->only(['email', 'first_name', 'last_name', 'middle_name', 'phone']),
            $request->ip(),
            $request->userAgent()
        );

        return response()->json([
            'message' => 'Profile details updated successfully.',
            'user' => $updatedUser,
        ]);
    }
}
