<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Role;

class RedirectBasedOnRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply this middleware to the home route (/)
        if ($request->is('/')) {
            // If user is authenticated
            if (auth()->check()) {
                $user = auth()->user();

                // Check user's role and redirect accordingly
                if ($user->hasRole(Role::ADMIN)) {
                    return redirect()->route('admin.dashboard');
                } elseif ($user->hasRole(Role::KASIR)) {
                    return redirect()->route('kasir.dashboard');
                } elseif ($user->hasRole(Role::KARYAWAN)) {
                    return redirect()->route('karyawan.dashboard');
                } elseif ($user->hasRole(Role::OWNER)) {
                    return redirect()->route('owner.dashboard');
                }
                // If user has role 'pelanggan' or no specific role, continue to home page
            }
            // If user is not authenticated, continue to home page (public access)
        }

        return $next($request);
    }
}
