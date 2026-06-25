<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'full_name' => ['required', 'string', 'max:255'],
            'role' => ['nullable', Rule::in(['student'])],
            'enrollment_no' => ['nullable', 'string', 'max:50', 'unique:users,enrollment_no'],
            'institute' => ['nullable', 'string', 'max:255'],
        ]);

        $data['role'] = 'student';

        $user = User::create($data);

        return response()->json([
            'user' => $user,
            'access_token' => $user->createToken('api')->plainTextToken,
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password) || ! $user->is_active || $user->is_suspended) {
            throw ValidationException::withMessages(['email' => 'The provided credentials are incorrect.']);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return response()->json([
            'access_token' => $user->createToken('api')->plainTextToken,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->noContent();
    }

    public function me(Request $request)
    {
        return response()->json(['user' => $request->user()]);
    }
}
