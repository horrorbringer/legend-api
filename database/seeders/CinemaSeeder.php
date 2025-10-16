<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{
    Cinema,
    Auditorium,
    Movie,
    Showtime,
    Seat,
    Booking,
    User
};

class CinemaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
          // Create Cinemas with Auditoriums, Seats, Movies, and Showtimes
        $cinemas = Cinema::factory(3)->create();

        foreach ($cinemas as $cinema) {
            $auditoriums = Auditorium::factory(3)->create(['cinema_id' => $cinema->id]);

            foreach ($auditoriums as $auditorium) {
                // Create seats
                $rows = range('A', 'E');
                foreach ($rows as $row) {
                    for ($i = 1; $i <= 10; $i++) {
                        Seat::create([
                            'auditorium_id' => $auditorium->id,
                            'seat_row' => $row,
                            'seat_number' => $i,
                        ]);
                    }
                }

                // Create movies and showtimes
                $movies = Movie::all();

                foreach ($movies as $movie) {
                    Showtime::factory(3)->create([
                        'movie_id' => $movie->id,
                        'auditorium_id' => $auditorium->id,
                    ]);
                }
            }
        }

        // Create customers and bookings
        // $customers = User::factory(10)->create();

        // $showtimes = \App\Models\Showtime::all();

        // foreach ($customers as $customer) {
        //     $showtime = $showtimes->random();
        //     $booking = Booking::factory()->create([
        //         'user_id' => $customer->id,
        //         'showtime_id' => $showtime->id,
        //         'status' => 'paid',
        //     ]);

        //     // Assign random seats (2–4)
        //     $availableSeats = $showtime->auditorium->seats()->inRandomOrder()->take(rand(2, 4))->get();

        //     $total = 0;
        //     foreach ($availableSeats as $seat) {
        //         $booking->seats()->attach($seat->id);
        //         $total += $showtime->price;
        //     }

        //     $booking->update(['total_price' => $total]);
        // }
    }
}
