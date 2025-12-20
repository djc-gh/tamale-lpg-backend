<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    protected $fillable = [
        'ip_address',
        'url',
        'method',
        'user_agent',
        'device_type',
        'browser',
        'os',
        'user_id',
        'response_code',
        'response_time_ms',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that made this visit
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
