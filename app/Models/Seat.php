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
}
