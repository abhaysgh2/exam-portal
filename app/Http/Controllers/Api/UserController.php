<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            User::query()
                ->when($request->query('role'), fn ($query, $role) => $query->where('role', $role))
                ->when($request->query('q'), fn ($query, $q) => $query->where('email', 'like', "%{$q}%")->orWhere('full_name', 'like', "%{$q}%"))
                ->paginate(),
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'full_name' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::in(['student', 'examiner', 'admin'])],
            'enrollment_no' => ['nullable', 'string', 'max:50', 'unique:users,enrollment_no'],
            'institute' => ['nullable', 'string', 'max:255'],
        ]);

        return response()->json(User::create($data), 201);
    }

    public function show(User $user)
    {
        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'full_name' => ['sometimes', 'string', 'max:255'],
            'role' => ['sometimes', Rule::in(['student', 'examiner', 'admin'])],
            'institute' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'is_suspended' => ['boolean'],
        ]);

        $user->update($data);

        return response()->json($user->fresh());
    }

    public function destroy(User $user)
    {
        $user->delete();

        return response()->noContent();
    }

    public function suspend(User $user)
    {
        $user->update(['is_suspended' => true]);

        return response()->json($user->fresh());
    }

    public function activate(User $user)
    {
        $user->update(['is_active' => true, 'is_suspended' => false]);

        return response()->json($user->fresh());
    }
}
