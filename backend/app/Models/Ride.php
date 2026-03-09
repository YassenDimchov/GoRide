<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Ride extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'driver_id',
        'start_lat',
        'start_lng',
        'end_lat',
        'end_lng',
        'start_address',
        'end_address',
        'passenger_count',
        'trip_distance_m',
        'trip_duration_s',
        'estimated_fare',
        'fare',
        'status',
        'accepted_at',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'estimated_fare' => 'decimal:2',
        'accepted_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user that was on the ride.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the driver of the ride.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the payment of the ride.
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Get the review of the ride.
     */
    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }
}
