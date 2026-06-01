<?php

declare(strict_types=1);

namespace TexHub\InstagramGraphApi\Webhook;

/**
 * A single normalized webhook change/messaging event.
 */
final class WebhookEvent
{
    /**
     * @param string               $type      'comment', 'message', 'mention', … (the change field
     *                                         or 'message'/'messaging_postback' for DMs).
     * @param array<string, mixed> $value     The event value/payload.
     * @param array<string, mixed> $raw       The raw entry/messaging item.
     * @param string|null          $accountId The Instagram account id (entry.id) that received the
     *                                         event — the tenant key for multi-tenant (SaaS) routing.
     */
    public function __construct(
        public readonly string $type,
        public readonly array $value,
        public readonly array $raw = [],
        public readonly ?string $accountId = null,
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->value[$key] ?? $default;
    }

    /**
     * The Instagram account id (entry.id) that received this event. Use it to
     * route the event to the right connected account in a multi-tenant setup.
     */
    public function accountId(): ?string
    {
        return $this->accountId;
    }

    /**
     * For message events: the recipient (your connected account) IGSID.
     */
    public function recipientId(): ?string
    {
        return isset($this->raw['recipient']['id']) ? (string) $this->raw['recipient']['id'] : null;
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
