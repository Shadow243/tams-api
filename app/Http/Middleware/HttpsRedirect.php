<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class HttpsRedirect
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->secure() && app()->environment('production')) {
            return redirect()->secure($request->getRequestUri());
        }

        return $next($request);
    }
}
