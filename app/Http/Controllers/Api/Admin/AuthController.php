<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $r)
    {
        $r->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
        ]);

        // Force admin role for this endpoint
        $user = User::create([
            'name' => $r->name,
            'email' => $r->email,
            'password' => Hash::make($r->password),
            'role' => 'admin',
        ]);

        $token = $user->createToken('admin-token')->plainTextToken;

        return response()->json([
            'message' => 'Admin registered successfully',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $r)
    {
        $r->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $r->email)->first();

        if (! $user || ! Hash::check($r->password, $user->password) || $user->role !== 'admin') {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials or not authorized.'],
            ]);
        }

           if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized. Admin access only.'
            ], 403);
        }

        $user->tokens()->delete();
        
        $token = $user->createToken('admin-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $r)
    {
         $r->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function user(Request $r)
    {
        return response()->json($r->user());
    }
}
