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
         // Calculate total seats and available seats
        $totalSeats = $this->auditorium->seats()->count();
        $bookedSeats = $this->auditorium->seats()
            ->whereHas('bookings', fn($q) =>
                $q->where('showtime_id', $this->id)
                  ->whereIn('status', ['confirmed', 'pending']) // Only count active bookings
            )
            ->count();
        $availableSeats = $totalSeats - $bookedSeats;

        return [
            'id' => $this->id,
            'start_time' => $this->start_time->toDateTimeString(),
            'price' => (float) $this->price,
            'available_seats' => $availableSeats,
            'total_seats' => $totalSeats,

            // Movie information
            'movie' => [
                'id' => $this->movie->id,
                'title' => $this->movie->title,
                'poster' => $this->movie->poster_url
                    ? (str_starts_with($this->movie->poster_url, 'http')
                        ? $this->movie->poster_url
                        : url($this->movie->poster_url))
                    : url('images/default-poster.png'),
                'duration' => "{$this->movie->duration_minutes} mins",
                'genre' => $this->movie->genre,
                'rating' => $this->movie->rating,
                'format' => $this->movie->format, // '2D', '3D', 'IMAX', '4DX'
            ],

            // Auditorium and Cinema information
            'auditorium' => [
                'name' => $this->auditorium->name,
                'cinema' => [
                    'name' => $this->auditorium->cinema->name,
                    'location' => $this->auditorium->cinema->location ?? $this->auditorium->cinema->address,
                ],
            ],

            // seats
            'seats' => $this->auditorium->seats->map(function ($seat) {
                return [
                    'id' => $seat->id,
                    'seat_row' => $seat->seat_row,
                    'seat_number' => $seat->seat_number,
                    'is_booked' => $seat->bookings()
                        ->where('showtime_id', $this->id)
                        ->whereIn('status', ['pending', 'paid'])
                        ->exists(),
                ];
            }),
        ];
    }
}
