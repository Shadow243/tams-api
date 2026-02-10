<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Tams;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

final class LocaleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale($this->getRequestLocale($request));

        return $next($request);
    }

    private function getRequestLocale(Request $request): ?string
    {
        $supported = Tams::getAvailableLocaleCodes();
        $appLocale = config('app.locale');

        if ($request->hasHeader('X-Locale') && $supported->contains($request->header('X-Locale'))) {
            return $request->header('X-Locale');
        }

        if ($supported->contains($appLocale)) {
            return $appLocale;
        }

        $browserLocale = $request->server('HTTP_ACCEPT_LANGUAGE', '');
        if (! empty($browserLocale)) {

            $first = explode(',', $browserLocale)[0];

            $code = mb_substr($first, 0, 2);
            $code = mb_strtolower($code);

            if ($supported->contains($code)) {
                return $code;
            }
        }

        return $request->getPreferredLanguage($supported->all()) ?? $supported->first();
    }
}
