<?php

declare(strict_types=1);

namespace TexHub\InstagramGraphApi\Http;

use TexHub\InstagramGraphApi\Exceptions\TransportException;

/**
 * HTTP transport abstraction so the SDK has no hard dependency on a specific
 * HTTP client and can be fully unit-tested with a fake.
 */
interface Transport
{
    /**
     * Perform an HTTP request.
     *
     * @param array<string, string>     $headers
     * @param array<string, mixed>|null $form JSON-encoded as application/x-www-form-urlencoded body.
     * @param array<string, mixed>|null $json JSON body (Content-Type: application/json).
     *
     * @throws TransportException On connection/network failures.
     */
    public function request(
        string $method,
        string $url,
        array $headers = [],
        ?array $form = null,
        ?array $json = null,
    ): RawResponse;
}
