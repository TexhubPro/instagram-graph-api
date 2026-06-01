<?php

declare(strict_types=1);

namespace TexHub\InstagramGraphApi\Tests\Support;

use TexHub\InstagramGraphApi\Http\RawResponse;
use TexHub\InstagramGraphApi\Http\Transport;

/**
 * In-memory transport for tests: records every request and returns queued
 * canned responses (or a default) without touching the network.
 */
final class FakeTransport implements Transport
{
    /** @var array<int, array{method: string, url: string, headers: array, form: ?array, json: ?array}> */
    public array $history = [];

    /** @var array<int, RawResponse> */
    private array $queue = [];

    public function __construct(
        private int $defaultStatus = 200,
        private string $defaultBody = '{"id":"123"}',
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function push(array $payload, int $status = 200): self
    {
        $this->queue[] = new RawResponse($status, (string) json_encode($payload));

        return $this;
    }

    public function request(
        string $method,
        string $url,
        array $headers = [],
        ?array $form = null,
        ?array $json = null,
    ): RawResponse {
        $this->history[] = compact('method', 'url', 'headers', 'form', 'json');

        return $this->queue !== [] ? array_shift($this->queue) : new RawResponse($this->defaultStatus, $this->defaultBody);
    }

    public function last(): array
    {
        return $this->history[count($this->history) - 1];
    }

    public function lastUrl(): string
    {
        return $this->last()['url'];
    }
}
