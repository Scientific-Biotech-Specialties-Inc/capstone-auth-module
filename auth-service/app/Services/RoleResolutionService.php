<?php

namespace App\Services;

use App\Models\Delegation;
use App\Models\Role;
use App\Models\RoleNameHistory;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Resolves "who currently holds role R" (primary assignment UNION active
 * delegation) and role identity/history lookups. This is the service that
 * backs the internal endpoints CMS calls to determine workflow-step
 * eligibility, trigger empty-role notifications, and render the deleted
 * /renamed role indicators on the Visual Contract Workflow Tracker.
 */
class RoleResolutionService
{
    /**
     * Active users who currently hold role $roleId, either as their
     * primary assigned role or via an active delegation. "Active" here
     * means the user account is active (is_active = true); an inactive
     * delegate does not count as a holder even if their delegation window
     * is still open.
     *
     * @return Collection<int, User>
     */
    public function activeHoldersOf(int $roleId): Collection
    {
        app(DelegationService::class)->syncStatuses();

        $primaryHolders = User::where('is_active', true)
            ->whereHas('profile', function ($q) use ($roleId) {
                $q->where('role_id', $roleId);
            })
            ->with(['profile.role', 'profile.department'])
            ->get();

        $delegateIds = Delegation::currentlyActive()
            ->where('role_id', $roleId)
            ->pluck('delegate_user_id');

        $delegateHolders = User::where('is_active', true)
            ->whereIn('id', $delegateIds)
            ->with(['profile.role', 'profile.department'])
            ->get();

        return $primaryHolders->concat($delegateHolders)->unique('id')->values();
    }

    /**
     * True when role $roleId has zero active holders (primary or
     * delegated). CMS uses this to put a contract on hold at that step and
     * trigger the automatic admin notification (workflow engine decision
     * #4/#12).
     */
    public function hasNoActiveHolders(int $roleId): bool
    {
        return $this->activeHoldersOf($roleId)->isEmpty();
    }

    /**
     * Resolve a role's current display identity for a consumer like the
     * CMS tracker: whether it still exists, its current name (or
     * last-known name if deleted), and whether it has ever been renamed.
     *
     * @return array{
     *   exists: bool,
     *   deleted: bool,
     *   current_name: string|null,
     *   was_renamed: bool,
     *   name_history: array<int, array{previous_name: string, new_name: string, changed_at: string|null}>
     * }
     */
    public function describeRole(int $roleId): array
    {
        $role = Role::withTrashed()->with('nameHistory')->find($roleId);

        if (!$role) {
            // Role row no longer exists at all (hard-removed outside the
            // normal soft-delete flow, or never existed). Caller should
            // fall back to whatever name snapshot it cached at step-save time.
            return [
                'exists'       => false,
                'deleted'      => true,
                'current_name' => null,
                'was_renamed'  => false,
                'name_history' => [],
            ];
        }

        $history = $role->nameHistory->map(fn (RoleNameHistory $h) => [
            'previous_name' => $h->previous_name,
            'new_name'      => $h->new_name,
            'changed_at'    => $h->changed_at?->toIso8601String(),
        ])->values()->all();

        return [
            'exists'       => true,
            'deleted'      => $role->trashed(),
            'current_name' => $role->name,
            'was_renamed'  => count($history) > 0,
            'name_history' => $history,
        ];
    }
}
