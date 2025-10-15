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
}
