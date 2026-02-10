<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Vite;

final class SecurityHeaders
{
    // Enumerate headers which you do not want in your application's responses.
    // Great starting point would be to go check out @Scott_Helme's:
    // https://securityheaders.com/
    private $unwantedHeaderList = [
        'X-Powered-By',
        'Server',
    ];

    public function handle($request, Closure $next)
    {
        Vite::useCspNonce();

        $this->removeUnwantedHeaders($this->unwantedHeaderList);

        $response = $next($request);

        if ($response instanceof \Illuminate\Http\Response) {
            return $response->withHeaders([
                'X-Frame-Options' => 'SAMEORIGIN',
                'X-XSS-Protection' => '1; mode=block',
                'X-Content-Type-Options' => 'nosniff',
                'Permissions-Policy' => 'geolocation=(self)',
                'Referrer-Policy' => 'no-referrer-when-downgrade',
                'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            ]);
        }

        return $response;
    }

    private function removeUnwantedHeaders($headerList)
    {
        foreach ($headerList as $header) {
            header_remove($header);
        }
    }
}
