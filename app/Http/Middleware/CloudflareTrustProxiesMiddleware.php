<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Cloudflare\CloudflareIpService;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class CloudflareTrustProxiesMiddleware
{
    public function __construct(
        private readonly CloudflareIpService $cloudflareService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Configure trusted proxies based on environment
        if (in_array(config('app.env'), ['staging', 'production'])) {
            // Production/Staging configuration with Cloudflare IPs
            $trustedProxies = ['192.168.0.0/20'];

            try {
                $cloudflareIps = $this->cloudflareService->getTrustProxiesIps();

                if (! empty($cloudflareIps)) {
                    $trustedProxies = array_merge($trustedProxies, $cloudflareIps);
                }
            } catch (Exception $e) {
                // Log the error but don't fail the request
                Log::error('Failed to add Cloudflare IPs to trusted proxies', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            $request->setTrustedProxies(
                $trustedProxies,
                Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_HOST |
                Request::HEADER_X_FORWARDED_PORT |
                Request::HEADER_X_FORWARDED_PROTO |
                Request::HEADER_X_FORWARDED_AWS_ELB |
                Request::HEADER_FORWARDED
            );
        } else {
            // Local/Development Configuration
            $request->setTrustedProxies(
                ['127.0.0.1', '::1'],
                Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_PROTO
            );
        }

        return $next($request);
    }
}
