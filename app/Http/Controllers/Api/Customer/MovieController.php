<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\MovieResource;
use App\Models\Movie;
use Illuminate\Http\Request;

class MovieController extends Controller
{
    public function index(Request $request)
    {
        $query = Movie::query();

        // Filter by status: showing or upcoming
        if ($request->has('status')) {
            if ($request->status === 'showing') {
                $query->whereDate('release_date', '<=', now());
            } elseif ($request->status === 'upcoming') {
                $query->whereDate('release_date', '>', now());
            }
        }

        return MovieResource::collection($query->orderBy('release_date', 'desc')->get());
    }

    public function show(Movie $movie)
    {
        $movie->load('showtimes.auditorium.cinema');
        return new MovieResource($movie);
    }
}
