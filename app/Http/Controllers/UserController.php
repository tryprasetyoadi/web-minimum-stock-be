<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $includeDeleted = filter_var($request->query('include_deleted', 'false'), FILTER_VALIDATE_BOOLEAN);
        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min($perPage, 100)); // clamp per_page 1..100

        $query = User::query();
        if (! $includeDeleted) {
            $query->where('is_deleted', false);
        }

        $keyword = $request->query('keyword');
        if ($keyword !== null && $keyword !== '') {
            $t = $keyword;
            $query->where(function ($q) use ($t) {
                $q->where('name', 'like', "%$t%")
                    ->orWhere('email', 'like', "%$t%")
                    ->orWhere('phone', 'like', "%$t%")
                    ->orWhere('address', 'like', "%$t%");
            });
        }

        $paginator = $query
            ->orderBy('id', 'desc')
            ->paginate($perPage, [
                'id', 'first_name', 'last_name', 'email', 'address', 'phone', 'profile_picture', 'role_id', 'is_deleted'
            ]);

        $data = array_map(function ($user) {
            $url = $user->profile_picture ? Storage::url($user->profile_picture) : null;
            return [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'address' => $user->address,
                'phone' => $user->phone,
                'role_id' => $user->role_id,
                'is_deleted' => $user->is_deleted,
                'profile_picture_url' => $url,
            ];
        }, $paginator->items());

        return response()->json([
            'message' => 'Users fetched successfully',
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(User $user)
    {
        return response()->json([
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'address' => $user->address,
            'phone' => $user->phone,
            'role_id' => $user->role_id,
            'is_deleted' => $user->is_deleted,
            'profile_picture_url' => $user->profile_picture ? Storage::url($user->profile_picture) : null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:25'],
            'role_id' => ['required', 'integer', 'exists:roles,role_id'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'profile_picture' => ['nullable', 'image', 'max:2048'],
        ]);

        $profilePath = null;
        if ($request->hasFile('profile_picture')) {
            $profilePath = $request->file('profile_picture')->store('profile_pictures', 'public');
        }

        $user = User::create([
            'name' => $validated['first_name'].' '.$validated['last_name'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'address' => $validated['address'],
            'phone' => $validated['phone'],
            'role_id' => $validated['role_id'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_deleted' => false,
            'profile_picture' => $profilePath,
        ]);

        return response()->json([
            'message' => 'User created',
            'id' => $user->id,
        ], 201);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'address' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'max:25'],
            'role_id' => ['sometimes', 'required', 'integer', 'exists:roles,role_id'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['sometimes', 'required', 'string', 'min:8'],
            'profile_picture' => ['sometimes', 'nullable', 'image', 'max:2048'],
        ]);

        $payload = [];
        foreach (['first_name','last_name','address','phone','role_id','email'] as $field) {
            if (array_key_exists($field, $validated)) {
                $payload[$field] = $validated[$field];
            }
        }

        if (isset($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        if ($request->hasFile('profile_picture')) {
            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }
            $payload['profile_picture'] = $request->file('profile_picture')->store('profile_pictures', 'public');
        }

        if (isset($validated['first_name']) || isset($validated['last_name'])) {
            $payload['name'] = ($validated['first_name'] ?? $user->first_name) . ' ' . ($validated['last_name'] ?? $user->last_name);
        }

        $user->update($payload);

        return response()->json(['message' => 'User updated']);
    }

    public function destroy(User $user)
    {
        $user->is_deleted = true;
        $user->save();
        // Revoke tokens on soft-delete
        $user->tokens()->delete();

        return response()->json(['message' => 'User soft-deleted']);
    }

    public function restore(User $user)
    {
        $user->is_deleted = false;
        $user->save();

        return response()->json(['message' => 'User restored']);
    }
}
