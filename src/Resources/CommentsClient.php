<?php

declare(strict_types=1);

namespace TexHub\InstagramGraphApi\Resources;

use TexHub\InstagramGraphApi\Responses\ListResponse;
use TexHub\InstagramGraphApi\Responses\Response;

/**
 * Comments & replies on media.
 *
 * @see https://developers.facebook.com/docs/instagram-platform/comment-moderation
 */
final class CommentsClient extends Resource
{
    public const DEFAULT_FIELDS = ['id', 'text', 'username', 'timestamp', 'like_count', 'hidden'];

    /**
     * List comments on a media object.
     *
     * @param array<int, string>   $fields
     * @param array<string, mixed> $query
     */
    public function forMedia(string $mediaId, array $fields = self::DEFAULT_FIELDS, array $query = []): ListResponse
    {
        return ListResponse::from($this->http->get(
            $mediaId . '/comments',
            ['fields' => implode(',', $fields)] + $query,
        ));
    }

    /**
     * Create a top-level comment on a media object.
     */
    public function commentOnMedia(string $mediaId, string $message): Response
    {
        return Response::from($this->http->post($mediaId . '/comments', ['message' => $message]));
    }

    /**
     * Reply to an existing comment.
     */
    public function reply(string $commentId, string $message): Response
    {
        return Response::from($this->http->post($commentId . '/replies', ['message' => $message]));
    }

    /**
     * List replies to a comment.
     *
     * @param array<int, string> $fields
     */
    public function replies(string $commentId, array $fields = self::DEFAULT_FIELDS): ListResponse
    {
        return ListResponse::from($this->http->get(
            $commentId . '/replies',
            ['fields' => implode(',', $fields)],
        ));
    }

    /**
     * Get a single comment.
     *
     * @param array<int, string> $fields
     */
    public function get(string $commentId, array $fields = self::DEFAULT_FIELDS): Response
    {
        return Response::from($this->http->get($commentId, ['fields' => implode(',', $fields)]));
    }

    /**
     * Hide or unhide a comment.
     */
    public function hide(string $commentId, bool $hidden = true): Response
    {
        return Response::from($this->http->post($commentId, ['hide' => $hidden ? 'true' : 'false']));
    }

    /**
     * Enable/disable comments on a media object.
     */
    public function toggleOnMedia(string $mediaId, bool $enabled): Response
    {
        return Response::from($this->http->post($mediaId, ['comment_enabled' => $enabled ? 'true' : 'false']));
    }

    /**
     * Delete a comment.
     */
    public function delete(string $commentId): Response
    {
        return Response::from($this->http->delete($commentId));
    }
}
