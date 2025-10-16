<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Movie>
 */
class MovieFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $formats = ['2D', '3D', 'IMAX', '4DX'];
        $types = ['Standard', 'Premium', 'Dolby Atmos'];
        $genres = ['Action', 'Comedy', 'Drama', 'Horror', 'Sci-Fi', 'Romance', 'Thriller', 'Adventure'];
        $statuses = ['now_showing', 'upcoming', 'archived'];

        return [
            'title' => $this->faker->words(3, true),
            'duration_minutes' => $this->faker->numberBetween(90, 180),
            'rating' => $this->faker->randomFloat(1, 5.0, 9.5), // Rating as decimal (5.0 - 9.5)
            'genre' => $this->faker->randomElement($genres),
            'poster_url' => 'https://image.tmdb.org/t/p/w500/poster' . $this->faker->unique()->numberBetween(1, 1000) . '.jpg',
            'status' => $this->faker->randomElement($statuses),
            'format' => $this->faker->randomElement($formats),
            'type' => $this->faker->randomElement($types),
            'release_date' => $this->faker->dateTimeBetween('-1 year', '+6 months')->format('Y-m-d'),
            'description' => $this->faker->paragraph(3),
        ];
    }
}
