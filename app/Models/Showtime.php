<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Showtime extends Model
{
     use HasFactory;

    protected $fillable = [
        'movie_id',
        'auditorium_id',
        'start_time',
        'price',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'price' => 'decimal:2',
    ];

    // Relationships
    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }

    public function auditorium()
    {
        return $this->belongsTo(Auditorium::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

     /**
     * Get available seats for this showtime
     */
    public function getAvailableSeatsAttribute()
    {
        $allSeats = $this->auditorium->seats;
        $bookedSeatIds = $this->bookings()
            ->where('status', '!=', 'cancelled')
            ->with('bookingSeats')
            ->get()
            ->pluck('bookingSeats')
            ->flatten()
            ->pluck('seat_id')
            ->toArray();

        return $allSeats->map(function ($seat) use ($bookedSeatIds) {
            $seat->is_booked = in_array($seat->id, $bookedSeatIds);
            return $seat;
        });
    }
}
