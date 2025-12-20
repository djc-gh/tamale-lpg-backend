<?php

namespace App\Traits;

trait HasRoles
{
    /**
     * Check if user has admin role.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user has station manager role.
     */
    public function isStationManager(): bool
    {
        return $this->role === 'station';
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Check if user can manage a specific station.
     */
    public function canManageStation(string $stationId): bool
    {
        // Admins can manage all stations
        if ($this->isAdmin()) {
            return true;
        }

        // Station managers can only manage their assigned station
        if ($this->isStationManager()) {
            // Check if station_id is directly assigned (legacy)
            if ($this->station_id === $stationId) {
                return true;
            }

            // Check if manager has an active assignment to this station
            return $this->managerAssignments()
                ->where('station_id', $stationId)
                ->whereNull('removed_at')
                ->exists();
        }

        return false;
    }

    /**
     * Get user's role as a readable string.
     */
    public function getRoleDisplay(): string
    {
        return match ($this->role) {
            'admin' => 'Administrator',
            'station' => 'Station Manager',
            default => 'User',
        };
    }
}
