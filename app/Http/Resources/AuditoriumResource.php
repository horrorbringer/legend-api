<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditoriumResource extends JsonResource
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
            'cinema_id' => $this->cinema_id,
            'cinema_name' => $this->cinema->name ?? null,
            'name' => $this->name,
            'seat_capacity' => $this->seat_capacity,
        ];
    }
}
