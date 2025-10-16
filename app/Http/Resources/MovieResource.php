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
            'genre' => $this->genre,
            'rating' => $this->rating,
            'duration' => "{$this->duration_minutes} mins",

            // ✅ NEW: Use status instead of type
            'status' => $this->status, // 'now_showing', 'upcoming', 'archived'

            // ✅ NEW: Add format field
            'format' => $this->format, // '2D', '3D', 'IMAX', '4DX'

            'release_date' => $this->release_date?->format('Y-m-d'),

            // ✅ Always use full poster URL
            'poster' => $this->poster_url
                ? (str_starts_with($this->poster_url, 'http')
                    ? $this->poster_url
                    : url($this->poster_url))
                : url('images/default-poster.png'),

            // ✅ Grouped showtimes by cinema
            'showtimes' => $this->whenLoaded('showtimes', function () {
                return $this->showtimes
                    ->groupBy(fn($s) => $s->auditorium->cinema->name ?? 'Unknown Cinema')
                    ->map(function ($group, $cinema) {
                        return [
                            'cinema' => $cinema,
                            'auditorium' => $group->first()->auditorium->name ?? 'Unknown Hall',
                            'times' => $group->pluck('start_time')->map(fn($t) => $t->toDateTimeString()),
                        ];
                    })
                    ->values();
            }),

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
