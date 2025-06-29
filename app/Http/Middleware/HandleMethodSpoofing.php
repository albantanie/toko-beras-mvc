<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleMethodSpoofing
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the request has a _method parameter (Laravel method spoofing)
        if ($request->has('_method')) {
            $method = strtoupper($request->input('_method'));
            
            // Validate the method
            if (in_array($method, ['PUT', 'PATCH', 'DELETE'])) {
                // Set the method on the request
                $request->setMethod($method);
                
                // Remove the _method parameter from the request
                $request->request->remove('_method');
            }
        }
        
        // Also check for X-HTTP-Method-Override header
        if ($request->hasHeader('X-HTTP-Method-Override')) {
            $method = strtoupper($request->header('X-HTTP-Method-Override'));
            
            if (in_array($method, ['PUT', 'PATCH', 'DELETE'])) {
                $request->setMethod($method);
            }
        }

        return $next($request);
    }
} 