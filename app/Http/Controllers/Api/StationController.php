<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StationResource;
use App\Models\Station;
use App\Services\StationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StationController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(private StationService $stationService)
    {
    }

    /**
     * Get all stations with optional filtering and pagination.
     *
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'per_page' => 'integer|min:1|max:100',
            'assigned' => 'nullable|in:true,false,1,0',
            'available' => 'boolean',
            'latitude' => 'numeric',
            'longitude' => 'numeric',
            'radius' => 'integer|min:1|max:100',
            'sort_by' => 'in:name,price_per_kg,distance',
        ]);

        // Convert string booleans to actual booleans
        if (isset($validated['assigned'])) {
            $validated['assigned'] = filter_var($validated['assigned'], FILTER_VALIDATE_BOOLEAN);
        }

        $stations = $this->stationService->getAllStations($validated);

        return StationResource::collection($stations);
    }

    /**
     * Get a single station by ID.
     *
     * @param  string  $id
     * @return StationResource
     */
    public function show(string $id): StationResource
    {
        $station = $this->stationService->getStationById($id);

        return new StationResource($station);
    }

    /**
     * Create a new station.
     *
     * @return StationResource
     */
    public function store(Request $request): StationResource
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|unique:stations',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'operating_hours' => 'required|string|max:100',
            'price_per_kg' => 'numeric|min:0|nullable',
            'image' => 'url|nullable',
            'is_available' => 'boolean',
        ]);

        $station = $this->stationService->createStation($validated);

        return new StationResource($station);
    }

    /**
     * Update a station.
     *
     * @param  string  $id
     * @return StationResource
     */
    public function update(Request $request, string $id): StationResource
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'address' => 'string',
            'phone' => 'string|max:20',
            'email' => 'email|unique:stations,email,'.$id.',id',
            'latitude' => 'numeric',
            'longitude' => 'numeric',
            'operating_hours' => 'string|max:100',
            'price_per_kg' => 'numeric|min:0|nullable',
            'image' => 'url|nullable',
            'is_available' => 'boolean',
        ]);

        $station = $this->stationService->updateStation($id, $validated);

        return new StationResource($station);
    }

    /**
     * Delete a station.
     *
     * @param  string  $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        $this->stationService->deleteStation($id);

        return response()->json([
            'message' => 'Station deleted successfully',
        ], 204);
    }

    /**
     * Get stations within a specified radius, sorted by availability then distance.
     * 
     * API Response includes:
     * - Available stations first (closest to furthest)
     * - Unavailable stations after (closest to furthest)
     * - Each station includes distance_km
     *
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function nearbyStations(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'integer|min:1|max:100',
            'available_only' => 'boolean',
        ]);

        $stations = $this->stationService->getNearbyStations(
            $validated['latitude'],
            $validated['longitude'],
            $validated['radius'] ?? 5,
            $validated['available_only'] ?? false
        );

        // If no stations found
        if ($stations->isEmpty()) {
            return response()->json([
                'message' => 'No LPG stations found in the specified radius',
                'data' => [],
                'available_count' => 0,
                'unavailable_count' => 0,
                'radius_km' => $validated['radius'] ?? 5,
            ], 200);
        }

        // Separate available and unavailable for response metadata
        $availableCount = $stations->where('is_available', true)->count();
        $unavailableCount = $stations->where('is_available', false)->count();

        // If no available stations found
        if ($availableCount === 0 && !($validated['available_only'] ?? false)) {
            return response()->json([
                'message' => 'No available LPG station near you',
                'data' => StationResource::collection($stations),
                'available_count' => 0,
                'unavailable_count' => $unavailableCount,
                'radius_km' => $validated['radius'] ?? 5,
                'note' => 'All nearby stations are currently unavailable',
            ], 200);
        }

        return response()->json([
            'message' => 'Nearby stations retrieved successfully',
            'data' => StationResource::collection($stations),
            'available_count' => $availableCount,
            'unavailable_count' => $unavailableCount,
            'radius_km' => $validated['radius'] ?? 5,
        ], 200);
    }

    /**
     * Get price history for a station.
     *
     * @param  string  $stationId
     * @return AnonymousResourceCollection
     */
    public function priceHistory(string $stationId): AnonymousResourceCollection
    {
        $history = $this->stationService->getPriceHistory($stationId);

        return \App\Http\Resources\PriceHistoryResource::collection($history);
    }

    /**
     * Update station availability.
     * - Admins can update any station
     * - Station managers can only update their own assigned station
     *
     * @param  string  $id
     * @return StationResource|JsonResponse
     */
    public function updateAvailability(Request $request, string $id)
    {
        $user = $request->user();

        // Station managers can only update their own station
        if ($user->isStationManager() && ! $user->canManageStation($id)) {
            return response()->json([
                'message' => 'Unauthorized - You can only manage your assigned station',
            ], 403);
        }

        $validated = $request->validate([
            'is_available' => 'required|boolean',
        ]);

        $station = $this->stationService->updateAvailability($id, $validated['is_available']);

        return new StationResource($station);
    }

    /**
     * Toggle station active status.
     * - Admins can toggle any station
     * - Station managers can only toggle their own assigned station
     * Used when a station permanently closes or reopens.
     *
     * @param  string  $id Station ID
     * @return StationResource|JsonResponse
     */
    public function toggleStatus(Request $request, string $id)
    {
        $user = $request->user();

        // Station managers can only toggle their own station
        if ($user->isStationManager() && ! $user->canManageStation($id)) {
            return response()->json([
                'message' => 'Unauthorized - You can only manage your assigned station',
            ], 403);
        }

        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $station = Station::findOrFail($id);
        $station->update(['is_active' => $validated['is_active']]);

        $status = $validated['is_active'] ? 'activated' : 'deactivated';

        return response()->json([
            'message' => "Station {$status} successfully",
            'data' => new StationResource($station),
        ]);
    }
}
