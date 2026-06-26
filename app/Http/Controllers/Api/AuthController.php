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
        $token = $this->issueToken($user);

        return response()->json([
            'user' => $user,
            'access_token' => $token['plain_text_token'],
            'token_type' => 'Bearer',
            'expires_at' => $token['expires_at'],
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
        $token = $this->issueToken($user);

        return response()->json([
            'access_token' => $token['plain_text_token'],
            'token_type' => 'Bearer',
            'expires_at' => $token['expires_at'],
            'user' => $user,
        ]);
    }

    public function renew(Request $request)
    {
        abort_if($request->user()->role === 'student', 403, 'Student sessions cannot be renewed.');

        $currentToken = $request->user()->currentAccessToken();
        if ($currentToken && method_exists($currentToken, 'delete')) {
            $currentToken->delete();
        }
        $token = $this->issueToken($request->user());

        return response()->json([
            'access_token' => $token['plain_text_token'],
            'token_type' => 'Bearer',
            'expires_at' => $token['expires_at'],
            'user' => $request->user(),
        ]);
    }

    public function logout(Request $request)
    {
        $currentToken = $request->user()->currentAccessToken();
        if ($currentToken && method_exists($currentToken, 'delete')) {
            $currentToken->delete();
        }

        return response()->noContent();
    }

    public function me(Request $request)
    {
        return response()->json(['user' => $request->user()]);
    }

    private function issueToken(User $user): array
    {
        $expiresAt = now()->addMinutes($user->role === 'student' ? 65 : 60);
        $token = $user->createToken('api', ['*'], $expiresAt);

        return [
            'plain_text_token' => $token->plainTextToken,
            'expires_at' => $expiresAt->toISOString(),
        ];
    }
}
