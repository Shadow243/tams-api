<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class NormalizeCallbackQuery
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $uri = $request->getRequestUri();

        if (mb_substr_count($uri, '?') > 1) {
            // Split only on the *first* "?"
            [$path, $queryString] = explode('?', $uri, 2);

            // Replace any further "?" in the query string with "&"
            $queryString = str_replace('?', '&', $queryString);

            // Parse query params
            parse_str($queryString, $params);

            // Replace Laravel’s query bag
            $request->query->replace($params);

            // Rebuild normalized REQUEST_URI so signature validation works
            $normalizedUri = $path . '?' . http_build_query($params);

            $request->server->set('REQUEST_URI', $normalizedUri);
        }

        return $next($request);
    }
}
