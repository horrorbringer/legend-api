<?php

namespace Database\Seeders;

use App\Models\Movie;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MovieSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $movies = [
            // Now Showing Movies
            [
                'title' => 'Dune: Part Two',
                'duration_minutes' => 166,
                'rating' => '8.9',
                'genre' => 'Sci-Fi',
                'poster_url' => 'https://image.tmdb.org/t/p/w500/8b8R8l88Qje9dn9OE8PY05Nxl1X.jpg',
                'status' => 'now_showing',
                'format' => 'IMAX',
                'release_date' => '2024-03-01',
                'description' => 'Paul Atreides unites with Chani and the Fremen while seeking revenge against the conspirators who destroyed his family.',
            ],
            [
                'title' => 'Kung Fu Panda 4',
                'duration_minutes' => 94,
                'rating' => '7.5',
                'genre' => 'Animation',
                'poster_url' => 'https://image.tmdb.org/t/p/w500/kDp1vUBnMpe8ak4rjgl3cLELqjU.jpg',
                'status' => 'now_showing',
                'format' => '3D',
                'release_date' => '2024-03-08',
                'description' => 'Po must train a new warrior when he\'s chosen to become the spiritual leader of the Valley of Peace.',
            ],
            [
                'title' => 'Godzilla x Kong: The New Empire',
                'duration_minutes' => 115,
                'rating' => '7.8',
                'genre' => 'Action',
                'poster_url' => 'https://image.tmdb.org/t/p/w500/z1p34vh7dEOnLDmyCrlUVLuoDzd.jpg',
                'status' => 'now_showing',
                'format' => '4DX',
                'release_date' => '2024-03-29',
                'description' => 'Two titans team up against a colossal undiscovered threat hidden within our world.',
            ],
            [
                'title' => 'Civil War',
                'duration_minutes' => 109,
                'rating' => '8.2',
                'genre' => 'Thriller',
                'poster_url' => 'https://image.tmdb.org/t/p/w500/sh7Rg8Er3tFcN9BpKIPOMvALgZd.jpg',
                'status' => 'now_showing',
                'format' => '2D',
                'release_date' => '2024-04-12',
                'description' => 'A journey across a dystopian future America, following a team of military-embedded journalists.',
            ],
            [
                'title' => 'The Fall Guy',
                'duration_minutes' => 126,
                'rating' => '7.9',
                'genre' => 'Action',
                'poster_url' => 'https://image.tmdb.org/t/p/w500/tSz1qsmSJon0rqjHBxXZmrotuse.jpg',
                'status' => 'now_showing',
                'format' => '2D',
                'release_date' => '2024-05-03',
                'description' => 'A stuntman working on his ex-girlfriend\'s blockbuster action film discovers a sinister plot.',
            ],

            // Upcoming Movies
            [
                'title' => 'Deadpool & Wolverine',
                'duration_minutes' => 128,
                'rating' => '9.0',
                'genre' => 'Action',
                'poster_url' => 'https://image.tmdb.org/t/p/w500/8cdWjvZQUExUUTzyp4t6EDMubfO.jpg',
                'status' => 'upcoming',
                'format' => 'IMAX',
                'release_date' => '2024-07-26',
                'description' => 'Deadpool teams up with Wolverine in an adventure that will change the MCU forever.',
            ],
            [
                'title' => 'Inside Out 2',
                'duration_minutes' => 96,
                'rating' => '8.5',
                'genre' => 'Animation',
                'poster_url' => 'https://image.tmdb.org/t/p/w500/vpnVM9B6NMmQpWeZvzLvDESb2QY.jpg',
                'status' => 'upcoming',
                'format' => '3D',
                'release_date' => '2024-06-14',
                'description' => 'A sequel that introduces new emotions as Riley enters her teenage years.',
            ],
            [
                'title' => 'A Quiet Place: Day One',
                'duration_minutes' => 99,
                'rating' => '8.3',
                'genre' => 'Horror',
                'poster_url' => 'https://image.tmdb.org/t/p/w500/hU42CRk14JuPEdqZG3AWmagiPAP.jpg',
                'status' => 'upcoming',
                'format' => '2D',
                'release_date' => '2024-06-28',
                'description' => 'Experience the day the world went quiet in this prequel to A Quiet Place.',
            ],
            [
                'title' => 'Bad Boys: Ride or Die',
                'duration_minutes' => 115,
                'rating' => '8.0',
                'genre' => 'Action',
                'poster_url' => 'https://image.tmdb.org/t/p/w500/nP6RliHjxsz4irTKsxe8FRhKZYl.jpg',
                'status' => 'upcoming',
                'format' => 'IMAX',
                'release_date' => '2024-06-07',
                'description' => 'Miami detectives Mike Lowrey and Marcus Burnett reunite for another explosive adventure.',
            ],
        ];

        foreach ($movies as $movie) {
            Movie::create($movie);
        }
    }
}
