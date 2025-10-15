<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CinemaResource;
use App\Models\Cinema;
use Illuminate\Http\Request;

class CinemaController extends Controller
{
    public function index()
    {
        return CinemaResource::collection(Cinema::latest()->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
        ]);

        $cinema = Cinema::create($data);
        return new CinemaResource($cinema);
    }

    public function show(Cinema $cinema)
    {
        return new CinemaResource($cinema);
    }

    public function update(Request $request, Cinema $cinema)
    {
        $cinema->update($request->only(['name', 'address', 'city', 'phone']));
        return new CinemaResource($cinema);
    }

    public function destroy(Cinema $cinema)
    {
        $cinema->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
