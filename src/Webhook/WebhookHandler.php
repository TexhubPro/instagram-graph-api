<?php

declare(strict_types=1);

namespace TexHub\InstagramGraphApi\Webhook;

use TexHub\InstagramGraphApi\Config;
use TexHub\InstagramGraphApi\Exceptions\InstagramException;
use TexHub\InstagramGraphApi\Exceptions\InvalidSignatureException;

/**
 * Verifies and parses Instagram webhook callbacks.
 *
 * - Subscription verification: echo `hub.challenge` when `hub.verify_token` matches.
 * - Event delivery: verify the `X-Hub-Signature-256` header (HMAC-SHA256 of the
 *   raw body with the app secret), then parse the payload into events.
 */
final class WebhookHandler
{
    public function __construct(
        private readonly Config $config,
    ) {
    }

    /**
     * Handle the GET subscription handshake. Returns the challenge string to
     * echo back (HTTP 200), or null if verification failed.
     *
     * @param array<string, mixed> $query The request query parameters.
     */
    public function verifyChallenge(array $query): ?string
    {
        $mode = $query['hub_mode'] ?? $query['hub.mode'] ?? null;
        $token = $query['hub_verify_token'] ?? $query['hub.verify_token'] ?? null;
        $challenge = $query['hub_challenge'] ?? $query['hub.challenge'] ?? null;

        if ($mode === 'subscribe'
            && $this->config->webhookVerifyToken !== null
            && hash_equals($this->config->webhookVerifyToken, (string) $token)) {
            return $challenge === null ? null : (string) $challenge;
        }

        return null;
    }

    /**
     * Verify the X-Hub-Signature-256 header against the raw request body.
     */
    public function verifySignature(string $rawBody, ?string $signatureHeader): bool
    {
        if ($signatureHeader === null || ! str_starts_with($signatureHeader, 'sha256=')) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $this->config->appSecret);

        return hash_equals($expected, $signatureHeader);
    }

    /**
     * Verify the signature, throwing on mismatch.
     *
     * @throws InvalidSignatureException
     */
    public function assertValidSignature(string $rawBody, ?string $signatureHeader): void
    {
        if (! $this->verifySignature($rawBody, $signatureHeader)) {
            throw new InvalidSignatureException('Instagram webhook signature verification failed.');
        }
    }

    /**
     * Parse a webhook payload into a flat list of events.
     *
     * @param string|array<string, mixed> $payload
     *
     * @return array<int, WebhookEvent>
     *
     * @throws InstagramException On invalid JSON.
     */
    public function parse(string|array $payload): array
    {
        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            if (! is_array($decoded)) {
                throw new InstagramException('Webhook payload is not valid JSON.');
            }
            $payload = $decoded;
        }

        $events = [];

        foreach (($payload['entry'] ?? []) as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            // Field changes (comments, mentions, …)
            foreach (($entry['changes'] ?? []) as $change) {
                if (is_array($change)) {
                    $events[] = new WebhookEvent(
                        type: (string) ($change['field'] ?? 'unknown'),
                        value: is_array($change['value'] ?? null) ? $change['value'] : [],
                        raw: $change,
                    );
                }
            }

            // Direct messages
            foreach (($entry['messaging'] ?? []) as $messaging) {
                if (is_array($messaging)) {
                    $type = isset($messaging['message']) ? 'messages'
                        : (isset($messaging['postback']) ? 'messaging_postback' : 'messaging');

                    $events[] = new WebhookEvent(
                        type: $type,
                        value: $messaging,
                        raw: $messaging,
                    );
                }
            }
        }

        return $events;
    }
}
