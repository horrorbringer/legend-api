<?php

namespace Database\Factories;

use App\Models\Cinema;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Auditorium>
 */
class AuditoriumFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cinema_id' => Cinema::factory(),
            'name' => 'Hall ' . $this->faker->randomDigitNotNull(),
            'seat_capacity' => $this->faker->numberBetween(50, 200),
        ];
    }
}
