<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class DisableEloquentStrictMode
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Disable strict mode for translation routes
        Model::shouldBeStrict(false);

        return $next($request);
    }
}
