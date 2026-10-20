<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'authenticated' => $request->user() !== null,
            'user' => $request->user()?->load('role'),
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = $data['email'];
        $user = User::query()
            ->where('email', $login)
            ->orWhere('username', $login)
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง',
            ], 422);
        }

        if ($user->approval_status !== 'active') {
            return response()->json([
                'message' => $user->approval_status === 'pending'
                    ? 'บัญชีนี้รออนุมัติจากแอดมิน'
                    : 'บัญชีนี้ยังไม่สามารถเข้าใช้งานได้ กรุณาติดต่อแอดมิน',
            ], 403);
        }

        Auth::login($user, true);

        $request->session()->regenerate();

        return response()->json([
            'ok' => true,
            'user' => $request->user()->load('role'),
            'csrf_token' => csrf_token(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'ok' => true,
            'csrf_token' => csrf_token(),
        ]);
    }

    public function roles(): JsonResponse
    {
        return response()->json(Role::query()->orderBy('id')->get());
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'username' => ['required', 'string', 'max:100', 'alpha_dash:ascii', Rule::unique('users', 'username')],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $defaultRole = Role::query()->where('key', 'nurse')->first();
        $fullName = trim("{$data['first_name']} {$data['last_name']}");

        $user = User::create([
            'name' => $fullName,
            'email' => Str::lower($data['username']).'@pending.local',
            'username' => Str::lower($data['username']),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'position' => $data['position'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'role_id' => $defaultRole?->id,
            'approval_status' => 'pending',
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'ลงทะเบียนสำเร็จ กรุณารอแอดมินอนุมัติบัญชี',
            'user' => $user->load('role'),
        ], 201);
    }

    public function users(): JsonResponse
    {
        return response()->json(User::query()->with('role')->orderByRaw("approval_status = 'pending' desc")->orderBy('name')->get());
    }

    public function storeUser(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'username' => ['nullable', 'string', 'max:100', 'alpha_dash:ascii', Rule::unique('users', 'username')],
            'position' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:6'],
            'role_id' => ['required', 'exists:roles,id'],
            'approval_status' => ['nullable', Rule::in(['pending', 'active', 'rejected'])],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'username' => isset($data['username']) ? Str::lower($data['username']) : null,
            'position' => $data['position'] ?? null,
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'role_id' => $data['role_id'],
            'approval_status' => $data['approval_status'] ?? 'active',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ])->load('role');

        return response()->json(['ok' => true, 'user' => $user], 201);
    }

    public function updateUser(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'username' => ['nullable', 'string', 'max:100', 'alpha_dash:ascii', Rule::unique('users', 'username')->ignore($user->id)],
            'position' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:6'],
            'role_id' => ['required', 'exists:roles,id'],
            'approval_status' => ['nullable', Rule::in(['pending', 'active', 'rejected'])],
        ]);

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'username' => isset($data['username']) ? Str::lower($data['username']) : null,
            'position' => $data['position'] ?? null,
            'phone' => $data['phone'] ?? null,
            'role_id' => $data['role_id'],
            'approval_status' => $data['approval_status'] ?? $user->approval_status,
        ]);

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return response()->json(['ok' => true, 'user' => $user->fresh('role')]);
    }

    public function approveUser(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'role_id' => ['nullable', 'exists:roles,id'],
        ]);

        $user->fill([
            'approval_status' => 'active',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejected_at' => null,
            'approval_note' => null,
        ]);

        if (! empty($data['role_id'])) {
            $user->role_id = $data['role_id'];
        }

        $user->save();

        return response()->json(['ok' => true, 'user' => $user->fresh('role')]);
    }

    public function rejectUser(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'approval_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $user->fill([
            'approval_status' => 'rejected',
            'rejected_at' => now(),
            'approval_note' => $data['approval_note'] ?? null,
        ])->save();

        return response()->json(['ok' => true, 'user' => $user->fresh('role')]);
    }
}
