<?php

use App\Http\Controllers\AdminAuditLogController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\BrevoWebhookController;
use App\Http\Controllers\DelegationController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\InternalAuditLogController;
use App\Http\Controllers\InternalRoleController;
use App\Http\Controllers\InternalUserController;
use App\Http\Controllers\InternalVerifyTokenController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

Route::get('/encryption-key', [AuthController::class, 'getEncryptionKey']);
Route::post('/webhooks/brevo', BrevoWebhookController::class);
Route::post('/login', [AuthController::class, 'login'])->name('login')->middleware(['throttle:auth', 'decrypt.rsa:password']);
Route::post('/refresh', [AuthController::class, 'refresh']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:auth');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware(['throttle:auth', 'decrypt.rsa:password,password_confirmation']);

// Password change is the self-activation path for inactive (not yet
// activated) accounts — changePassword() flips is_active on success — so
// it must NOT sit behind the active.session check (which would 401 with
// "User account is inactive" before the controller ever runs). auth:api
// still identifies the caller, and the current password is verified
// inside the service. No privilege change: callers can only ever touch
// their own account.
Route::middleware(['auth:api'])->group(function () {
    Route::post('/me/password', [AuthController::class, 'changePassword'])->middleware('decrypt.rsa:current_password,new_password,new_password_confirmation');
});
Route::get('/verify-email', [AuthController::class, 'verifyEmail']);
Route::post('/me/password', [AuthController::class, 'changePassword'])->middleware(['password.change.session', 'active.session', 'decrypt.rsa:current_password,new_password,new_password_confirmation']);

Route::get('/internal/audit-logs', [InternalAuditLogController::class, 'index']);
Route::post('/internal/verify-token', InternalVerifyTokenController::class);
Route::get('/internal/users/{id}', [InternalUserController::class, 'show']);
Route::get('/internal/users-by-roles', [InternalUserController::class, 'getUsersByRoles']);
Route::get('/internal/users-batch', [InternalUserController::class, 'getUsersBatch']);
Route::get('/internal/roles/{id}/active-holders', [InternalRoleController::class, 'activeHolders']);
Route::get('/internal/roles/{id}/describe', [InternalRoleController::class, 'describe']);
Route::get('/internal/branches', [BranchController::class, 'internalBranches']);

Route::middleware(['auth:api', 'active.session'])->group(function () {
    // Routes that must be accessible even if password change is required
    Route::get('/user', [AuthController::class, 'me']);

    // Routes that require password change
    Route::middleware('require.password.change')->group(function () {
        Route::post('/send-verification', [AuthController::class, 'sendVerification']);
        Route::post('/verify-password', [AuthController::class, 'verifyPassword']);
        Route::get('/me/permissions', [AuthController::class, 'permissions']);
        Route::put('/me/profile', [AuthController::class, 'updateProfile']);

        // "My Delegations" — any user holding a role they can delegate
        // manages their own delegations here (see RolePermissionService
        // Phase 1 decisions: delegators act only on their own behalf).
        Route::get('/me/delegations', [DelegationController::class, 'myDelegations']);
        Route::post('/me/delegations', [DelegationController::class, 'store']);
        Route::delete('/me/delegations/{id}', [DelegationController::class, 'revoke']);
    });
});

Route::middleware(['auth:api', 'active.session', 'require.password.change', 'can:manage-users'])->prefix('admin')->group(function () {
    Route::get('/dashboard-stats', [AdminDashboardController::class, 'stats']);
    Route::get('/audit-logs', [AdminAuditLogController::class, 'index']);
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::post('/users', [AdminUserController::class, 'store']);
    Route::get('/users/{id}', [AdminUserController::class, 'show']);
    Route::patch('/users/{id}/status', [AdminUserController::class, 'toggleStatus']);
    Route::patch('/users/{id}/branch', [BranchController::class, 'assignBranch']);
    Route::get('/department-options', [AdminUserController::class, 'getDepartments']);
    Route::get('/role-options', [AdminUserController::class, 'getRoles']);
    Route::get('/branch-options', [BranchController::class, 'options']);
});

Route::middleware(['auth:api', 'active.session', 'require.password.change', 'can:manage-roles'])->prefix('admin')->group(function () {
    // Static segment BEFORE apiResource: otherwise 'access-systems' would
    // be captured as roles/{role}.
    Route::get('roles/access-systems', [RoleController::class, 'accessSystems']);
    Route::apiResource('roles', RoleController::class);
    Route::get('roles/{id}/users', [RoleController::class, 'users']);
    Route::patch('users/{id}/role', [RoleController::class, 'assignRole']);

    // Permission Management
    Route::apiResource('permissions', PermissionController::class);
    Route::get('permissions/{id}/roles', [PermissionController::class, 'getRoles']);
    Route::post('permissions/{id}/roles', [PermissionController::class, 'syncRoles']);
    Route::get('roles/{id}/permissions', [RoleController::class, 'permissions']);
    Route::post('roles/{id}/permissions', [RoleController::class, 'syncPermissions']);

    // Delegation oversight — IT/System admins can view all delegations and
    // revoke any of them, but do not create delegations on others' behalf.
    Route::get('delegations', [DelegationController::class, 'index']);
    Route::delete('delegations/{id}', [DelegationController::class, 'adminRevoke']);
});

Route::middleware(['auth:api', 'active.session', 'require.password.change', 'can:manage-departments'])->prefix('admin')->group(function () {
    Route::apiResource('departments', DepartmentController::class);
    Route::get('departments/{id}/users', [DepartmentController::class, 'users']);
    Route::patch('users/{id}/department', [DepartmentController::class, 'assignDepartment']);
    Route::apiResource('branches', BranchController::class);
});

Route::middleware(['auth:api', 'active.session', 'require.password.change'])->group(function () {
    Route::get('users/{id}/permissions', [AdminUserController::class, 'getUserPermissions']);
});
