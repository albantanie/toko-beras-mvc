<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Check if user has any of the required roles
        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        // If user doesn't have required role, redirect based on their role
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard')->with('error', 'Akses ditolak');
        } elseif ($user->isOwner()) {
            return redirect()->route('owner.dashboard')->with('error', 'Akses ditolak');
        } elseif ($user->isKaryawan()) {
            return redirect()->route('karyawan.dashboard')->with('error', 'Akses ditolak');
        } elseif ($user->isKasir()) {
            return redirect()->route('kasir.dashboard')->with('error', 'Akses ditolak');
        } else {
            return redirect()->route('dashboard')->with('error', 'Akses ditolak');
        }
    }
}
