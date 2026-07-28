<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    /**
     * Ensure the authenticated user has the required role, otherwise bounce
     * them back to their own dashboard.
     */
    public function handle(Request $request, Closure $next, string $role): mixed
    {
        if (! auth()->check()) {
            return redirect('/login');
        }

        $userRole = auth()->user()->role;

        if ($userRole->value !== $role) {
            return match ($userRole) {
                UserRole::Admin => redirect('/admin/dashboard'),
                UserRole::Siswa => redirect('/student/dashboard'),
            };
        }

        return $next($request);
    }
}
