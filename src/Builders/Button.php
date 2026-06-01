<?php

declare(strict_types=1);

namespace TexHub\InstagramGraphApi\Builders;

/**
 * Helpers for building message buttons and quick replies.
 */
final class Button
{
    /**
     * A button that opens a URL.
     *
     * @return array<string, string>
     */
    public static function url(string $title, string $url): array
    {
        return ['type' => 'web_url', 'title' => $title, 'url' => $url];
    }

    /**
     * A postback button (sends a payload back to your webhook when tapped).
     *
     * @return array<string, string>
     */
    public static function postback(string $title, string $payload): array
    {
        return ['type' => 'postback', 'title' => $title, 'payload' => $payload];
    }

    /**
     * A quick reply chip.
     *
     * @return array<string, string>
     */
    public static function quickReply(string $title, string $payload, ?string $imageUrl = null): array
    {
        $reply = ['content_type' => 'text', 'title' => $title, 'payload' => $payload];

        if ($imageUrl !== null) {
            $reply['image_url'] = $imageUrl;
        }

        return $reply;
    }
}
