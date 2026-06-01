<?php

declare(strict_types=1);

namespace TexHub\InstagramGraphApi\Resources;

use TexHub\InstagramGraphApi\Builders\Message;
use TexHub\InstagramGraphApi\Responses\ListResponse;
use TexHub\InstagramGraphApi\Responses\Response;

/**
 * Direct messaging (Instagram messaging).
 *
 * Send messages, images, buttons and quick replies; read conversations and
 * their messages. The recipient id is the Instagram-scoped id (IGSID) you
 * receive in messaging webhooks.
 *
 * @see https://developers.facebook.com/docs/instagram-platform/instagram-api-with-instagram-login/messaging-api
 */
final class MessagesClient extends Resource
{
    /**
     * Send a pre-built message object (see {@see Message}).
     *
     * @param array<string, mixed> $message
     */
    public function send(string $recipientId, array $message): Response
    {
        return Response::from($this->http->postJson($this->config->requireIgUserId() . '/messages', [
            'recipient' => ['id' => $recipientId],
            'message' => $message,
        ]));
    }

    public function sendText(string $recipientId, string $text): Response
    {
        return $this->send($recipientId, Message::text($text));
    }

    public function sendImage(string $recipientId, string $imageUrl): Response
    {
        return $this->send($recipientId, Message::image($imageUrl));
    }

    /**
     * Send text with a button template.
     *
     * @param array<int, array<string, string>> $buttons
     */
    public function sendButtons(string $recipientId, string $text, array $buttons): Response
    {
        return $this->send($recipientId, Message::buttons($text, $buttons));
    }

    /**
     * Send text with quick-reply chips.
     *
     * @param array<int, array<string, string>> $quickReplies
     */
    public function sendQuickReplies(string $recipientId, string $text, array $quickReplies): Response
    {
        return $this->send($recipientId, Message::quickReplies($text, $quickReplies));
    }

    /**
     * React to a message with an emoji (e.g. "love").
     */
    public function react(string $recipientId, string $messageId, string $reaction = 'love'): Response
    {
        return Response::from($this->http->postJson($this->config->requireIgUserId() . '/messages', [
            'recipient' => ['id' => $recipientId],
            'sender_action' => 'react',
            'payload' => ['message_id' => $messageId, 'reaction' => $reaction],
        ]));
    }

    /**
     * Show the typing indicator (typing_on / typing_off / mark_seen).
     */
    public function senderAction(string $recipientId, string $action = 'typing_on'): Response
    {
        return Response::from($this->http->postJson($this->config->requireIgUserId() . '/messages', [
            'recipient' => ['id' => $recipientId],
            'sender_action' => $action,
        ]));
    }

    /**
     * List conversations.
     *
     * @param array<string, mixed> $query
     */
    public function conversations(array $query = []): ListResponse
    {
        return ListResponse::from($this->http->get(
            $this->config->requireIgUserId() . '/conversations',
            ['platform' => 'instagram'] + $query,
        ));
    }

    /**
     * Find the conversation with a particular user (IGSID).
     */
    public function conversationWith(string $userId): ListResponse
    {
        return ListResponse::from($this->http->get(
            $this->config->requireIgUserId() . '/conversations',
            ['platform' => 'instagram', 'user_id' => $userId],
        ));
    }

    /**
     * List messages in a conversation.
     *
     * @param array<int, string> $fields
     */
    public function messages(string $conversationId, array $fields = ['id', 'created_time', 'from', 'to', 'message']): ListResponse
    {
        return ListResponse::from($this->http->get(
            $conversationId . '/messages',
            ['fields' => implode(',', $fields)],
        ));
    }
}
