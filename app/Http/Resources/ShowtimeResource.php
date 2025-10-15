<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowtimeResource extends JsonResource
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
        'movie_title' => $this->movie->title ?? null,
        'cinema' => $this->auditorium->cinema->name ?? null,
        'auditorium' => $this->auditorium->name ?? null,
        'start_time' => $this->start_time,
        'price' => $this->price,
        'available_seats' => $this->auditorium->seats()
            ->whereDoesntHave('bookings', fn($q) =>
                $q->where('showtime_id', $this->id)
            )
            ->count(),
        ];
    }
}
