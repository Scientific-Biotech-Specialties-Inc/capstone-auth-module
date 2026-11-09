<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    /**
     * Get system-wide KPI summary metrics and recent audit trail entries.
     */
    public function stats(Request $request): JsonResponse
    {
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $totalRoles = Role::count();
        $totalDepartments = Department::count();
        $totalBranches = Branch::count();
        $totalPermissions = Permission::count();

        $recentLogs = AuditLog::with(['actor.profile.department', 'actor.profile.role'])
            ->orderBy('action_date', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($log) {
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
            'data' => [
                'kpis' => [
                    'total_users' => $totalUsers,
                    'active_users' => $activeUsers,
                    'total_roles' => $totalRoles,
                    'total_departments' => $totalDepartments,
                    'total_branches' => $totalBranches,
                    'total_permissions' => $totalPermissions,
                ],
                'recent_audit_logs' => $recentLogs,
            ],
        ]);
    }
}
