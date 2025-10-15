<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\ShowtimeResource;
use App\Models\Showtime;
use Illuminate\Http\Request;

class ShowtimeController extends Controller
{
     public function index(Request $request)
    {
        $query = Showtime::with(['movie', 'auditorium.cinema']);

        // Filter by date (e.g., ?date=2025-10-07)
        if ($request->has('date')) {
            $query->whereDate('start_time', $request->date);
        }

        // Filter by cinema (e.g., ?cinema_id=1)
        if ($request->has('cinema_id')) {
            $query->whereHas('auditorium', fn($q) =>
                $q->where('cinema_id', $request->cinema_id)
            );
        }

        return ShowtimeResource::collection($query->orderBy('start_time')->get());
    }

    public function show($id)
    {
        $showtime = Showtime::with(['movie', 'auditorium.cinema'])->findOrFail($id);
        return new ShowtimeResource($showtime);
    }
}
