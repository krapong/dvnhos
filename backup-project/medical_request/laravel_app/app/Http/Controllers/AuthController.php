<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        if (Auth::attempt(['username' => $request->username, 'password' => $request->password])) {
            $request->session()->regenerate();
            $user = Auth::user();
            
            AuditLog::create([
                'username' => $user->username,
                'action' => 'LOGIN',
                'details' => 'เข้าสู่ระบบสำเร็จ'
            ]);
            
            return response()->json([
                'success' => true,
                'user' => [
                    'username' => $user->username,
                    'role' => $user->role,
                    'dept' => $user->department,
                    'fullName' => $user->full_name
                ]
            ]);
        }

        return response()->json(['success' => false, 'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง']);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|unique:users',
            'password' => 'required|min:4',
            'fullName' => 'required',
            'dept' => 'required'
        ]);

        $user = User::create([
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
            'full_name' => $data['fullName'],
            'department' => $data['dept'],
            'role' => 'user'
        ]);

        AuditLog::create([
            'username' => $user->username,
            'action' => 'REGISTER',
            'details' => 'ลงทะเบียนผู้ใช้ใหม่'
        ]);

        return response()->json(['success' => true]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json(['success' => true]);
    }

    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'currentPassword' => 'required',
            'newPassword' => 'required|min:4'
        ]);

        $user = Auth::user();

        if (!Hash::check($data['currentPassword'], $user->password)) {
            return response()->json(['success' => false, 'message' => 'รหัสผ่านปัจจุบันไม่ถูกต้อง']);
        }

        $user->password = Hash::make($data['newPassword']);
        $user->save();

        AuditLog::create([
            'username' => $user->username,
            'action' => 'CHANGE_PASSWORD',
            'details' => 'เปลี่ยนรหัสผ่านสำเร็จ'
        ]);

        return response()->json(['success' => true]);
    }
}
