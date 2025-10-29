<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureNonAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $u = $request->user();
        $isAdmin = (bool)($u->role === 'admin');
        if ($isAdmin) {
            return redirect()->route('admin.attendance.index')
                ->with('status', '管理者は一般ユーザー画面を利用できません。');
        }
        return $next($request);
    }
}
