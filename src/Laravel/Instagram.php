<?php

declare(strict_types=1);

namespace TexHub\InstagramGraphApi\Laravel;

use Illuminate\Support\Facades\Facade;

/**
 * Laravel facade for the Instagram Graph API client.
 *
 * @method static \TexHub\InstagramGraphApi\OAuth\OAuthClient      oauth()
 * @method static \TexHub\InstagramGraphApi\Webhook\WebhookHandler webhooks()
 * @method static \TexHub\InstagramGraphApi\Resources\UsersClient    users()
 * @method static \TexHub\InstagramGraphApi\Resources\MediaClient    media()
 * @method static \TexHub\InstagramGraphApi\Resources\CommentsClient comments()
 * @method static \TexHub\InstagramGraphApi\Resources\MessagesClient messages()
 * @method static \TexHub\InstagramGraphApi\Http\HttpClient          http()
 * @method static \TexHub\InstagramGraphApi\Instagram                withAccessToken(string $accessToken)
 * @method static \TexHub\InstagramGraphApi\Config                   config()
 *
 * @see \TexHub\InstagramGraphApi\Instagram
 */
class Instagram extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'instagram';
    }
}
