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
        'status',
        'format',
        'type',
        'description',
    ];

    protected $casts = [
        'release_date' => 'date',
        'duration_minutes' => 'integer',
    ];

    // Relationships
    public function showtimes()
    {
        return $this->hasMany(Showtime::class);
    }
      // ✅ Scopes for easy querying
    public function scopeNowShowing($query)
    {
        return $query->where('status', 'now_showing');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'upcoming');
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    public function scopeByFormat($query, string $format)
    {
        return $query->where('format', $format);
    }

}
