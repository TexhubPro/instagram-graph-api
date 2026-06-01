<?php

declare(strict_types=1);

namespace TexHub\InstagramGraphApi\Resources;

use TexHub\InstagramGraphApi\Responses\ListResponse;
use TexHub\InstagramGraphApi\Responses\Response;

/**
 * Content publishing & media — photos, videos/reels, stories and carousels.
 *
 * Publishing is two steps: create a media container, then publish it. The
 * `publish*()` helpers do both in one call. Images/videos must be reachable
 * public URLs (Instagram fetches them).
 *
 * @see https://developers.facebook.com/docs/instagram-platform/content-publishing
 */
final class MediaClient extends Resource
{
    public const DEFAULT_FIELDS = [
        'id', 'caption', 'media_type', 'media_url', 'permalink',
        'thumbnail_url', 'timestamp', 'username', 'like_count', 'comments_count',
    ];

    // ---- Containers -------------------------------------------------------

    /**
     * Create a photo container.
     *
     * @param array<string, mixed> $options Extra fields (e.g. user_tags, location_id).
     */
    public function createPhoto(string $imageUrl, ?string $caption = null, array $options = []): Response
    {
        return $this->createContainer(['image_url' => $imageUrl] + $this->withCaption($caption, $options));
    }

    /**
     * Create a reel (video) container.
     *
     * @param array<string, mixed> $options
     */
    public function createReel(string $videoUrl, ?string $caption = null, array $options = []): Response
    {
        return $this->createContainer(
            ['media_type' => 'REELS', 'video_url' => $videoUrl] + $this->withCaption($caption, $options),
        );
    }

    /**
     * Create a story container (image or video URL).
     *
     * @param array<string, mixed> $options
     */
    public function createStory(string $mediaUrl, bool $isVideo = false, array $options = []): Response
    {
        $media = $isVideo ? ['video_url' => $mediaUrl] : ['image_url' => $mediaUrl];

        return $this->createContainer(['media_type' => 'STORIES'] + $media + $options);
    }

    /**
     * Create a single carousel item container (photo or video).
     *
     * @param array<string, mixed> $options
     */
    public function createCarouselItem(string $mediaUrl, bool $isVideo = false, array $options = []): Response
    {
        $media = $isVideo ? ['media_type' => 'VIDEO', 'video_url' => $mediaUrl] : ['image_url' => $mediaUrl];

        return $this->createContainer(['is_carousel_item' => 'true'] + $media + $options);
    }

    /**
     * Create a carousel container from previously created item ids.
     *
     * @param array<int, string>   $childrenIds
     * @param array<string, mixed> $options
     */
    public function createCarousel(array $childrenIds, ?string $caption = null, array $options = []): Response
    {
        return $this->createContainer([
            'media_type' => 'CAROUSEL',
            'children' => implode(',', $childrenIds),
        ] + $this->withCaption($caption, $options));
    }

    /**
     * @param array<string, mixed> $params
     */
    public function createContainer(array $params): Response
    {
        return Response::from($this->http->post($this->config->requireIgUserId() . '/media', $params));
    }

    // ---- Publish ----------------------------------------------------------

    /**
     * Publish a previously created container.
     */
    public function publish(string $creationId): Response
    {
        return Response::from($this->http->post($this->config->requireIgUserId() . '/media_publish', [
            'creation_id' => $creationId,
        ]));
    }

    /**
     * Create a photo and publish it in one call. Returns the published media.
     *
     * @param array<string, mixed> $options
     */
    public function publishPhoto(string $imageUrl, ?string $caption = null, array $options = []): Response
    {
        return $this->publish((string) $this->createPhoto($imageUrl, $caption, $options)->id());
    }

    /**
     * Create a reel and publish it in one call.
     *
     * @param array<string, mixed> $options
     */
    public function publishReel(string $videoUrl, ?string $caption = null, array $options = []): Response
    {
        return $this->publish((string) $this->createReel($videoUrl, $caption, $options)->id());
    }

    /**
     * Create a story and publish it in one call.
     *
     * @param array<string, mixed> $options
     */
    public function publishStory(string $mediaUrl, bool $isVideo = false, array $options = []): Response
    {
        return $this->publish((string) $this->createStory($mediaUrl, $isVideo, $options)->id());
    }

    // ---- Read -------------------------------------------------------------

    /**
     * List the account's media.
     *
     * @param array<int, string>   $fields
     * @param array<string, mixed> $query
     */
    public function list(array $fields = self::DEFAULT_FIELDS, array $query = []): ListResponse
    {
        return ListResponse::from($this->http->get(
            $this->config->requireIgUserId() . '/media',
            ['fields' => implode(',', $fields)] + $query,
        ));
    }

    /**
     * Get a single media object.
     *
     * @param array<int, string> $fields
     */
    public function get(string $mediaId, array $fields = self::DEFAULT_FIELDS): Response
    {
        return Response::from($this->http->get($mediaId, ['fields' => implode(',', $fields)]));
    }

    /**
     * Check the daily content publishing rate limit usage.
     */
    public function publishingLimit(): Response
    {
        return Response::from($this->http->get(
            $this->config->requireIgUserId() . '/content_publishing_limit',
            ['fields' => 'config,quota_usage'],
        ));
    }

    /**
     * Poll a container until it is finished processing (useful for reels/video).
     */
    public function containerStatus(string $containerId): Response
    {
        return Response::from($this->http->get($containerId, ['fields' => 'status_code,status']));
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function withCaption(?string $caption, array $options): array
    {
        if ($caption !== null) {
            $options['caption'] = $caption;
        }

        return $options;
    }
}
