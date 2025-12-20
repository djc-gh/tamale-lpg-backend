<?php

namespace App\Services;

use App\Models\Station;
use App\Models\StationAvailabilityLog;
use App\Models\PriceHistory;
use Illuminate\Pagination\LengthAwarePaginator;

class StationService
{
    /**
     * Get all stations with optional filtering and pagination.
     *
     * @param  array  $filters
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getAllStations(array $filters = []): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = Station::query();

        // Filter by assignment status
        if (isset($filters['assigned'])) {
            if ($filters['assigned']) {
                // Stations with active manager assignments
                $query->whereHas('managerAssignments', function ($q) {
                    $q->active();
                });
            } else {
                // Stations without active manager assignments
                $query->whereDoesntHave('managerAssignments', function ($q) {
                    $q->active();
                });
            }
        }

        // Filter by availability
        if (isset($filters['available']) && $filters['available']) {
            $query->where('is_available', true);
        }

        // Filter by location radius if provided
        if (isset($filters['latitude'], $filters['longitude'])) {
            $radius = $filters['radius'] ?? 5;
            $query->withinRadius(
                $filters['latitude'],
                $filters['longitude'],
                $radius
            );
        }

        // Sort results
        if (isset($filters['sort_by'])) {
            match ($filters['sort_by']) {
                'price_per_kg' => $query->orderBy('price_per_kg'),
                'name' => $query->orderBy('name'),
                'distance' => null, // Distance sorting is handled by withinRadius scope
                default => $query->latest('updated_at')
            };
        } else {
            $query->latest('updated_at');
        }

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    /**
     * Get a single station by ID.
     *
     * @param  string  $id
     * @return Station
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getStationById(string $id): Station
    {
        return Station::findOrFail($id);
    }

    /**
     * Create a new station.
     *
     * @param  array  $data
     * @return Station
     */
    public function createStation(array $data): Station
    {
        return Station::create($data);
    }

    /**
     * Update a station.
     *
     * @param  string  $id
     * @param  array  $data
     * @return Station
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function updateStation(string $id, array $data): Station
    {
        $station = $this->getStationById($id);
        $station->update($data);

        return $station;
    }

    /**
     * Delete a station.
     *
     * @param  string  $id
     * @return bool
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function deleteStation(string $id): bool
    {
        return $this->getStationById($id)->delete();
    }

    /**
     * Get stations within a specified radius.
     *
     * @param  float  $latitude
     * @param  float  $longitude
     * @param  int  $radius Distance in kilometers
     * @param  bool  $availableOnly
     * @return mixed
     */
    /**
     * Get nearby stations with distance calculation and availability sorting
     * 
     * Stations are sorted by:
     * 1. Availability (available first)
     * 2. Distance (closest first)
     *
     * @param float $latitude User's latitude
     * @param float $longitude User's longitude
     * @param int $radius Search radius in kilometers
     * @param bool $availableOnly Return only available stations
     * @return mixed
     */
    public function getNearbyStations(float $latitude, float $longitude, int $radius = 5, bool $availableOnly = false): mixed
    {
        $query = Station::withinRadius($latitude, $longitude, $radius);

        // Get all stations within radius
        $stations = $query->get();

        if ($stations->isEmpty()) {
            return collect(); // Return empty collection if no stations found
        }

        // Separate available and unavailable stations
        $availableStations = $stations->filter(fn($s) => $s->is_available)->values();
        $unavailableStations = $stations->filter(fn($s) => !$s->is_available)->values();

        // If only available stations are requested
        if ($availableOnly) {
            return $availableStations;
        }

        // Combine: available first (sorted by distance), then unavailable (sorted by distance)
        // This ensures the closest available station is first, but we still show unavailable ones
        return $availableStations->concat($unavailableStations)->values();
    }

    /**
     * Get price history for a station.
     *
     * @param  string  $stationId
     * @return mixed
     */
    public function getPriceHistory(string $stationId): mixed
    {
        return PriceHistory::where('station_id', $stationId)
            ->latest('effective_from')
            ->paginate(20);
    }

    /**
     * Update station availability.
     *
     * @param  string  $stationId
     * @param  bool  $isAvailable
     * @return Station
     */
    public function updateAvailability(string $stationId, bool $isAvailable): Station
    {
        $station = $this->getStationById($stationId);

        // Log the change
        StationAvailabilityLog::create([
            'station_id' => $stationId,
            'is_available' => $isAvailable,
            'changed_by' => auth()->id(),
            'changed_at' => now(),
        ]);

        return $station->update(['is_available' => $isAvailable]) ? $station->fresh() : $station;
    }

    /**
     * Update price for a station.
     *
     * @param  string  $stationId
     * @param  float  $price
     * @return Station
     */
    public function updatePrice(string $stationId, float $price): Station
    {
        $station = $this->getStationById($stationId);

        // Create price history record
        PriceHistory::create([
            'station_id' => $stationId,
            'price_per_kg' => $price,
            'effective_from' => now(),
            'updated_by' => auth()->id(),
        ]);

        return $station->update(['price_per_kg' => $price]) ? $station->fresh() : $station;
    }
}
