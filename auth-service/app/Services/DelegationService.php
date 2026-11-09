<?php

namespace App\Services;

use App\Models\Delegation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Handles creation, validation, revocation, and resolution of role
 * delegations. A delegation hands an entire identity role (not a granular
 * permission) from one user to another for a bounded date range.
 *
 * Delegation rules enforced here (trimmed to what applies to whole-role
 * delegation, from the workflow engine plan's Phase 1 scope):
 *   1. No self-delegation.
 *   2. The delegator must currently and PRIMARILY hold the role being
 *      delegated (not via another delegation) — this is what prevents
 *      re-delegation (rule 4 below) as a side effect.
 *   3. No circular delegation (A -> B -> A) for the same role.
 *   4. No re-delegation: a role held only via delegation cannot be
 *      delegated onward (enforced by rule 2's "primarily" check).
 *   5. Delegate must be an active user.
 *   6. start_date <= end_date; end_date cannot be in the past.
 *   7. The role must exist and not be soft-deleted.
 *
 * Separation-of-duties (a delegate can't approve a document they created)
 * is intentionally NOT enforced here — it needs contract-level context
 * (who requested/created the document) and belongs in the CMS approval
 * engine (Phase 5), not in auth-module.
 */
class DelegationService
{
    /**
     * Validate and create a new delegation. The delegator is always the
     * acting user — admins can revoke any delegation (see revoke()) but do
     * not create delegations on behalf of other users.
     */
    public function createDelegation(array $data, User $actor): Delegation
    {
        $roleId = (int) $data['role_id'];
        $delegateUserId = (int) $data['delegate_user_id'];
        $startDate = $data['start_date'];
        $endDate = $data['end_date'];

        $role = Role::find($roleId);
        if (!$role) {
            throw ValidationException::withMessages([
                'role_id' => ['This role does not exist or has been deleted.'],
            ]);
        }

        if ($delegateUserId === $actor->id) {
            throw ValidationException::withMessages([
                'delegate_user_id' => ['You cannot delegate a role to yourself.'],
            ]);
        }

        $delegate = User::with('profile')->find($delegateUserId);
        if (!$delegate || !$delegate->is_active) {
            throw ValidationException::withMessages([
                'delegate_user_id' => ['The selected user must be an active account.'],
            ]);
        }

        // Rule: delegator must PRIMARILY hold the role (not via delegation).
        // This single check doubles as the "no re-delegation" rule — a user
        // whose only access to role_id comes from an active delegation does
        // not primarily hold it, so they fail this check and cannot
        // delegate it onward.
        $actor->loadMissing('profile');
        if ((int) $actor->profile?->role_id !== $roleId) {
            $actorHoldsViaDelegation = Delegation::currentlyActive()
                ->where('role_id', $roleId)
                ->where('delegate_user_id', $actor->id)
                ->exists();

            if ($actorHoldsViaDelegation) {
                throw ValidationException::withMessages([
                    'role_id' => ['This role was delegated to you and cannot be re-delegated.'],
                ]);
            }

            throw ValidationException::withMessages([
                'role_id' => ['You can only delegate a role you currently hold.'],
            ]);
        }

        // Rule: no circular delegation (A -> B -> A) for the same role.
        $circular = Delegation::currentlyActive()
            ->where('role_id', $roleId)
            ->where('delegator_user_id', $delegateUserId)
            ->where('delegate_user_id', $actor->id)
            ->exists();

        if ($circular) {
            throw ValidationException::withMessages([
                'delegate_user_id' => ['This user already has an active delegation of this role back to you.'],
            ]);
        }

        if ($startDate > $endDate) {
            throw ValidationException::withMessages([
                'end_date' => ['End date cannot be before the start date.'],
            ]);
        }

        $today = now()->toDateString();
        if ($endDate < $today) {
            throw ValidationException::withMessages([
                'end_date' => ['End date cannot be in the past.'],
            ]);
        }

        $status = $startDate > $today ? 'scheduled' : 'active';

        return Delegation::create([
            'delegator_user_id' => $actor->id,
            'delegate_user_id'  => $delegateUserId,
            'role_id'           => $roleId,
            'start_date'        => $startDate,
            'end_date'          => $endDate,
            'status'            => $status,
        ]);
    }

    /**
     * Revoke a delegation immediately. The delegator can revoke their own
     * delegation; an admin (gated by the caller via the 'manage-roles'
     * ability) can revoke any delegation.
     */
    public function revoke(int $delegationId, User $actor, bool $isAdmin = false): Delegation
    {
        $delegation = Delegation::find($delegationId);
        if (!$delegation) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(Delegation::class, $delegationId);
        }

        if (!$isAdmin && $delegation->delegator_user_id !== $actor->id) {
            throw new HttpResponseException(
                response()->json(['message' => 'You can only revoke delegations you created.'], 403)
            );
        }

        if ($delegation->status === 'revoked') {
            return $delegation;
        }

        $delegation->update([
            'status'     => 'revoked',
            'revoked_at' => now(),
            'revoked_by' => $actor->id,
        ]);

        return $delegation;
    }

    /**
     * Opportunistically bring stored `status` values in line with reality
     * (scheduled -> active, scheduled/active -> expired) via bulk updates.
     * Cheap enough to call at the top of any listing/resolution endpoint —
     * avoids needing a scheduled job purely for display purposes, while
     * resolution logic itself (currentlyActive()) never depends on this
     * being perfectly fresh.
     */
    public function syncStatuses(): void
    {
        $today = now()->toDateString();

        DB::table('delegations')
            ->where('status', 'scheduled')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->update(['status' => 'active', 'updated_at' => now()]);

        DB::table('delegations')
            ->whereIn('status', ['scheduled', 'active'])
            ->whereDate('end_date', '<', $today)
            ->update(['status' => 'expired', 'updated_at' => now()]);
    }

    /**
     * Delegations created by (or revocable by) the given user.
     */
    public function listOwn(User $user): Collection
    {
        $this->syncStatuses();

        return Delegation::with(['delegate.profile', 'role'])
            ->where('delegator_user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * All delegations in the system (admin view).
     */
    public function listAll(): Collection
    {
        $this->syncStatuses();

        return Delegation::with(['delegator.profile', 'delegate.profile', 'role'])
            ->orderByDesc('created_at')
            ->get();
    }
}
