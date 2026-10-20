<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ActivityLog;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (!auth()->user()->is_active) {
            auth()->logout();
            return redirect()->route('login')
                ->with('error', 'บัญชีของคุณถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ');
        }

        // ต้องมี role ใด role หนึ่ง
        $allowedRoles = ['super_admin', 'staff_pr', 'staff_procurement', 'staff_ita', 'staff_events', 'staff-sop'];
        if (!auth()->user()->hasAnyRole($allowedRoles)) {
            abort(403, 'คุณไม่มีสิทธิ์เข้าถึงระบบ Backend');
        }

        return $next($request);
    }
}
