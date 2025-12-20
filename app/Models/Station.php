<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Station extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stations';

    /**
     * The primary key associated with the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'is_available',
        'is_active',
        'price_per_kg',
        'operating_hours',
        'image',
        'latitude',
        'longitude',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_available' => 'boolean',
        'is_active' => 'boolean',
        'price_per_kg' => 'decimal:2',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the managers for this station.
     */
    public function managers(): HasMany
    {
        return $this->hasMany(User::class, 'station_id', 'id');
    }

    /**
     * Get the location history for this station.
     */
    public function locationHistory(): HasMany
    {
        return $this->hasMany(StationLocationHistory::class, 'station_id', 'id');
    }

    /**
     * Get the availability log for this station.
     */
    public function availabilityLog(): HasMany
    {
        return $this->hasMany(StationAvailabilityLog::class, 'station_id', 'id');
    }

    /**
     * Get the price history for this station.
     */
    public function priceHistory(): HasMany
    {
        return $this->hasMany(PriceHistory::class, 'station_id', 'id');
    }

    /**
     * Get all manager assignments for this station (including history)
     */
    public function managerAssignments(): HasMany
    {
        return $this->hasMany(StationManagerAssignment::class, 'station_id', 'id');
    }

    /**
     * Get the current active manager for this station
     */
    public function currentManager()
    {
        return $this->belongsToMany(
            User::class,
            'station_manager_assignments',
            'station_id',
            'manager_id'
        )
        ->wherePivotNull('removed_at')
        ->latest('station_manager_assignments.assigned_at')
        ->limit(1);
    }

    /**
     * Get all managers that have managed this station (current and past)
     */
    public function assignedManagers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'station_manager_assignments',
            'station_id',
            'manager_id'
        )
        ->orderByDesc('station_manager_assignments.assigned_at');
    }

    /**
     * Scope to get only available stations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    /**
     * Scope to find stations within a given radius using Haversine formula.
     * Only returns active stations.
     *
     * @param mixed $query
     * @param float $latitude
     * @param float $longitude
     * @param int $radius Distance in kilometers
     */
    public function scopeWithinRadius($query, $latitude, $longitude, $radius = 5)
    {
        return $query
            ->where('is_active', true) // Only active stations
            ->selectRaw(
                'stations.*,
                (6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                )) AS distance_km',
                [$latitude, $longitude, $latitude]
            )
            ->whereRaw(
                '(6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                )) <= ?',
                [$latitude, $longitude, $latitude, $radius]
            )
            ->orderBy('distance_km');
    }
}
