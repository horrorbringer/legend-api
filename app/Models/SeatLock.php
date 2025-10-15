<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeatLock extends Model
{
    use HasFactory;

    protected $fillable = ['seat_id', 'showtime_id', 'locked_until'];

    protected $casts = [
        'locked_until' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->locked_until->isPast();
    }

    public function seat()
    {
        return $this->belongsTo(Seat::class);
    }

    public function showtime()
    {
        return $this->belongsTo(Showtime::class);
    }
}
