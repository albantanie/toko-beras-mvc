<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Inertia\Inertia;

class RoleBasedUIRestriction
{
    /**
     * Handle an incoming request.
     *
     * This middleware adds role-based UI restrictions to Inertia responses
     * to control what UI elements are shown based on user roles.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only apply to authenticated users
        if (!auth()->check()) {
            return $response;
        }

        $user = auth()->user();
        $userRole = $user->roles->first()->name ?? 'guest';

        // Define role-based UI permissions
        $uiPermissions = [
            'canCreateTransactions' => in_array($userRole, ['admin', 'kasir']),
            'canEditTransactions' => in_array($userRole, ['admin', 'kasir']),
            'canDeleteTransactions' => in_array($userRole, ['admin', 'kasir']),
            'canManageStock' => in_array($userRole, ['kasir']),
            'canViewFinancialReports' => in_array($userRole, ['admin', 'owner']),
            'canViewStockReports' => in_array($userRole, ['admin', 'owner', 'kasir']),
            'canApproveReports' => in_array($userRole, ['owner']),
            'canManageUsers' => in_array($userRole, ['admin', 'owner']),
            'canViewTransactionHistory' => in_array($userRole, ['admin', 'owner', 'kasir']),
            'canProcessPayments' => in_array($userRole, ['admin', 'kasir']),
            'canGenerateReceipts' => in_array($userRole, ['admin', 'kasir']),
            'isOwner' => $userRole === 'owner',
            'isKasir' => $userRole === 'kasir',
            'isAdmin' => $userRole === 'admin',
            'isKaryawan' => $userRole === 'karyawan',
        ];

        // Share UI permissions with all Inertia responses
        Inertia::share([
            'uiPermissions' => $uiPermissions,
            'userRole' => $userRole,
        ]);

        return $response;
    }
}
