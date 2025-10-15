<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
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
            'status' => $this->status,
            'total_price' => $this->total_price,
            'created_at' => $this->created_at->toDateTimeString(),

            'customer' => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
            ],

            'showtime' => [
                'id' => $this->showtime->id,
                'movie_title' => $this->showtime->movie->title ?? 'Unknown',
                'start_time' => $this->showtime->start_time,
                'cinema_hall' => $this->showtime->cinemaHall->name ?? 'N/A',
            ],

            'seats' => $this->seats->map(fn($seat) => [
                'id' => $seat->id,
                'seat_number' => $seat->seat_number,
                'row' => $seat->row,
            ]),
        ];
    }
}
