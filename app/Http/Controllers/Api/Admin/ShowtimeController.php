<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ShowtimeResource;
use App\Models\Showtime;
use Illuminate\Http\Request;

class ShowtimeController extends Controller
{
     public function index()
    {
        return ShowtimeResource::collection(
            Showtime::with(['movie', 'auditorium'])->latest()->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'movie_id' => 'required|exists:movies,id',
            'auditorium_id' => 'required|exists:auditoriums,id',
            'start_time' => 'required|date_format:Y-m-d H:i:s',
            'price' => 'required|numeric|min:0',
        ]);

        $showtime = Showtime::create($data);
        return new ShowtimeResource($showtime->load(['movie', 'auditorium']));
    }

    public function show(Showtime $showtime)
    {
        return new ShowtimeResource($showtime->load(['movie', 'auditorium']));
    }

    public function update(Request $request, Showtime $showtime)
    {
        $showtime->update($request->only([
            'movie_id', 'auditorium_id', 'start_time', 'price'
        ]));

        return new ShowtimeResource($showtime->load(['movie', 'auditorium']));
    }

    public function destroy(Showtime $showtime)
    {
        $showtime->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
