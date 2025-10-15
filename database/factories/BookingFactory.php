<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Showtime;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'showtime_id' => Showtime::factory(),
            'booking_time' => now(),
            'total_price' => 0, // Will be updated after assigning seats
            'status' => $this->faker->randomElement(['pending', 'paid', 'cancelled']),
        ];
    }
}
