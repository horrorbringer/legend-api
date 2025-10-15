<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'duration_minutes',
        'rating',
        'genre',
        'release_date',
        'poster_url',
        'type',
    ];

    protected $casts = [
        'release_date' => 'date',
    ];
    // Relationships
    public function showtimes()
    {
        return $this->hasMany(Showtime::class);
    }

}
