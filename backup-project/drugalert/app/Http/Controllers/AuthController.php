<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        // Support login with either email or employee_code
        $user = User::where(function($query) use ($request) {
                        $query->where('email', $request->username)
                              ->orWhere('employee_code', $request->username);
                    })
                    ->first();

        if (! $user) {
            return response()->json(['error' => 'ไม่พบผู้ใช้ในระบบ'], 401);
        }

        if (! $user->is_active) {
            return response()->json(['error' => 'บัญชีของคุณกำลังรอการอนุมัติจากผู้ดูแลระบบ', 'status' => 'pending_approval'], 403);
        }

        if (! $user->password || ! Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'], 401);
        }

        // Update last login
        $user->last_login_at = now();
        $user->save();

        // Login using session
        auth()->login($user);

        return response()->json([
            'message' => 'เข้าสู่ระบบสำเร็จ',
            'user' => $user
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return response()->json(['message' => 'ออกจากระบบเรียบร้อยแล้ว']);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:6',
        ], [
            'email.unique' => 'อีเมลหรือชื่อผู้ใช้นี้มีในระบบแล้ว',
        ]);

        $newUser = new User();
        // Skip setting id if it's auto-incrementing in legacy DB, or set it if string
        if (gettype($newUser->getKey()) === 'string') {
             $newUser->id = 'user_' . \Illuminate\Support\Str::random(8);
        }
        $newUser->email = $request->email;
        $newUser->name = $request->name;
        $newUser->is_active = 0; // Wait for approval
        $newUser->password = Hash::make($request->password);
        
        // No role or department is assigned here. Admin will do it during approval.

        $newUser->save();

        // Log the registration event
        $this->recordAudit($newUser->id, 'user.register', 'user', (string) $newUser->id, [
            'status' => 'pending_approval'
        ]);

        return response()->json([
            'message' => 'ลงทะเบียนสำเร็จ กรุณารอการอนุมัติสิทธิ์จากผู้ดูแลระบบ',
        ], 201);
    }
}
