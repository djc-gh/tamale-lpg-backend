<?php

namespace App\Http\Controllers;

use App\Models\Station;
use App\Models\StationManagerAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StationManagerAssignmentController extends Controller
{
    /**
     * Assign a manager to a station
     */
    public function assignManager(Request $request, string $stationId): JsonResponse
    {
        $validated = $request->validate([
            'manager_id' => 'required|uuid|exists:users,id',
            'removal_reason' => 'nullable|string|max:255',
        ]);

        $station = Station::findOrFail($stationId);
        $manager = User::findOrFail($validated['manager_id']);

        // Validate manager has station role
        if ($manager->role !== 'station') {
            return response()->json([
                'message' => 'Only users with station role can be assigned as managers',
                'error' => 'invalid_role',
            ], 422);
        }

        // Check if manager is active
        if (!$manager->is_active) {
            return response()->json([
                'message' => 'Cannot assign an inactive user as manager',
                'error' => 'inactive_user',
            ], 422);
        }

        // Remove any existing active assignment for this station
        $existingAssignment = StationManagerAssignment::forStation($stationId)
            ->active()
            ->first();

        if ($existingAssignment) {
            $existingAssignment->update([
                'removed_at' => now(),
                'removal_reason' => 'Replaced by another manager',
            ]);
        }

        // Create new assignment
        $assignment = StationManagerAssignment::create([
            'manager_id' => $manager->id,
            'station_id' => $station->id,
            'assigned_by' => auth()->id(),
            'assigned_at' => now(),
        ]);

        return response()->json([
            'message' => 'Manager assigned successfully',
            'data' => [
                'assignment' => $assignment->load(['manager', 'station', 'assignedBy']),
            ],
        ], 201);
    }

    /**
     * Get the current manager for a station
     */
    public function getCurrentManager(string $stationId): JsonResponse
    {
        $station = Station::findOrFail($stationId);

        $assignment = StationManagerAssignment::forStation($stationId)
            ->active()
            ->latest('assigned_at')
            ->first();

        if (!$assignment) {
            return response()->json([
                'message' => 'No active manager assigned to this station',
                'data' => ['assignment' => null],
            ], 200);
        }

        return response()->json([
            'message' => 'Current manager retrieved successfully',
            'data' => [
                'assignment' => $assignment->load(['manager', 'assignedBy']),
            ],
        ], 200);
    }

    /**
     * Get the manager assignment history for a station
     */
    public function getManagerHistory(Request $request, string $stationId): JsonResponse
    {
        $station = Station::findOrFail($stationId);

        $query = StationManagerAssignment::forStation($stationId)
            ->with(['manager', 'assignedBy'])
            ->latest('assigned_at');

        // Optional filter by manager
        if ($request->has('manager_id')) {
            $query->where('manager_id', $request->manager_id);
        }

        $assignments = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'message' => 'Manager assignment history retrieved successfully',
            'data' => $assignments,
        ], 200);
    }

    /**
     * Remove a manager from a station
     */
    public function removeManager(Request $request, string $stationId): JsonResponse
    {
        $validated = $request->validate([
            'removal_reason' => 'nullable|string|max:255',
        ]);

        $station = Station::findOrFail($stationId);

        $assignment = StationManagerAssignment::forStation($stationId)
            ->active()
            ->latest('assigned_at')
            ->first();

        if (!$assignment) {
            return response()->json([
                'message' => 'No active manager assigned to this station',
                'error' => 'no_active_manager',
            ], 404);
        }

        $assignment->update([
            'removed_at' => now(),
            'removal_reason' => $validated['removal_reason'] ?? 'Manager removed',
        ]);

        return response()->json([
            'message' => 'Manager removed successfully',
            'data' => [
                'assignment' => $assignment,
            ],
        ], 200);
    }
}
