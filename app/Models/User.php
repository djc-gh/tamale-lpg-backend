<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\HasRoles;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasApiTokens, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'station_id',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'password_hash',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the station that this user manages.
     */
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'station_id', 'id');
    }

    /**
     * Get the availability logs created by this user.
     */
    public function availabilityLogs(): HasMany
    {
        return $this->hasMany(StationAvailabilityLog::class, 'changed_by', 'id');
    }

    /**
     * Get the price history records updated by this user.
     */
    public function priceHistories(): HasMany
    {
        return $this->hasMany(PriceHistory::class, 'updated_by', 'id');
    }

    /**
     * Get manager assignments where this user is the manager
     */
    public function managerAssignments(): HasMany
    {
        return $this->hasMany(StationManagerAssignment::class, 'manager_id', 'id');
    }

    /**
     * Get assignments made by this user (admin assignments)
     */
    public function assignmentsMade(): HasMany
    {
        return $this->hasMany(StationManagerAssignment::class, 'assigned_by', 'id');
    }

    /**
     * Get stations managed by this user (active assignments only)
     */
    public function managedStations()
    {
        return $this->belongsToMany(
            Station::class,
            'station_manager_assignments',
            'manager_id',
            'station_id'
        )
        ->wherePivotNull('removed_at');
    }
}
