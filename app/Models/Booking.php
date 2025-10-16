<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
     use HasFactory;

    protected $fillable = [
        'user_id',
        'showtime_id',
        'booking_time',
        'total_price',
        'status',
        'payment_method',
        'payment_reference',
        'paid_at',
    ];

    protected $casts = [
        'booking_time' => 'datetime',
        'paid_at' => 'datetime',
        'total_price' => 'decimal:2',
    ];

    public function showtime()
    {
        return $this->belongsTo(Showtime::class);
    }

     public function bookingSeats()
    {
        return $this->hasMany(BookingSeat::class);
    }

    public function seats()
    {
        return $this->belongsToMany(Seat::class, 'booking_seats');
    }

    public function user()
    {
        return $this->belongsTo(User::class );
    }

}
