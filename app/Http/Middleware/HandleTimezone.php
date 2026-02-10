<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use DateTimeZone;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class HandleTimezone
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $timezone = null;

        // Priority 1: Check if user is authenticated and has timezone set
        if ($request->user() && $request->user()->timezone) {
            $timezone = $request->user()->timezone;
        }
        // Priority 2: Get timezone from X-User-Timezone header
        elseif ($request->header('X-User-Timezone')) {
            $timezone = $request->header('X-User-Timezone');
        }

        // Validate and set timezone
        if ($timezone && $this->isValidTimezone($timezone)) {
            config(['app.display_timezone' => $timezone]);
        } else {
            // Fallback to default timezone
            config(['app.display_timezone' => 'Africa/Lubumbashi']);
        }

        return $next($request);
    }

    /**
     * Validate if the timezone is valid.
     */
    private function isValidTimezone(string $timezone): bool
    {
        try {
            new DateTimeZone($timezone);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
