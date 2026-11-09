<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\RoleResolutionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Internal (service-to-service) endpoints consumed by CMS's
 * contract-management service for the dynamic approval workflow engine:
 * resolving who currently holds a role (primary + active delegation), and
 * resolving a role's display identity (deleted / renamed) for the Visual
 * Contract Workflow Tracker.
 *
 * Secured the same way as the existing InternalUserController endpoints:
 * a shared X-Internal-Secret header, no end-user session required.
 */
final class InternalRoleController extends Controller
{
    private RoleResolutionService $roleResolution;

    public function __construct(RoleResolutionService $roleResolution)
    {
        $this->roleResolution = $roleResolution;
    }

    private function verifySecret(Request $request): ?JsonResponse
    {
        $secret = $request->header('X-Internal-Secret');
        $expectedSecret = env('INTERNAL_SERVICE_SECRET');

        if (!$secret || $secret !== $expectedSecret) {
            return response()->json(['valid' => false, 'message' => 'Forbidden.'], 403);
        }

        return null;
    }

    /**
     * GET /internal/roles/{id}/active-holders
     *
     * Active users currently holding the role, either as their primary
     * role or via an active delegation. Used by the CMS approval engine to
     * determine step eligibility and to detect an empty role (zero
     * holders) that should put the contract on hold and notify the admin.
     */
    public function activeHolders(Request $request, $id): JsonResponse
    {
        $forbidden = $this->verifySecret($request);
        if ($forbidden) {
            return $forbidden;
        }

        $holders = $this->roleResolution->activeHoldersOf((int) $id);

        $data = $holders->map(fn ($user) => [
            'id'         => $user->id,
            'email'      => $user->email,
            'first_name' => $user->profile?->first_name ?? '',
            'last_name'  => $user->profile?->last_name ?? '',
            'department' => $user->profile?->department?->name,
        ])->values()->all();

        return response()->json([
            'data'  => $data,
            'empty' => count($data) === 0,
        ]);
    }

    /**
     * GET /internal/roles/{id}/describe
     *
     * Resolves a role's current display identity: whether it still
     * exists, whether it was soft-deleted, its current (or last-known)
     * name, and its rename history. CMS caches a role_name_snapshot at
     * workflow-step save time and compares it against this at render time
     * to decide whether to show the "deleted" or "edited" indicator on the
     * Visual Contract Workflow Tracker.
     */
    public function describe(Request $request, $id): JsonResponse
    {
        $forbidden = $this->verifySecret($request);
        if ($forbidden) {
            return $forbidden;
        }

        return response()->json($this->roleResolution->describeRole((int) $id));
    }
}
