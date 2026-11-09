<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class InternalVerifyTokenController extends Controller
{
    /**
     * Verify token and return user details with permissions.
     */
    public function __invoke(Request $request)
    {
        $secret = (string) $request->header('X-Internal-Secret');
        $expectedSecret = (string) env('INTERNAL_SERVICE_SECRET');

        if ($secret === '' || $expectedSecret === '' || !hash_equals($expectedSecret, $secret)) {
            return response()->json(['valid' => false, 'message' => 'Forbidden.'], 403);
        }

        $token = $request->input('token');
        if (!$token) {
            return response()->json(['valid' => false, 'message' => 'Token missing.'], 422);
        }

        try {
            // Set Bearer Authorization header so auth('api')->user() reads it
            $request->headers->set('Authorization', 'Bearer ' . $token);
            
            $user = auth('api')->user();
            
            if (!$user || !$user->is_active) {
                return response()->json(['valid' => false]);
            }

            $user->loadMissing(['profile.role.permissions', 'profile.department', 'profile.branch']);
            
            $permissions = $user->profile?->role?->permissions
                ?->pluck('slug')
                ->toArray() ?? [];

            return response()->json([
                'valid' => true,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => trim(($user->profile?->first_name ?? '') . ' ' . ($user->profile?->last_name ?? '')),
                    'role' => $user->profile?->role?->name,
                    'department' => $user->profile?->department?->name,
                    'branch_id' => $user->profile?->branch_id,
                    'branch' => $user->profile?->branch?->name,
                    'branch_details' => $user->profile?->branch ? [
                        'id' => $user->profile->branch->id,
                        'name' => $user->profile->branch->name,
                        'address' => $user->profile->branch->address,
                    ] : null,
                    'permissions' => $permissions,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('InternalVerifyToken error', ['message' => $e->getMessage()]);
            return response()->json(['valid' => false]);
        }
    }
}
