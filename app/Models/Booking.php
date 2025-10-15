<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
     use HasFactory;

    protected $fillable = [
        'customer_id',
        'showtime_id',
        'booking_time',
        'total_price',
        'status',
    ];

    protected $casts = [
        'booking_time' => 'datetime',
    ];

    public function showtime()
    {
        return $this->belongsTo(Showtime::class);
    }

    public function seats()
    {
         return $this->belongsToMany(Seat::class, 'booking_seats')
                ->using(\App\Models\BookingSeat::class);
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

}
