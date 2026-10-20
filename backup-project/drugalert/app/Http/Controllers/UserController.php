<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!in_array($user->role, ['super_admin'])) {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        return response()->json($this->activeQuery(User::class)->get());
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!in_array($user->role, ['super_admin'])) {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        $request->validate([
            'username' => 'required|string|unique:users',
            'display_name' => 'required|string',
            'role' => 'required|string',
        ]);

        $newUser = new User();
        // Skip setting id if it's auto-incrementing in legacy DB, or set it if string
        if (gettype($newUser->getKey()) === 'string') {
             $newUser->id = 'user_' . Str::random(8);
        }
        $newUser->email = $request->username; // username -> email
        $newUser->name = $request->display_name; // display_name -> name
        $newUser->department_id = $request->location_id ?? null; // location_id -> department_id
        $newUser->is_active = ($request->status ?? 'active') === 'active' ? 1 : 0; // status -> is_active
        
        // Handling role is tricky since it uses spatie/laravel-permission or manual roles table
        // But for now we just don't try to write it to a non-existent 'role' column.
        // We will assign roles via DB facade or Eloquent relationships later if needed.
        
        if ($request->password) {
            $newUser->password = \Illuminate\Support\Facades\Hash::make($request->password);
        }

        $newUser->save();
        
        // Handle roles
        if ($request->role) {
            $roleId = \Illuminate\Support\Facades\DB::table('roles')
                ->where('name', match($request->role) {
                    'super_admin' => 'superadmin',
                    'dept_admin' => 'admin',
                    'pharmacist' => 'pharmacist',
                    'viewer' => 'nurse',
                    default => 'nurse'
                })->value('id');
            if ($roleId) {
                \Illuminate\Support\Facades\DB::table('model_has_roles')->insert([
                    'role_id' => $roleId,
                    'model_type' => User::class,
                    'model_id' => $newUser->id
                ]);
            }
        }

        $this->recordAudit($user->id, 'user.create', 'user', (string) $newUser->id, [
            'after' => $this->redactSensitiveData($newUser->getAttributes()),
        ]);

        return response()->json($newUser);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        if (!in_array($user->role, ['super_admin'])) {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        $targetUser = User::find($id);
        if (!$targetUser) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $before = $this->redactSensitiveData($targetUser->getAttributes());
        if ($request->has('username')) $targetUser->email = $request->username;
        if ($request->has('display_name')) $targetUser->name = $request->display_name;
        if ($request->has('status') || $request->has('active')) {
            $status = $request->status ?? ($request->active ? 'active' : 'inactive');
            $targetUser->is_active = $status === 'active' ? 1 : 0;
        }
        if ($request->exists('location_id')) $targetUser->department_id = $request->location_id;

        if ($request->has('password') && !empty($request->password)) {
            $targetUser->password = \Illuminate\Support\Facades\Hash::make($request->password);
        }

        $targetUser->save();
        
        // Handle roles
        if ($request->has('role')) {
            $roleId = \Illuminate\Support\Facades\DB::table('roles')
                ->where('name', match($request->role) {
                    'super_admin' => 'superadmin',
                    'dept_admin' => 'admin',
                    'pharmacist' => 'pharmacist',
                    'viewer' => 'nurse',
                    default => 'nurse'
                })->value('id');
            if ($roleId) {
                \Illuminate\Support\Facades\DB::table('model_has_roles')->where('model_id', $targetUser->id)->delete();
                \Illuminate\Support\Facades\DB::table('model_has_roles')->insert([
                    'role_id' => $roleId,
                    'model_type' => User::class,
                    'model_id' => $targetUser->id
                ]);
            }
        }

        $this->recordAudit($user->id, 'user.update', 'user', (string) $targetUser->id, [
            'before' => $before,
            'after' => $this->redactSensitiveData($targetUser->getAttributes()),
        ]);

        return response()->json($targetUser);
    }

    public function approve(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'super_admin') {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        $targetUser = User::find($id);
        if (!$targetUser) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $before = $this->redactSensitiveData($targetUser->getAttributes());
        
        if ($request->has('status') || $request->has('active')) {
            $status = $request->status ?? ($request->active ? 'active' : 'inactive');
            $targetUser->is_active = $status === 'active' ? 1 : 0;
        }
        if ($request->has('locationId')) $targetUser->department_id = $request->locationId;
        if ($request->has('location_id')) $targetUser->department_id = $request->location_id;

        $targetUser->save();
        
        // Handle roles
        if ($request->has('role')) {
            $roleId = \Illuminate\Support\Facades\DB::table('roles')
                ->where('name', match($request->role) {
                    'super_admin' => 'superadmin',
                    'dept_admin' => 'admin',
                    'pharmacist' => 'pharmacist',
                    'viewer' => 'nurse',
                    default => 'nurse'
                })->value('id');
            if ($roleId) {
                \Illuminate\Support\Facades\DB::table('model_has_roles')->where('model_id', $targetUser->id)->delete();
                \Illuminate\Support\Facades\DB::table('model_has_roles')->insert([
                    'role_id' => $roleId,
                    'model_type' => User::class,
                    'model_id' => $targetUser->id
                ]);
            }
        }

        $this->recordAudit($user->id, 'user.approve', 'user', (string) $targetUser->id, [
            'before' => $before,
            'after' => $this->redactSensitiveData($targetUser->getAttributes()),
        ]);

        return response()->json($targetUser);
    }

    public function resetPassword(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'super_admin') {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        $targetUser = User::find($id);
        if (!$targetUser) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $request->validate([
            'password' => 'required|string',
        ]);

        $targetUser->password = \Illuminate\Support\Facades\Hash::make($request->password);
        $targetUser->save();

        $this->recordAudit($user->id, 'user.reset_password', 'user', $targetUser->id, [
            'username' => $targetUser->username,
        ]);

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if (!in_array($user->role, ['super_admin'])) {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        $targetUser = User::find($id);
        if ($targetUser && $targetUser->id !== $user->id) {
            $this->recordAudit($user->id, 'user.delete', 'user', $targetUser->id, [
                'before' => $this->redactSensitiveData($targetUser->getAttributes()),
            ]);
            if (! $this->softDeleteIfSupported($targetUser)) {
                return response()->json([
                    'error' => 'Soft delete is not supported by the current users table schema.'
                ], 409);
            }
        }

        return response()->json(['success' => true]);
    }
}
