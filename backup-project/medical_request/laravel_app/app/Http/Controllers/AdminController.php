<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MedicalRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function updateRequestStatus(Request $request)
    {
        $id = $request->id;
        $status = $request->status;
        $fileUrl = $request->fileUrl;

        $medicalRequest = MedicalRequest::where('request_id', $id)->firstOrFail();
        $medicalRequest->update([
            'status' => $status,
            'file_url' => $fileUrl
        ]);

        AuditLog::create([
            'username' => Auth::user()->username,
            'action' => 'UPDATE_STATUS',
            'details' => "อัปเดตสถานะ $id เป็น $status"
        ]);

        return response()->json(['success' => true]);
    }

    public function getAllUsers()
    {
        $users = User::all();
        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function resetUserPassword(Request $request)
    {
        $id = $request->id;
        $user = User::findOrFail($id);
        
        // กำหนดรหัสผ่านเริ่มต้นเป็น mch1234
        $user->update([
            'password' => Hash::make('mch1234')
        ]);

        AuditLog::create([
            'username' => Auth::user()->username,
            'action' => 'RESET_PASSWORD',
            'details' => "รีเซ็ตรหัสผ่านของผู้ใช้: " . $user->username
        ]);

        return response()->json(['success' => true, 'message' => 'รีเซ็ตรหัสผ่านเป็น mch1234 เรียบร้อยแล้ว']);
    }

    public function importUsers(Request $request)
    {
        $userData = $request->userData; // Array of [id, username, password, role, dept, full_name]

        if (!empty($userData)) {
            // Option 1: Clear and replace
            User::where('role', '<>', 'superadmin')->delete(); // Keep superadmin if exists

            foreach ($userData as $row) {
                User::create([
                    'username' => $row[1],
                    'password' => Hash::make($row[2]), // Hash imported passwords
                    'role' => $row[3],
                    'department' => $row[4],
                    'full_name' => $row[5],
                ]);
            }
        }

        AuditLog::create([
            'username' => Auth::user()->username,
            'action' => 'IMPORT_USERS',
            'details' => 'นำเข้าผู้ใช้จำนวน ' . count($userData) . ' รายการ'
        ]);

        return response()->json(['success' => true]);
    }
}
