<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DeveloperSignature
{
    // Система разработана Коваленко А.С. | pizzakov@gmail.com | 2026
    private const DEVELOPER = 'Kovalenko A.S.';
    private const CONTACT = 'pizzakov@gmail.com';
    private const YEAR = 2026;

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('X-Developed-By', self::DEVELOPER);
        $response->headers->set('X-Developer-Contact', self::CONTACT);
        $response->headers->set('X-System', 'VMS-KELET-' . self::YEAR);

        return $response;
    }
}
