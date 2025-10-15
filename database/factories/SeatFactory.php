<?php

namespace Database\Factories;

use App\Models\Auditorium;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Seat>
 */
class SeatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'auditorium_id' => Auditorium::factory(),
            'seat_row' => $this->faker->randomElement(range('A', 'J')),
            'seat_number' => $this->faker->numberBetween(1, 20),
        ];
    }
}
