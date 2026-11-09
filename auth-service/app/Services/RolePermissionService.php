<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RoleNameHistory;
use App\Models\User;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RolePermissionService
{
    protected UserRepositoryInterface $userRepo;

    protected RoleRepositoryInterface $roleRepo;

    protected PermissionRepositoryInterface $permissionRepo;

    protected AuditLogRepositoryInterface $auditLogRepo;

    protected InternalAuditService $internalAuditService;

    public function __construct(
        UserRepositoryInterface $userRepo,
        RoleRepositoryInterface $roleRepo,
        PermissionRepositoryInterface $permissionRepo,
        AuditLogRepositoryInterface $auditLogRepo,
        InternalAuditService $internalAuditService
    ) {
        $this->userRepo = $userRepo;
        $this->roleRepo = $roleRepo;
        $this->permissionRepo = $permissionRepo;
        $this->auditLogRepo = $auditLogRepo;
        $this->internalAuditService = $internalAuditService;
    }

    public function getRolesForUserCreation(?User $actor): array
    {
        try {
            $roles = Cache::remember('roles:all', 3600, function () {
                return Role::all()->toArray();
            });

            if ($actor && ! in_array($actor->profile?->role?->name, ['IT Admin', 'Super Admin'])) {
                if ($actor->profile?->department?->name === 'Sales & Marketing' && $actor->profile?->role?->name === 'Admin') {
                    $allowed = ['Manager', 'Employee'];
                    $roles = array_filter($roles, function ($r) use ($allowed) {
                        return in_array($r['name'], $allowed);
                    });
                    $roles = array_values($roles);
                } else {
                    $roles = [];
                }
            }

            return $roles;
        } catch (\Exception $e) {
            return Role::all()->toArray();
        }
    }

    public function getRolesList(?User $actor = null): array
    {
        try {
            $roles = Cache::remember('roles:list', 3600, function () {
                return $this->roleRepo->all();
            });
        } catch (\Exception $e) {
            Log::warning('Cache unavailable for roles:list, querying DB directly', ['error' => $e->getMessage()]);
            $roles = $this->roleRepo->all();
        }

        if ($actor && ! in_array($actor->profile?->role?->name, ['IT Admin', 'Super Admin'])) {
            if ($actor->profile?->department?->name === 'Sales & Marketing' && $actor->profile?->role?->name === 'Admin') {
                $allowed = ['Manager', 'Employee'];
                $roles = array_filter($roles, function ($r) use ($allowed) {
                    return in_array($r['name'], $allowed);
                });
                $roles = array_values($roles);
            } else {
                $roles = [];
            }
        }

        return $roles;
    }

    public function getPaginatedRoles(int $perPage = 15, ?string $search = null, ?User $actor = null): LengthAwarePaginator
    {
        $allowedNames = $this->visibleRoleNamesForActor($actor);

        if (is_array($allowedNames) && empty($allowedNames)) {
            // Return empty paginator
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
        }

        return $this->roleRepo->paginate($perPage, $search, $allowedNames);
    }

    /**
     * Role names visible to $actor in role listings. Null = unrestricted
     * (IT/Super Admin, or internal calls with no actor). Sales & Marketing
     * Admins see the legacy base names PLUS any custom role currently
     * holding at least one cms-system permission — so granting a role
     * (e.g. CEO) CMS access in the Role Builder automatically surfaces it
     * in CMS-side surfaces (User Management tabs, workflow Role Picker)
     * without code changes. Everyone else sees nothing.
     *
     * Platform admin roles (Super Admin / IT Admin / Admin) stay
     * IT-governed and are never delegated here: the sales admin can SEE
     * their users via tabs but cannot open or modify the roles
     * themselves (see isRoleAllowedForActor + the access-systems
     * endpoint, which includes Admin for tab parity).
     *
     * @return string[]|null
     */
    private function platformAdmin(?User $actor): bool
    {
        return (bool) $actor && in_array($actor->profile?->role?->name, ['IT Admin', 'Super Admin']);
    }

    private function salesMarketingAdmin(?User $actor): bool
    {
        return (bool) $actor
            && $actor->profile?->role?->name === 'Admin'
            && $actor->profile?->department?->name === 'Sales & Marketing';
    }

    /**
     * Role names visible to $actor in role listings. Null = unrestricted
     * (IT/Super Admin, or internal calls with no actor). Sales & Marketing
     * Admins see the legacy base names PLUS any custom role currently
     * holding at least one cms-system permission — so granting a role
     * (e.g. CEO) CMS access in the Role Builder automatically surfaces it
     * in CMS-side surfaces (User Management tabs, workflow Role Picker)
     * without code changes. Everyone else sees nothing.
     *
     * Platform admin roles (Super Admin / IT Admin / Admin) stay
     * IT-governed and are never delegated through listings.
     *
     * @return string[]|null
     */
    private function visibleRoleNamesForActor(?User $actor): ?array
    {
        if (! $actor || $this->platformAdmin($actor)) {
            return null;
        }

        if ($this->salesMarketingAdmin($actor)) {
            return array_values(array_unique(array_merge(
                ['Manager', 'Employee'],
                $this->cmsHoldingRoleNames(['Super Admin', 'IT Admin', 'Admin'])
            )));
        }

        return [];
    }

    /**
     * Distinct names of non-deleted roles holding >= 1 cms-system
     * permission, minus any excluded names. Single join query (no N+1).
     *
     * @param  string[]  $exclude
     * @return string[]
     */
    private function cmsHoldingRoleNames(array $exclude = []): array
    {
        $query = DB::table('roles')
            ->join('role_permission', 'role_permission.role_id', '=', 'roles.id')
            ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
            ->where('permissions.system', 'cms')
            ->whereNull('roles.deleted_at');

        if (! empty($exclude)) {
            $query->whereNotIn('roles.name', $exclude);
        }

        return $query->distinct()->pluck('roles.name')->all();
    }

    /**
     * Whether a single role currently holds >= 1 cms-system permission.
     */
    private function roleHoldsCmsPermission(int $roleId): bool
    {
        return DB::table('role_permission')
            ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
            ->where('role_permission.role_id', $roleId)
            ->where('permissions.system', 'cms')
            ->exists();
    }

    /**
     * Every non-deleted role with its permission systems, scoped to what
     * $actor may see (same rule as listings). Consumed by CMS to render
     * dynamic role tabs: a tab appears for each role whose systems include
     * 'cms'. Deliberately uncached (one cheap join query) so a grant in
     * the Role Builder is visible immediately.
     */
    public function getRolesAccessSystems(?User $actor = null): array
    {
        // Tab parity rule: same visibility as listings, except the Admin
        // role itself is included so CMS keeps its historic Admin tab
        // (filtering user rows — read-only). Admin stays IT-managed:
        // isRoleAllowedForActor still refuses open/sync on it, and infra
        // roles (Super/IT Admin) are hidden everywhere for scoped actors.
        if (! $actor || $this->platformAdmin($actor)) {
            $allowedNames = null;
        } elseif ($this->salesMarketingAdmin($actor)) {
            $allowedNames = array_values(array_unique(array_merge(
                ['Manager', 'Employee'],
                $this->cmsHoldingRoleNames(['Super Admin', 'IT Admin'])
            )));
        } else {
            return [];
        }

        if (is_array($allowedNames) && empty($allowedNames)) {
            return [];
        }

        $driver = DB::connection()->getDriverName();
        $groupConcat = $driver === 'sqlite'
            ? "GROUP_CONCAT(permissions.system, ',') as systems"
            : "GROUP_CONCAT(DISTINCT permissions.system SEPARATOR ',') as systems";

        $query = DB::table('roles')
            ->leftJoin('role_permission', 'role_permission.role_id', '=', 'roles.id')
            ->leftJoin('permissions', 'permissions.id', '=', 'role_permission.permission_id')
            ->whereNull('roles.deleted_at')
            ->select('roles.id', 'roles.name')
            ->selectRaw($groupConcat)
            ->groupBy('roles.id', 'roles.name')
            ->orderBy('roles.id');

        if (is_array($allowedNames)) {
            $query->whereIn('roles.name', $allowedNames);
        }

        return $query->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => $row->name,
                'systems' => $row->systems !== null && $row->systems !== ''
                    ? array_values(array_unique(array_filter(explode(',', (string) $row->systems))))
                    : [],
            ])
            ->all();
    }

    public function getPaginatedPermissions(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return $this->permissionRepo->paginate($perPage, $search);
    }

    public function createRole(array $data, ?User $actor, string $ip, string $userAgent): Role
    {
        if ($actor && ! in_array($actor->profile?->role?->name, ['IT Admin', 'Super Admin'])) {
            throw new HttpResponseException(
                response()->json(['message' => 'Unauthorized to manage role configuration.'], 403)
            );
        }

        $role = $this->roleRepo->create($data);

        $this->invalidateRoleCaches();

        $this->auditLogRepo->log($actor ? $actor->id : null, 'ROLE_CREATED', "Created role: {$role->name}", $ip, $userAgent);

        $this->internalAuditService->pushEvent(
            'ROLE_CREATED',
            'Role',
            $role->id,
            ['message' => "Created role: {$role->name}", 'role_name' => $role->name],
            $actor
        );

        return $role;
    }

    public function getRole(int $id, ?User $actor = null): Role
    {
        $role = $this->roleRepo->findById($id);
        if (! $role) {
            throw (new ModelNotFoundException)->setModel(Role::class, $id);
        }
        if ($actor && ! $this->isRoleVisibleForActor($role, $actor)) {
            throw new HttpResponseException(
                response()->json(['message' => 'Unauthorized to access this role.'], 403)
            );
        }

        return $role;
    }

    public function updateRole(int $id, array $data, ?User $actor, string $ip, string $userAgent): Role
    {
        if ($actor && ! in_array($actor->profile?->role?->name, ['IT Admin', 'Super Admin'])) {
            throw new HttpResponseException(
                response()->json(['message' => 'Unauthorized to manage role configuration.'], 403)
            );
        }

        $role = $this->roleRepo->findById($id);
        if (! $role) {
            throw (new ModelNotFoundException)->setModel(Role::class, $id);
        }

        $oldName = $role->name;
        $oldDesc = $role->description;

        $this->roleRepo->update($id, $data);

        $this->invalidateRoleCaches();

        $role->refresh();

        // Track renames so downstream consumers (e.g. the CMS Visual Contract
        // Workflow Tracker) can show an "edited" indicator plus the previous
        // name on hover for steps that acted on this role before the rename.
        if (array_key_exists('name', $data) && $oldName !== $role->name) {
            RoleNameHistory::create([
                'role_id' => $role->id,
                'previous_name' => $oldName,
                'new_name' => $role->name,
                'changed_by' => $actor?->id,
                'changed_at' => now(),
            ]);
        }

        $this->auditLogRepo->log(
            $actor ? $actor->id : null,
            'ROLE_UPDATED',
            "Updated role '{$oldName}'. Changes: Name[{$oldName} -> {$role->name}], Description[{$oldDesc} -> {$role->description}]",
            $ip,
            $userAgent
        );

        $this->internalAuditService->pushEvent(
            'ROLE_UPDATED',
            'Role',
            $role->id,
            ['message' => "Updated role '{$oldName}'", 'role_name' => $role->name],
            $actor
        );

        return $role;
    }

    public function deleteRole(int $id, ?User $actor, string $ip, string $userAgent): void
    {
        if ($actor && ! in_array($actor->profile?->role?->name, ['IT Admin', 'Super Admin'])) {
            throw new HttpResponseException(
                response()->json(['message' => 'Unauthorized to manage role configuration.'], 403)
            );
        }

        if ($this->roleRepo->hasAssignedUsers($id)) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'Cannot delete role with assigned users.',
                    'user_count' => $this->roleRepo->findById($id)->users_count,
                ], 409)
            );
        }

        $role = $this->roleRepo->findById($id);
        $roleName = $role->name;
        $this->roleRepo->delete($id);

        $this->invalidateRoleCaches();

        $this->auditLogRepo->log($actor ? $actor->id : null, 'ROLE_DELETED', "Deleted role: {$roleName}", $ip, $userAgent);

        $this->internalAuditService->pushEvent(
            'ROLE_DELETED',
            'Role',
            $id,
            ['message' => "Deleted role: {$roleName}", 'role_name' => $roleName],
            $actor
        );
    }

    public function getRoleUsers(int $roleId, ?User $actor = null): LengthAwarePaginator
    {
        $role = $this->roleRepo->findById($roleId);
        if (! $role) {
            throw (new ModelNotFoundException)->setModel(Role::class, $roleId);
        }
        if ($actor && ! $this->isRoleVisibleForActor($role, $actor)) {
            throw new HttpResponseException(
                response()->json(['message' => 'Unauthorized to view users for this role.'], 403)
            );
        }

        if ($actor && $actor->profile?->role?->name === 'Admin' && $actor->profile?->department?->name === 'Sales & Marketing') {
            return User::whereHas('profile', function ($query) use ($roleId, $actor) {
                $query->where('role_id', $roleId)
                    ->where('department_id', $actor->profile->department_id);
            })->with(['profile.department'])->paginate(15);
        }

        return $this->roleRepo->getUsers($roleId);
    }

    public function assignRole(int $userId, int $roleId, ?User $actor, string $ip, string $userAgent): User
    {
        $user = $this->userRepo->findById($userId);
        if (! $user) {
            throw (new ModelNotFoundException)->setModel(User::class, $userId);
        }

        $role = $this->roleRepo->findById($roleId);
        if (! $role) {
            throw (new ModelNotFoundException)->setModel(Role::class, $roleId);
        }

        if ($actor && ! $this->isRoleAllowedForActor($role, $actor)) {
            throw new HttpResponseException(
                response()->json(['message' => 'Unauthorized to assign this role.'], 403)
            );
        }

        if ($actor && $actor->profile?->role?->name === 'Admin' && $actor->profile?->department?->name === 'Sales & Marketing') {
            if ($user->profile?->department_id != $actor->profile->department_id) {
                throw new HttpResponseException(
                    response()->json(['message' => 'You can only assign roles to users in the Sales & Marketing department.'], 403)
                );
            }
        }

        try {
            DB::beginTransaction();

            $profile = $user->profile;
            $oldRoleName = $profile->role ? $profile->role->name : 'None';

            $profile->update(['role_id' => $role->id]);

            // Invalidate permission cache
            $this->invalidateUserPermissionCache($user->id);

            $this->auditLogRepo->log(
                $actor ? $actor->id : null,
                'ROLE_ASSIGNED',
                "Assigned role {$role->name} to user {$user->email} (was {$oldRoleName})",
                $ip,
                $userAgent
            );

            $this->internalAuditService->pushEvent(
                'ROLE_ASSIGNED',
                'User',
                $user->id,
                ['message' => "Assigned role {$role->name} to user {$user->email} (was {$oldRoleName})", 'role_name' => $role->name, 'email' => $user->email],
                $actor
            );

            DB::commit();

            return $user->load('profile.role');

        } catch (\Exception $e) {
            DB::rollBack();
            throw new HttpResponseException(
                response()->json(['message' => 'Failed to assign role.', 'error' => $e->getMessage()], 500)
            );
        }
    }

    public function getRolePermissions(int $roleId, ?User $actor = null): array
    {
        $role = $this->roleRepo->findById($roleId);
        if (! $role) {
            throw (new ModelNotFoundException)->setModel(Role::class, $roleId);
        }
        if ($actor && ! $this->isRoleVisibleForActor($role, $actor)) {
            throw new HttpResponseException(
                response()->json(['message' => 'Unauthorized to view permissions for this role.'], 403)
            );
        }

        return $this->roleRepo->getRolePermissions($roleId);
    }

    public function syncRolePermissions(int $roleId, array $permissionIds, ?User $actor, string $ip, string $userAgent): void
    {
        $role = $this->roleRepo->findById($roleId);
        if (! $role) {
            throw (new ModelNotFoundException)->setModel(Role::class, $roleId);
        }

        if ($actor && ! $this->isRoleAllowedForActor($role, $actor)) {
            throw new HttpResponseException(
                response()->json(['message' => 'Unauthorized to modify permissions for this role.'], 403)
            );
        }

        $this->roleRepo->syncPermissions($roleId, $permissionIds);

        // Role↔permission mapping changed: clear cached role listings so
        // dynamic CMS visibility (cms-holding roles) updates immediately.
        $this->invalidateRoleCaches();

        // Invalidate permission cache for all users with this role
        $userIds = User::whereHas('profile', function ($q) use ($roleId) {
            $q->where('role_id', $roleId);
        })->pluck('id');

        foreach ($userIds as $userId) {
            $this->invalidateUserPermissionCache($userId);
        }

        $this->auditLogRepo->log(
            $actor ? $actor->id : null,
            'ROLE_PERMISSIONS_UPDATED',
            "Updated permissions for role: {$role->name}",
            $ip,
            $userAgent
        );

        $this->internalAuditService->pushEvent(
            'ROLE_PERMISSIONS_UPDATED',
            'Role',
            $roleId,
            ['message' => "Updated permissions for role: {$role->name}", 'role_name' => $role->name],
            $actor
        );
    }

    public function getUserPermissions(int $userId): array
    {
        try {
            return Cache::remember("user_permissions:{$userId}", 86400, function () use ($userId) {
                return $this->userRepo->getUserPermissions($userId);
            });
        } catch (\Exception $e) {
            Log::warning('Cache unavailable for user permissions, querying DB directly', ['error' => $e->getMessage()]);

            return $this->userRepo->getUserPermissions($userId);
        }
    }

    public function getUserPermissionsBySystem(int $userId, ?string $system = null): array
    {
        if ($system) {
            $user = $this->userRepo->findById($userId, ['profile.role']);
            if (! $user || ! $user->profile || ! $user->profile->role) {
                return [];
            }

            return $user->profile->role->permissions()
                ->where('system', $system)
                ->pluck('slug')
                ->toArray();
        }

        return $this->getUserPermissions($userId);
    }

    public function getAllPermissions(): Collection
    {
        return $this->permissionRepo->all();
    }

    public function createPermission(array $data, ?User $actor, string $ip, string $userAgent): Permission
    {
        $permission = $this->permissionRepo->create($data);

        $this->auditLogRepo->log(
            $actor ? $actor->id : null,
            'PERMISSION_CREATED',
            "Created permission: {$permission->name} ({$permission->slug})",
            $ip,
            $userAgent
        );

        return $permission;
    }

    public function getPermission(int $id): Permission
    {
        $permission = $this->permissionRepo->findById($id);
        if (! $permission) {
            throw (new ModelNotFoundException)->setModel(Permission::class, $id);
        }

        return $permission;
    }

    public function updatePermission(int $id, array $data, ?User $actor, string $ip, string $userAgent): Permission
    {
        $permission = $this->permissionRepo->findById($id);
        if (! $permission) {
            throw (new ModelNotFoundException)->setModel(Permission::class, $id);
        }

        $this->permissionRepo->update($id, $data);

        $permission->refresh();

        // Invalidate cache for all affected users
        $this->invalidateAffectedUsers($permission);

        $this->auditLogRepo->log(
            $actor ? $actor->id : null,
            'PERMISSION_UPDATED',
            "Updated permission: {$permission->name}",
            $ip,
            $userAgent
        );

        return $permission;
    }

    public function deletePermission(int $id, ?User $actor, string $ip, string $userAgent): void
    {
        $permission = $this->permissionRepo->findById($id);
        if (! $permission) {
            throw (new ModelNotFoundException)->setModel(Permission::class, $id);
        }

        // Invalidate cache before deletion
        $this->invalidateAffectedUsers($permission);

        $permissionName = $permission->name;
        $this->permissionRepo->delete($id);

        $this->auditLogRepo->log(
            $actor ? $actor->id : null,
            'PERMISSION_DELETED',
            "Deleted permission: {$permissionName}",
            $ip,
            $userAgent
        );
    }

    public function getPermissionRoles(int $permissionId, ?User $actor = null): Collection
    {
        $permission = $this->permissionRepo->findById($permissionId);
        if (! $permission) {
            throw (new ModelNotFoundException)->setModel(Permission::class, $permissionId);
        }

        $roles = $this->permissionRepo->getRoles($permissionId);

        if ($actor && ! in_array($actor->profile?->role?->name, ['IT Admin', 'Super Admin'])) {
            if ($actor->profile?->role?->name === 'Admin' && $actor->profile?->department?->name === 'Sales & Marketing') {
                $allowedNames = ['Manager', 'Employee'];
                $roles = $roles->filter(function ($role) use ($allowedNames) {
                    return in_array($role->name, $allowedNames);
                });
            } else {
                return new Collection;
            }
        }

        return $roles;
    }

    public function syncPermissionRoles(int $permissionId, array $roleIds, ?User $actor, string $ip, string $userAgent): void
    {
        $permission = $this->permissionRepo->findById($permissionId);
        if (! $permission) {
            throw (new ModelNotFoundException)->setModel(Permission::class, $permissionId);
        }

        if ($actor && ! in_array($actor->profile?->role?->name, ['IT Admin', 'Super Admin'])) {
            if ($actor->profile?->role?->name === 'Admin' && $actor->profile?->department?->name === 'Sales & Marketing') {
                // Get roles currently associated with the permission
                $currentRoleIds = $permission->roles()->pluck('roles.id')->toArray();

                // Allowed roles for Finance Admin
                $allowedRoles = Role::whereIn('name', ['Manager', 'Employee'])->pluck('id')->toArray();

                $disallowedRequested = array_diff($roleIds, $allowedRoles);
                $disallowedCurrent = array_diff($currentRoleIds, $allowedRoles);

                sort($disallowedRequested);
                sort($disallowedCurrent);
                if ($disallowedRequested !== $disallowedCurrent) {
                    throw new HttpResponseException(
                        response()->json(['message' => 'You are only authorized to map permissions to Manager or Employee roles.'], 403)
                    );
                }
            } else {
                throw new HttpResponseException(
                    response()->json(['message' => 'Unauthorized to modify roles for permissions.'], 403)
                );
            }
        }

        $this->permissionRepo->syncRoles($permissionId, $roleIds);

        // Invalidate cache for all users who have this permission
        $this->invalidateAffectedUsers($permission);

        $this->auditLogRepo->log(
            $actor ? $actor->id : null,
            'PERMISSION_ROLES_UPDATED',
            "Updated roles for permission: {$permission->name}",
            $ip,
            $userAgent
        );

        $this->internalAuditService->pushEvent(
            'PERMISSION_ROLES_UPDATED',
            'Permission',
            $permissionId,
            ['message' => "Updated roles for permission: {$permission->name}", 'permission_name' => $permission->name],
            $actor
        );
    }

    private function invalidateAffectedUsers(Permission $permission): void
    {
        try {
            $userIds = $this->permissionRepo->getUsersWithPermission($permission->id);
            foreach ($userIds as $userId) {
                $this->invalidateUserPermissionCache($userId);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to invalidate user permission cache', ['error' => $e->getMessage()]);
        }
    }

    private function invalidateUserPermissionCache(int $userId): void
    {
        try {
            Cache::forget("user_permissions:{$userId}");
        } catch (\Exception $e) {
            Log::warning('Failed to invalidate permission cache', ['user_id' => $userId, 'error' => $e->getMessage()]);
        }
    }

    private function invalidateRoleCaches(): void
    {
        try {
            Cache::forget('roles:list');
            Cache::forget('roles:all');
        } catch (\Exception $e) {
            Log::warning('Failed to invalidate role caches', ['error' => $e->getMessage()]);
        }
    }

    protected function isRoleAllowedForActor(Role|int $role, ?User $actor): bool
    {
        if (! $actor) {
            return false;
        }

        $actorRole = $actor->profile?->role?->name;
        $actorDept = $actor->profile?->department?->name;

        if (in_array($actorRole, ['IT Admin', 'Super Admin'])) {
            return true;
        }

        if ($actorRole === 'Admin' && $actorDept === 'Sales & Marketing') {
            $roleName = $role instanceof Role ? $role->name : $this->roleRepo->findById($role)?->name;
            if (in_array($roleName, ['Manager', 'Employee'])) {
                return true;
            }
            // Platform admin roles stay IT-governed even when they hold
            // cms permissions — otherwise this would be self-escalation.
            if (in_array($roleName, ['Super Admin', 'IT Admin', 'Admin'])) {
                return false;
            }
            // Dynamic CMS visibility: a custom role granted any cms
            // permission becomes manageable here too (mirrors listings).
            $roleId = $role instanceof Role ? $role->id : (int) $role;

            return $roleId > 0 && $this->roleHoldsCmsPermission($roleId);
        }

        return false;
    }

    /**
     * Read visibility: everything the manage guard allows, plus any role
     * holding cms permissions (platform roles included). Reading a role's
     * detail, user list, or permission list cannot escalate privilege; it
     * lets scoped admins validate workflows containing e.g. an Admin step
     * and lets CMS resolve live role names. Writes (assign/sync) stay
     * behind isRoleAllowedForActor. Used by getRole/getRoleUsers/
     * getRolePermissions.
     */
    protected function isRoleVisibleForActor(Role|int $role, ?User $actor): bool
    {
        if ($this->isRoleAllowedForActor($role, $actor)) {
            return true;
        }

        if (! $actor) {
            return false;
        }

        if ($actor->profile?->role?->name === 'Admin'
            && $actor->profile?->department?->name === 'Sales & Marketing') {
            $roleId = $role instanceof Role ? $role->id : (int) $role;

            return $roleId > 0 && $this->roleHoldsCmsPermission($roleId);
        }

        return false;
    }
}
