<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class AuthController extends BaseApiController
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        if (! Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 422);
        }

        $user = Auth::user() ?? User::query()->where('email', $credentials['email'])->first();

        if (! $user) {
            return response()->json(['message' => 'Unable to resolve authenticated user'], 500);
        }

        if (! $user->is_active) {
            Auth::logout();

            return response()->json([
                'message' => 'บัญชีนี้ยังไม่เปิดใช้งาน กรุณารอผู้ดูแลระบบอนุมัติ',
            ], 403);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $token = $user->createToken('drugstock-api')->plainTextToken;

        return $this->ok([
            'token' => $token,
            'user' => new UserResource($user),
        ], 'Logged in');
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'employee_code' => $data['employee_code'],
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'position_title' => $data['position_title'],
            'is_active' => false,
            'auth_provider' => 'local',
            'password' => $data['password'],
        ]);

        $defaultRole = SystemSetting::getValue('registration_default_role', config('medstock.registration_default_role', 'Viewer'));

        if ($defaultRole && Role::query()->where('name', $defaultRole)->exists()) {
            $user->syncRoles([$defaultRole]);
        }

        return $this->ok([
            'user' => new UserResource($user),
        ], 'ลงทะเบียนเรียบร้อย ระบบจะรอการอนุมัติจากผู้ดูแลระบบ', 201);
    }

    public function me(Request $request): JsonResponse
    {
        return $this->ok(new UserResource($request->user()));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return $this->ok(null, 'Logged out');
    }
}
