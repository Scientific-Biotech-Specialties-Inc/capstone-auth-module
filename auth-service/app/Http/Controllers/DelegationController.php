<?php

namespace App\Http\Controllers;

use App\Services\DelegationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DelegationController extends Controller
{
    protected DelegationService $delegationService;

    public function __construct(DelegationService $delegationService)
    {
        $this->delegationService = $delegationService;
    }

    /**
     * GET /me/delegations — delegations created by the current user.
     */
    public function myDelegations(Request $request)
    {
        $delegations = $this->delegationService->listOwn($request->user());
        return response()->json($this->formatMany($delegations));
    }

    /**
     * POST /me/delegations — create a new delegation of a role the current
     * user currently holds.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'role_id'           => 'required|integer|exists:roles,id',
            'delegate_user_id'  => 'required|integer|exists:users,id',
            'start_date'        => 'required|date',
            'end_date'          => 'required|date',
        ]);

        $delegation = $this->delegationService->createDelegation($validated, $request->user());

        return response()->json($this->format($delegation->load(['delegate.profile', 'role'])), 201);
    }

    /**
     * DELETE /me/delegations/{id} — revoke a delegation the current user
     * created.
     */
    public function revoke(Request $request, $id)
    {
        $delegation = $this->delegationService->revoke((int) $id, $request->user(), isAdmin: false);
        return response()->json($this->format($delegation->load(['delegate.profile', 'role'])));
    }

    /**
     * GET /admin/delegations — every delegation in the system. IT/System
     * admins can view all delegations but do not create delegations on
     * behalf of other users (they can only revoke).
     */
    public function index(Request $request)
    {
        Gate::authorize('manage-roles');
        $delegations = $this->delegationService->listAll();
        return response()->json($this->formatMany($delegations, includeDelegator: true));
    }

    /**
     * DELETE /admin/delegations/{id} — admin revoke of any delegation.
     */
    public function adminRevoke(Request $request, $id)
    {
        Gate::authorize('manage-roles');
        $delegation = $this->delegationService->revoke((int) $id, $request->user(), isAdmin: true);
        return response()->json($this->format($delegation->load(['delegator.profile', 'delegate.profile', 'role'])));
    }

    private function formatMany($delegations, bool $includeDelegator = false): array
    {
        return $delegations->map(fn ($d) => $this->format($d, $includeDelegator))->values()->all();
    }

    private function format($delegation, bool $includeDelegator = false): array
    {
        $data = [
            'id'           => $delegation->id,
            'role_id'      => $delegation->role_id,
            'role_name'    => $delegation->role?->name,
            'delegate'     => $this->formatUserSummary($delegation->delegate),
            'start_date'   => $delegation->start_date?->toDateString(),
            'end_date'     => $delegation->end_date?->toDateString(),
            'status'       => $delegation->status,
            'revoked_at'   => $delegation->revoked_at?->toIso8601String(),
        ];

        if ($includeDelegator) {
            $data['delegator'] = $this->formatUserSummary($delegation->delegator);
        }

        return $data;
    }

    private function formatUserSummary($user): ?array
    {
        if (!$user) {
            return null;
        }

        return [
            'id'    => $user->id,
            'email' => $user->email,
            'name'  => trim(($user->profile?->first_name ?? '') . ' ' . ($user->profile?->last_name ?? '')) ?: $user->email,
        ];
    }
}
