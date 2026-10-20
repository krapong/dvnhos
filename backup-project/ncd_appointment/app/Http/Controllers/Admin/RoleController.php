<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all();
        
        return Inertia::render('Admin/Roles', [
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'array',
        ]);

        $role = Role::create(['name' => $request->name]);
        
        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()->back()->with('success', 'สร้างกลุ่มสิทธิ์สำเร็จ');
    }

    public function update(Request $request, Role $role)
    {
        // Don't allow renaming the super admin role
        if ($role->name === 'admin' && $request->name !== 'admin') {
            return redirect()->back()->with('error', 'ไม่อนุญาตให้เปลี่ยนชื่อสิทธิ์ admin');
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'array',
        ]);

        $role->update(['name' => $request->name]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()->back()->with('success', 'อัปเดตกลุ่มสิทธิ์สำเร็จ');
    }

    public function destroy(Role $role)
    {
        if ($role->name === 'admin') {
            return redirect()->back()->with('error', 'ไม่อนุญาตให้ลบสิทธิ์ admin พื้นฐานของระบบ');
        }

        $role->delete();

        return redirect()->back()->with('success', 'ลบกลุ่มสิทธิ์สำเร็จ');
    }
}
