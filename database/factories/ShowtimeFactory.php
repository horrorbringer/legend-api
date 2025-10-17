<?php

namespace Database\Factories;

use App\Models\Auditorium;
use App\Models\Movie;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Showtime>
 */
class ShowtimeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'movie_id' => Movie::factory(),
            'auditorium_id' => Auditorium::factory(),
            'start_time' => $this->faker->dateTimeBetween('now', '+7 days'),
            'price' => $this->faker->randomFloat(2, 0.01, 0.20),
            // 'price' => $this->faker->randomFloat(2, 3.00, 8.00),
        ];
    }
}
