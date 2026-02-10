<?php

declare(strict_types=1);

namespace App\Http\Integrations\Cloudflare;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

final class CloudflareConnector extends Connector
{
    use AcceptsJson;

    public function __construct(
        private readonly string $email,
        private readonly string $apiKey,
    ) {}

    /**
     * The Base URL of the API
     */
    public function resolveBaseUrl(): string
    {
        return config('services.cloudflare.api_url') . '/client/v4';
    }

    /**
     * Get default headers for testing purposes
     */
    public function getDefaultHeaders(): array
    {
        return $this->defaultHeaders();
    }

    /**
     * Default headers for every request
     */
    protected function defaultHeaders(): array
    {
        return [
            'X-Auth-Email' => $this->email,
            'X-Auth-Key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Default HTTP client options
     */
    protected function defaultConfig(): array
    {
        return [
            'timeout' => 30,
        ];
    }
}
