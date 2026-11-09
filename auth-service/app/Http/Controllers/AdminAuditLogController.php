<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAuditLogController extends Controller
{
    /**
     * Get paginated audit logs for the administration authority.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, (int) $request->input('per_page', 15)));
        $query = AuditLog::with(['actor.profile.department', 'actor.profile.role']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('actor', function ($uq) use ($search) {
                      $uq->where('email', 'like', "%{$search}%")
                         ->orWhereHas('profile', function ($pq) use ($search) {
                             $pq->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                         });
                  });
            });
        }

        if ($action = $request->input('action')) {
            $query->where('action', $action);
        }

        if ($date = $request->input('date')) {
            $query->whereDate('action_date', $date);
        }

        $paginator = $query->orderBy('action_date', 'desc')->paginate($perPage);

        $logs = collect($paginator->items())->map(function ($log) {
            return [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'performed_at' => $log->action_date ? $log->action_date->toIso8601String() : null,
                'actor' => $log->actor ? [
                    'id' => $log->actor->id,
                    'email' => $log->actor->email,
                    'first_name' => $log->actor->profile?->first_name,
                    'last_name' => $log->actor->profile?->last_name,
                    'department' => $log->actor->profile?->department?->name,
                    'role' => $log->actor->profile?->role?->name,
                ] : null,
            ];
        });

        return response()->json([
            'data' => $logs,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }
}
