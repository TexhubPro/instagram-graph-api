<?php

declare(strict_types=1);

namespace TexHub\InstagramGraphApi\Webhook;

/**
 * A single normalized webhook change/messaging event.
 */
final class WebhookEvent
{
    /**
     * @param string               $type    'comment', 'message', 'mention', … (the change field
     *                                       or 'message'/'messaging_postback' for DMs).
     * @param array<string, mixed> $value   The event value/payload.
     * @param array<string, mixed> $raw     The raw entry/messaging item.
     */
    public function __construct(
        public readonly string $type,
        public readonly array $value,
        public readonly array $raw = [],
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->value[$key] ?? $default;
    }

    public function isComment(): bool
    {
        return $this->type === 'comments';
    }

    public function isMessage(): bool
    {
        return $this->type === 'messages' || $this->type === 'message';
    }

    /**
     * For message events: the sender's Instagram-scoped id (IGSID).
     */
    public function senderId(): ?string
    {
        return isset($this->raw['sender']['id']) ? (string) $this->raw['sender']['id'] : null;
    }

    /**
     * For message events: the message text, if any.
     */
    public function messageText(): ?string
    {
        return isset($this->raw['message']['text']) ? (string) $this->raw['message']['text'] : null;
    }
}
