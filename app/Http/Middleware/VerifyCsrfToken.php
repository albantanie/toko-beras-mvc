<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'laporan/generate-sales',
        'laporan/generate-stock',
        'laporan/generate-*',
        'laporan/*',
        'test/csrf-bypass',
        'csrf-token', // Allow CSRF token refresh
        'test-session', // Test route
        'login', // Disable CSRF for login
        'logout', // Disable CSRF for logout
        'register', // Disable CSRF for register
    ];
}
