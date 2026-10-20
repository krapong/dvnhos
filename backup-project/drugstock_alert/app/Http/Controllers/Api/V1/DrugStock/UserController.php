<?php

namespace App\Http\Controllers\Api\V1\DrugStock;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\DrugStock\UpdateUserStatusRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class UserController extends BaseApiController
{
    public function index(Request $request)
    {
        $users = User::query()
            ->with('roles')
            ->when($request->string('search')->toString(), function ($query, $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate((int) $request->integer('per_page', 15));

        return $this->ok([
            'users' => $users,
            'roles' => Role::query()
                ->where('guard_name', 'web')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->values(),
        ]);
    }

    public function updateStatus(UpdateUserStatusRequest $request, User $user)
    {
        $user->update(['is_active' => $request->boolean('is_active')]);

        return $this->ok($user->refresh()->load('roles'), 'อัปเดตสถานะผู้ใช้งานเรียบร้อย');
    }

    public function update(Request $request, User $user)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
        ];

        if ($request->filled('password')) {
            $rules['password'] = ['string', 'min:6'];
        }

        $data = $request->validate($rules);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return $this->ok($user->refresh()->load('roles'), 'อัปเดตข้อมูลผู้ใช้งานเรียบร้อย');
    }

    public function updateRoles(Request $request, User $user)
    {
        $data = $request->validate([
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string'],
        ]);

        $roleNames = Role::query()
            ->where('guard_name', 'web')
            ->pluck('name')
            ->all();

        $selected = array_values(array_intersect($data['roles'] ?? [], $roleNames));
        $user->syncRoles($selected);

        return $this->ok($user->refresh()->load('roles'), 'บันทึกบทบาทผู้ใช้งานเรียบร้อย');
    }

    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return response()->json(['message' => 'ไม่สามารถลบบัญชีของตนเองได้'], 403);
        }

        $user->delete();

        return $this->ok(null, 'ลบผู้ใช้งานเรียบร้อยแล้ว');
    }
}
