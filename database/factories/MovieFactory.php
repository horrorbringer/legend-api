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
        $types = ['2D', '3D', 'IMAX'];
        $genres = ['Action', 'Comedy', 'Drama', 'Horror', 'Sci-Fi', 'Romance'];

        return [
            'title' => $this->faker->catchPhrase(),
            'duration_minutes' => $this->faker->numberBetween(90, 180),
            'rating' => $this->faker->randomElement(['G', 'PG', 'PG13', 'R']),
            'genre' => $this->faker->randomElement($genres),
            'release_date' => $this->faker->date(),
            'type' => $this->faker->randomElement($types),
            'poster_url' => 'https://picsum.photos/200/300?random=' . $this->faker->unique()->numberBetween(1, 1000),
        ];
    }
}
