<?php

declare(strict_types=1);

namespace App\Http\Integrations\Cloudflare\Requests;

use Illuminate\Support\Facades\Cache;
use Saloon\CachePlugin\Contracts\Cacheable;
use Saloon\CachePlugin\Contracts\Driver;
use Saloon\CachePlugin\Drivers\LaravelCacheDriver;
use Saloon\CachePlugin\Traits\HasCaching;
use Saloon\Enums\Method;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;

final class GetCloudflareIpsRequest extends Request implements Cacheable
{
    use HasCaching;

    /**
     * The HTTP method of the request
     */
    protected Method $method = Method::GET;

    /**
     * The endpoint for the request
     */
    public function resolveEndpoint(): string
    {
        return '/ips';
    }

    /**
     * Resolve the cache driver for this request
     */
    public function resolveCacheDriver(): Driver
    {
        return new LaravelCacheDriver(Cache::store(config('cache.default')));
    }

    /**
     * Cache expiry in seconds (7 days)
     */
    public function cacheExpiryInSeconds(): int
    {
        return 604800; // 7 days
    }

    /**
     * Get the cacheable methods
     */
    protected function getCacheableMethods(): array
    {
        return [Method::GET, Method::OPTIONS];
    }

    /**
     * Customize the cache key for Cloudflare IPs
     */
    protected function cacheKey(PendingRequest $pendingRequest): ?string
    {
        return 'cloudflare:ips:v4';
    }
}
