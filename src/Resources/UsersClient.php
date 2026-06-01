<?php

declare(strict_types=1);

namespace TexHub\InstagramGraphApi\Resources;

use TexHub\InstagramGraphApi\Responses\Response;

/**
 * User/account information.
 *
 * @see https://developers.facebook.com/docs/instagram-platform
 */
final class UsersClient extends Resource
{
    public const DEFAULT_FIELDS = [
        'user_id', 'username', 'name', 'account_type',
        'profile_picture_url', 'followers_count', 'follows_count',
        'media_count', 'biography',
    ];

    /**
     * Get the authenticated account's profile.
     *
     * @param array<int, string> $fields
     */
    public function me(array $fields = self::DEFAULT_FIELDS): Response
    {
        return Response::from($this->http->get('me', ['fields' => implode(',', $fields)]));
    }

    /**
     * Get a user/account by id.
     *
     * @param array<int, string> $fields
     */
    public function get(string $userId, array $fields = self::DEFAULT_FIELDS): Response
    {
        return Response::from($this->http->get($userId, ['fields' => implode(',', $fields)]));
    }

    /**
     * Convenience: the profile picture (avatar) URL of the authenticated account.
     */
    public function avatarUrl(): ?string
    {
        $url = $this->me(['profile_picture_url'])->get('profile_picture_url');

        return $url === null ? null : (string) $url;
    }

    /**
     * Convenience: the username of the authenticated account.
     */
    public function username(): ?string
    {
        $username = $this->me(['username'])->get('username');

        return $username === null ? null : (string) $username;
    }
}
