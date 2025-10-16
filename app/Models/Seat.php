<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
       use HasFactory;

    protected $fillable = [
        'auditorium_id',
        'seat_row',
        'seat_number',
    ];

    // Relationships
    public function auditorium()
    {
        return $this->belongsTo(Auditorium::class);
    }

    public function bookings()
    {
        return $this->belongsToMany(Booking::class, 'booking_seats')
                    ->withTimestamps();
    }
    public function bookingSeats()
    {
        return $this->hasMany(BookingSeat::class);
    }
     /**
     * Check if seat is booked for a specific showtime
     */
    public function isBookedForShowtime($showtimeId)
    {
        return $this->bookingSeats()
            ->whereHas('booking', function ($query) use ($showtimeId) {
                $query->where('showtime_id', $showtimeId)
                      ->where('status', '!=', 'cancelled');
            })
            ->exists();
    }
}
