<?php

namespace App\Http\Controllers\Api\V1\DrugStock;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends BaseApiController
{
    public function index()
    {
        $roles = Role::query()
            ->where('guard_name', 'web')
            ->with('permissions')
            ->withCount('permissions')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions_count' => $role->permissions_count,
                'permissions' => $role->permissions->pluck('name')->values(),
            ])
            ->values();

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get()
            ->map(fn (Permission $permission) => [
                'id' => $permission->id,
                'name' => $permission->name,
            ])
            ->values();

        return $this->ok([
            'roles' => $roles,
            'permissions' => $permissions,
            'default_role' => SystemSetting::getValue('registration_default_role', config('medstock.registration_default_role', 'Viewer')),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        abort_unless($role->guard_name === 'web', 404);

        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        $permissionNames = Permission::query()
            ->where('guard_name', 'web')
            ->pluck('name')
            ->all();

        $allowed = array_values(array_intersect($data['permissions'] ?? [], $permissionNames));
        $role->syncPermissions($allowed);

        return $this->ok([
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions_count' => $role->permissions()->count(),
                'permissions' => $role->permissions()->pluck('name')->values(),
            ],
        ], 'บันทึกสิทธิ์บทบาทเรียบร้อย');
    }
}
