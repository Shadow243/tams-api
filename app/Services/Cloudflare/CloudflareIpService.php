<?php

declare(strict_types=1);

namespace App\Services\Cloudflare;

use App\Http\Integrations\Cloudflare\CloudflareConnector;
use App\Http\Integrations\Cloudflare\Requests\GetCloudflareIpsRequest;
use Exception;
use Illuminate\Support\Facades\Log;

final class CloudflareIpService
{
    public function __construct(
        private readonly CloudflareConnector $connector
    ) {}

    /**
     * Get Cloudflare IP ranges from the API
     */
    public function getCloudflareIps(): array
    {
        try {
            $request = new GetCloudflareIpsRequest();
            $response = $this->connector->send($request);

            if (! $response->ok()) {
                Log::warning('Failed to fetch Cloudflare IPs', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

            $data = $response->json();

            if (! isset($data['result']['ipv4_cidrs']) || ! isset($data['result']['ipv6_cidrs'])) {
                Log::warning('Invalid response format from Cloudflare API', [
                    'data' => $data,
                ]);

                return [];
            }

            return [
                'ipv4' => $data['result']['ipv4_cidrs'],
                'ipv6' => $data['result']['ipv6_cidrs'],
            ];
        } catch (Exception $e) {
            Log::error('Exception while fetching Cloudflare IPs', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [];
        }
    }

    /**
     * Get all Cloudflare IP ranges as a flat array for trustProxies
     * This method handles all exceptions internally and returns an empty array on failure
     */
    public function getTrustProxiesIps(): array
    {
        try {
            $ips = $this->getCloudflareIps();

            if (empty($ips)) {
                return [];
            }

            return array_merge($ips['ipv4'] ?? [], $ips['ipv6'] ?? []);
        } catch (Exception $e) {
            Log::error('Failed to fetch Cloudflare IPs for trustProxies', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [];
        }
    }
}
