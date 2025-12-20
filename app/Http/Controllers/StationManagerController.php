<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\StationManagerAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StationManagerController extends Controller
{
    /**
     * List all station managers with their assignment status
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::where('role', 'station');

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', (bool) $request->input('is_active'));
        }

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $managers = $query->with(['managerAssignments' => function ($q) {
            $q->active()->latest('assigned_at');
        }, 'managedStations'])->paginate($request->input('per_page', 15));

        // Transform to include station assignment info
        $managers->getCollection()->transform(function ($manager) {
            $activeAssignment = $manager->managerAssignments()->active()->latest('assigned_at')->first();
            
            return [
                'id' => $manager->id,
                'name' => $manager->name,
                'email' => $manager->email,
                'is_active' => $manager->is_active,
                'created_at' => $manager->created_at,
                'assignment_status' => [
                    'is_assigned' => !is_null($activeAssignment),
                    'station_id' => $activeAssignment?->station_id,
                    'station_name' => $activeAssignment?->station?->name,
                    'assigned_at' => $activeAssignment?->assigned_at,
                    'assigned_by' => $activeAssignment?->assignedBy?->name,
                ],
            ];
        });

        return response()->json([
            'message' => 'Station managers retrieved successfully',
            'data' => $managers,
        ], 200);
    }

    /**
     * List only active station managers with their assignment status
     */
    public function active(Request $request): JsonResponse
    {
        $query = User::where('role', 'station')->where('is_active', true);

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $managers = $query->with(['managerAssignments' => function ($q) {
            $q->active()->latest('assigned_at');
        }, 'managedStations'])->paginate($request->input('per_page', 15));

        // Transform to include station assignment info
        $managers->getCollection()->transform(function ($manager) {
            $activeAssignment = $manager->managerAssignments()->active()->latest('assigned_at')->first();
            
            return [
                'id' => $manager->id,
                'name' => $manager->name,
                'email' => $manager->email,
                'is_active' => $manager->is_active,
                'created_at' => $manager->created_at,
                'assignment_status' => [
                    'is_assigned' => !is_null($activeAssignment),
                    'station_id' => $activeAssignment?->station_id,
                    'station_name' => $activeAssignment?->station?->name,
                    'assigned_at' => $activeAssignment?->assigned_at,
                    'assigned_by' => $activeAssignment?->assignedBy?->name,
                ],
            ];
        });

        return response()->json([
            'message' => 'Active station managers retrieved successfully',
            'data' => $managers,
        ], 200);
    }

    /**
     * Create a new station manager
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'is_active' => 'boolean',
        ]);

        $manager = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role' => 'station',
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Station manager created successfully',
            'data' => [
                'manager' => $manager,
            ],
        ], 201);
    }

    /**
     * Get a specific station manager with assignment details
     */
    public function show(string $id): JsonResponse
    {
        $manager = User::where('id', $id)
            ->where('role', 'station')
            ->with(['managerAssignments' => function ($q) {
                $q->active()->latest('assigned_at');
            }, 'managedStations'])
            ->firstOrFail();

        $activeAssignment = $manager->managerAssignments()->active()->latest('assigned_at')->first();

        return response()->json([
            'message' => 'Station manager retrieved successfully',
            'data' => [
                'manager' => $manager,
                'assignment_status' => [
                    'is_assigned' => !is_null($activeAssignment),
                    'station_id' => $activeAssignment?->station_id,
                    'station_name' => $activeAssignment?->station?->name,
                    'assigned_at' => $activeAssignment?->assigned_at,
                    'assigned_by' => $activeAssignment?->assignedBy?->name,
                ],
            ],
        ], 200);
    }

    /**
     * Update a station manager's information
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $manager = User::where('id', $id)
            ->where('role', 'station')
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:8',
            'is_active' => 'sometimes|boolean',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        }

        $manager->update($validated);

        return response()->json([
            'message' => 'Station manager updated successfully',
            'data' => [
                'manager' => $manager,
            ],
        ], 200);
    }

    /**
     * Soft delete a station manager (mark as inactive)
     */
    public function destroy(string $id): JsonResponse
    {
        $manager = User::where('id', $id)
            ->where('role', 'station')
            ->firstOrFail();

        // Check if manager has active assignment
        $activeAssignment = StationManagerAssignment::where('manager_id', $id)
            ->active()
            ->first();

        if ($activeAssignment) {
            return response()->json([
                'message' => 'Cannot delete manager with active station assignment. Remove from station first.',
                'error' => 'has_active_assignment',
            ], 422);
        }

        // Soft delete by marking as inactive
        $manager->update(['is_active' => false]);

        return response()->json([
            'message' => 'Station manager deactivated successfully',
            'data' => [
                'manager' => $manager,
            ],
        ], 200);
    }
}
