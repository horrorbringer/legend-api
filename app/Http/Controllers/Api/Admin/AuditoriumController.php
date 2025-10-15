<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditoriumResource;
use App\Models\Auditorium;
use Illuminate\Http\Request;

class AuditoriumController extends Controller
{
     public function index()
    {
        return AuditoriumResource::collection(Auditorium::with('cinema')->latest()->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cinema_id' => 'required|exists:cinemas,id',
            'name' => 'required|string|max:50',
            'seat_capacity' => 'required|integer|min:1',
        ]);

        $auditorium = Auditorium::create($data);
        return new AuditoriumResource($auditorium);
    }

    public function show(Auditorium $auditorium)
    {
        return new AuditoriumResource($auditorium->load('cinema'));
    }

    public function update(Request $request, Auditorium $auditorium)
    {
        $auditorium->update($request->only(['name', 'seat_capacity']));
        return new AuditoriumResource($auditorium);
    }

    public function destroy(Auditorium $auditorium)
    {
        $auditorium->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
