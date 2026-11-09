<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class BranchController extends Controller
{
    protected AuditLogRepositoryInterface $auditLogRepo;

    public function __construct(AuditLogRepositoryInterface $auditLogRepo)
    {
        $this->auditLogRepo = $auditLogRepo;
    }
    /**
     * Display a listing of branches with assigned users count.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Branch::withCount('users');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        $branches = $query->orderBy('name', 'asc')->paginate($perPage);

        return response()->json($branches);
    }

    /**
     * Get simple list of branches for dropdown selectors.
     */
    public function options(): JsonResponse
    {
        $branches = Branch::select(['id', 'name', 'address'])->orderBy('name', 'asc')->get();
        return response()->json($branches);
    }

    /**
     * Store a newly created branch in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:branches,name',
            'address' => 'required|string|max:500',
        ], [
            'name.unique' => 'A branch with this name already exists.',
            'name.required' => 'Branch name is required.',
            'address.required' => 'Branch address is required.',
        ]);

        $branch = Branch::create($validated);

        return response()->json([
            'message' => 'Branch created successfully.',
            'branch' => $branch,
        ], 201);
    }

    /**
     * Display the specified branch.
     */
    public function show(int $id): JsonResponse
    {
        $branch = Branch::withCount('users')->findOrFail($id);
        return response()->json($branch);
    }

    /**
     * Update the specified branch in storage.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $branch = Branch::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:branches,name,' . $branch->id,
            'address' => 'required|string|max:500',
        ], [
            'name.unique' => 'A branch with this name already exists.',
            'name.required' => 'Branch name is required.',
            'address.required' => 'Branch address is required.',
        ]);

        $branch->update($validated);

        return response()->json([
            'message' => 'Branch updated successfully.',
            'branch' => $branch,
        ]);
    }

    /**
     * Remove the specified branch from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $branch = Branch::withCount('users')->findOrFail($id);

        if ($branch->users_count > 0) {
            return response()->json([
                'message' => "Cannot delete branch '{$branch->name}' because it currently has {$branch->users_count} assigned users. Reassign users first."
            ], 422);
        }

        $branch->delete();

        return response()->json([
            'message' => 'Branch deleted successfully.'
        ]);
    }

    /**
     * Internal endpoint for downstream subsystems (like Ticketing System)
     * to fetch branch options using the shared internal service secret.
     */
    public function internalBranches(Request $request): JsonResponse
    {
        $secret = (string) $request->header('X-Internal-Secret');
        $expectedSecret = (string) env('INTERNAL_SERVICE_SECRET');

        if ($secret === '' || $expectedSecret === '' || !hash_equals($expectedSecret, $secret)) {
            return response()->json(['valid' => false, 'message' => 'Forbidden.'], 403);
        }

        $branches = Branch::select(['id', 'name', 'address'])->orderBy('name', 'asc')->get();

        return response()->json([
            'valid' => true,
            'data' => $branches
        ]);
    }

    /**
     * Assign a branch to a user (or clear if branch_id is null).
     */
    public function assignBranch(Request $request, int $userId): JsonResponse
    {
        if (Gate::denies('manage-users') && Gate::denies('manage-departments')) {
            abort(403, 'Unauthorized to assign branches to users.');
        }

        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
        ], [
            'branch_id.exists' => 'The selected branch does not exist.',
        ]);

        $user = User::with('profile.branch')->findOrFail($userId);

        if (!$user->profile) {
            return response()->json(['message' => 'User profile does not exist.'], 422);
        }

        $oldBranchName = $user->profile->branch?->name ?? 'None';
        $newBranchId = !empty($validated['branch_id']) ? (int) $validated['branch_id'] : null;
        $newBranch = $newBranchId ? Branch::find($newBranchId) : null;
        $newBranchName = $newBranch?->name ?? 'None';

        try {
            DB::beginTransaction();

            $user->profile->update(['branch_id' => $newBranchId]);

            $this->auditLogRepo->log(
                $request->user()?->id,
                'USER_BRANCH_CHANGED',
                "Assigned branch {$newBranchName} to user {$user->email} (was {$oldBranchName})",
                $request->ip(),
                $request->userAgent()
            );

            DB::commit();

            $user->load(['profile.role', 'profile.department', 'profile.branch']);

            return response()->json([
                'message' => 'Branch assigned successfully.',
                'user' => $user
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to assign branch.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
