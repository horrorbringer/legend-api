<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\MovieResource;
use App\Models\Movie;
use Illuminate\Http\Request;

class MovieController extends Controller
{
    public function index()
    {
        return MovieResource::collection(Movie::latest()->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:150',
            'duration_minutes' => 'nullable|integer|min:1',
            'rating' => 'nullable|string|max:10',
            'genre' => 'nullable|string|max:50',
            'release_date' => 'nullable|date',
        ]);

        $movie = Movie::create($data);
        return new MovieResource($movie);
    }

    public function show(Movie $movie)
    {
        return new MovieResource($movie);
    }

    public function update(Request $request, Movie $movie)
    {
        $movie->update($request->only([
            'title', 'duration_minutes', 'rating', 'genre', 'release_date'
        ]));

        return new MovieResource($movie);
    }

    public function destroy(Movie $movie)
    {
        $movie->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
