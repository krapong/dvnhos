<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $appends = ['settings', 'role', 'displayName', 'username', 'status', 'locationId', 'active'];

    public function getSettingsAttribute()
    {
        return Setting::normalizedMap();
    }

    public function getRoleAttribute()
    {
        $roleName = \Illuminate\Support\Facades\DB::table('roles')
            ->join('model_has_roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_id', $this->id)
            ->value('roles.name');
            
        return match($roleName) {
            'superadmin' => 'super_admin',
            'admin' => 'dept_admin',
            'pharmacist' => 'pharmacist',
            'nurse' => 'viewer',
            default => 'viewer'
        };
    }

    public function getDisplayNameAttribute()
    {
        return $this->name;
    }

    public function getUsernameAttribute()
    {
        return $this->email;
    }

    public function getStatusAttribute()
    {
        return $this->is_active ? 'active' : 'inactive';
    }

    public function getActiveAttribute()
    {
        return (bool) $this->is_active;
    }

    public function getLocationIdAttribute()
    {
        return $this->department_id;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
