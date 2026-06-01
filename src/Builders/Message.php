<?php

declare(strict_types=1);

namespace TexHub\InstagramGraphApi\Builders;

/**
 * Helpers for building the `message` object sent to the messaging endpoint.
 */
final class Message
{
    /**
     * Plain text message.
     *
     * @return array<string, mixed>
     */
    public static function text(string $text): array
    {
        return ['text' => $text];
    }

    /**
     * An image attachment by URL.
     *
     * @return array<string, mixed>
     */
    public static function image(string $url): array
    {
        return self::attachment('image', $url);
    }

    /**
     * A video / audio / file attachment by URL.
     *
     * @return array<string, mixed>
     */
    public static function attachment(string $type, string $url): array
    {
        return ['attachment' => ['type' => $type, 'payload' => ['url' => $url]]];
    }

    /**
     * Text with up to 13 quick-reply chips.
     *
     * @param array<int, array<string, string>> $quickReplies built via {@see Button::quickReply()}
     *
     * @return array<string, mixed>
     */
    public static function quickReplies(string $text, array $quickReplies): array
    {
        return ['text' => $text, 'quick_replies' => $quickReplies];
    }

    /**
     * A button template (up to 3 buttons).
     *
     * @param array<int, array<string, string>> $buttons built via {@see Button::url()} / {@see Button::postback()}
     *
     * @return array<string, mixed>
     */
    public static function buttons(string $text, array $buttons): array
    {
        return [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'button',
                    'text' => $text,
                    'buttons' => $buttons,
                ],
            ],
        ];
    }

    /**
     * A generic carousel template (cards with image, title, subtitle, buttons).
     *
     * @param array<int, array<string, mixed>> $elements
     *
     * @return array<string, mixed>
     */
    public static function generic(array $elements): array
    {
        return [
            'attachment' => [
                'type' => 'template',
                'payload' => ['template_type' => 'generic', 'elements' => $elements],
            ],
        ];
    }
}
