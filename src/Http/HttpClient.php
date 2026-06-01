<?php

declare(strict_types=1);

namespace TexHub\InstagramGraphApi\Http;

use TexHub\InstagramGraphApi\Config;
use TexHub\InstagramGraphApi\Exceptions\ApiException;
use TexHub\InstagramGraphApi\Exceptions\InstagramException;

/**
 * Graph API HTTP wrapper: builds versioned URLs, injects the access token,
 * decodes JSON and converts error payloads into {@see ApiException}.
 */
final class HttpClient
{
    public function __construct(
        private readonly Config $config,
        private readonly Transport $transport,
    ) {
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = []): array
    {
        return $this->decode($this->transport->request('GET', $this->buildUrl($path, $query)));
    }

    /**
     * POST with form-encoded params (the Graph API default).
     *
     * @param array<string, mixed> $params
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    public function post(string $path, array $params = [], array $query = []): array
    {
        return $this->decode($this->transport->request('POST', $this->buildUrl($path, $query), form: $params));
    }

    /**
     * POST with a JSON body (used for messaging).
     *
     * @param array<string, mixed> $json
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    public function postJson(string $path, array $json, array $query = []): array
    {
        return $this->decode($this->transport->request('POST', $this->buildUrl($path, $query), json: $json));
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    public function delete(string $path, array $query = []): array
    {
        return $this->decode($this->transport->request('DELETE', $this->buildUrl($path, $query)));
    }

    /**
     * @param array<string, mixed> $query
     */
    private function buildUrl(string $path, array $query): string
    {
        $query['access_token'] ??= $this->config->requireAccessToken();

        return $this->config->url($path) . '?' . http_build_query($query);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(RawResponse $response): array
    {
        $decoded = $response->body === '' ? [] : json_decode($response->body, true);

        if (! is_array($decoded)) {
            if ($response->isSuccessful()) {
                throw new InstagramException('Unexpected non-JSON response from Instagram: ' . substr($response->body, 0, 200));
            }

            throw new ApiException('Instagram Graph API error (HTTP ' . $response->statusCode . ')', $response->statusCode);
        }

        if (! $response->isSuccessful() || isset($decoded['error'])) {
            throw ApiException::fromResponse($response->statusCode, $decoded);
        }

        return $decoded;
    }
}
