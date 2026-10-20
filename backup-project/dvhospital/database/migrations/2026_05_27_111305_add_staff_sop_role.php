<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $role = Role::firstOrCreate(['name' => 'staff-sop', 'guard_name' => 'web']);
        
        $permission = Permission::firstOrCreate(['name' => 'manage_sops', 'guard_name' => 'web']);
        
        if (!$role->hasPermissionTo('manage_sops')) {
            $role->givePermissionTo('manage_sops');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Role::where('name', 'staff-sop')->delete();
    }
};
