<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

final class InternalUserController extends Controller
{
    private function verifySecret(Request $request): ?JsonResponse
    {
        $secret = (string) $request->header('X-Internal-Secret');
        $expectedSecret = (string) env('INTERNAL_SERVICE_SECRET');

        if ($secret === '' || $expectedSecret === '' || !hash_equals($expectedSecret, $secret)) {
            return response()->json(['valid' => false, 'message' => 'Forbidden.'], 403);
        }

        return null;
    }

    /**
     * GET /internal/users/{id}
     */
    public function show(Request $request, $id): JsonResponse
    {
        $forbidden = $this->verifySecret($request);
        if ($forbidden) {
            return $forbidden;
        }

        $user = User::with(['profile.role', 'profile.department', 'profile.branch'])->find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        return response()->json([
            'id' => $user->id,
            'email' => $user->email,
            'first_name' => $user->profile?->first_name ?? '',
            'last_name' => $user->profile?->last_name ?? '',
            'role' => $user->profile?->role?->name,
            'department' => $user->profile?->department?->name,
            'branch_id' => $user->profile?->branch_id,
            'branch' => $user->profile?->branch?->name,
            'branch_details' => $user->profile?->branch ? [
                'id' => $user->profile->branch->id,
                'name' => $user->profile->branch->name,
                'address' => $user->profile->branch->address,
            ] : null,
        ]);
    }

    /**
     * GET /internal/users-by-roles
     */
    public function getUsersByRoles(Request $request): JsonResponse
    {
        $forbidden = $this->verifySecret($request);
        if ($forbidden) {
            return $forbidden;
        }

        $rolesString = $request->query('roles', '');
        $rolesList = array_filter(array_map('trim', explode(',', $rolesString)));

        if (empty($rolesList)) {
            return response()->json(['data' => []]);
        }

        $users = User::where('is_active', true)
            ->whereHas('profile.role', function ($query) use ($rolesList) {
                $query->whereIn('name', $rolesList);
            })
            ->with(['profile.role', 'profile.department', 'profile.branch'])
            ->get();

        $data = $users->map(fn ($user) => [
            'id' => $user->id,
            'email' => $user->email,
            'first_name' => $user->profile?->first_name ?? '',
            'last_name' => $user->profile?->last_name ?? '',
            'role' => $user->profile?->role?->name,
            'department' => $user->profile?->department?->name,
            'branch_id' => $user->profile?->branch_id,
            'branch' => $user->profile?->branch?->name,
            'branch_details' => $user->profile?->branch ? [
                'id' => $user->profile->branch->id,
                'name' => $user->profile->branch->name,
                'address' => $user->profile->branch->address,
            ] : null,
        ])->values()->all();

        return response()->json(['data' => $data]);
    }

    /**
     * GET /internal/users-batch
     */
    public function getUsersBatch(Request $request): JsonResponse
    {
        $forbidden = $this->verifySecret($request);
        if ($forbidden) {
            return $forbidden;
        }

        $idsString = $request->query('ids', '');
        $idsList = array_filter(array_map('intval', explode(',', $idsString)));

        if (empty($idsList)) {
            return response()->json(['data' => []]);
        }

        $users = User::whereIn('id', $idsList)
            ->with(['profile.role', 'profile.department', 'profile.branch'])
            ->get();

        $data = $users->map(fn ($user) => [
            'id' => $user->id,
            'email' => $user->email,
            'first_name' => $user->profile?->first_name ?? '',
            'last_name' => $user->profile?->last_name ?? '',
            'role' => $user->profile?->role?->name,
            'department' => $user->profile?->department?->name,
            'branch_id' => $user->profile?->branch_id,
            'branch' => $user->profile?->branch?->name,
            'branch_details' => $user->profile?->branch ? [
                'id' => $user->profile->branch->id,
                'name' => $user->profile->branch->name,
                'address' => $user->profile->branch->address,
            ] : null,
        ])->values()->all();

        return response()->json(['data' => $data]);
    }
}
