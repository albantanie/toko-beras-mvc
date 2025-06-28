<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware untuk memfilter harga beli dari response untuk kasir
 * 
 * Middleware ini memastikan bahwa kasir tidak dapat melihat harga_beli
 * dalam response JSON atau data Inertia untuk melindungi informasi bisnis sensitif
 */
class FilterPurchasePriceForKasir
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Check if user is kasir
        $user = Auth::user();
        if (!$user || !$user->roles()->where('name', 'kasir')->exists()) {
            return $response;
        }
        
        // Filter response data for kasir
        if ($response instanceof \Inertia\Response) {
            $this->filterInertiaResponse($response);
        } elseif ($response->headers->get('content-type') === 'application/json') {
            $this->filterJsonResponse($response);
        }
        
        return $response;
    }
    
    /**
     * Filter Inertia response data
     */
    private function filterInertiaResponse($response): void
    {
        $data = $response->toResponse(request())->getData();
        
        if (isset($data->props)) {
            $this->filterPurchasePrices($data->props);
        }
    }
    
    /**
     * Filter JSON response data
     */
    private function filterJsonResponse($response): void
    {
        $content = $response->getContent();
        $data = json_decode($content, true);
        
        if ($data) {
            $this->filterPurchasePrices($data);
            $response->setContent(json_encode($data));
        }
    }
    
    /**
     * Recursively filter purchase prices from data
     */
    private function filterPurchasePrices(&$data): void
    {
        if (is_array($data) || is_object($data)) {
            foreach ($data as $key => &$value) {
                if ($key === 'harga_beli') {
                    unset($data->$key);
                } elseif (is_array($value) || is_object($value)) {
                    $this->filterPurchasePrices($value);
                }
            }
        }
    }
}
