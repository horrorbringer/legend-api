<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovieResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'duration_minutes' => $this->duration_minutes,
            'rating' => $this->rating,
            'genre' => $this->genre,
            'type' => $this->type, // e.g., '2D', '3D', etc.
            'release_date' => $this->release_date?->format('Y-m-d'),

            // Make sure poster URL works correctly (absolute URL)
            'poster_url' => $this->poster_url
                ? (str_starts_with($this->poster_url, 'http')
                    ? $this->poster_url
                    : url($this->poster_url))
                : url('images/default-poster.png'),

            // Include relationships if loaded (for detailed movie page)
            'showtimes' => $this->whenLoaded('showtimes', function () {
                return $this->showtimes->map(function ($showtime) {
                    return [
                        'id' => $showtime->id,
                        'start_time' => $showtime->start_time,
                        'end_time' => $showtime->end_time,
                        'auditorium' => $showtime->auditorium?->name,
                        'cinema' => $showtime->auditorium?->cinema?->name,
                    ];
                });
            }),

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
