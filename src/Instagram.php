<?php

declare(strict_types=1);

namespace TexHub\InstagramGraphApi;

use TexHub\InstagramGraphApi\Http\CurlTransport;
use TexHub\InstagramGraphApi\Http\HttpClient;
use TexHub\InstagramGraphApi\Http\Transport;
use TexHub\InstagramGraphApi\OAuth\OAuthClient;
use TexHub\InstagramGraphApi\Resources\CommentsClient;
use TexHub\InstagramGraphApi\Resources\MediaClient;
use TexHub\InstagramGraphApi\Resources\MessagesClient;
use TexHub\InstagramGraphApi\Resources\UsersClient;
use TexHub\InstagramGraphApi\Webhook\WebhookHandler;

/**
 * Entry point of the Instagram Graph API SDK.
 *
 * Framework-agnostic: construct it directly, or resolve it from the container
 * in Laravel via the {@see \TexHub\InstagramGraphApi\Laravel\Instagram} facade.
 *
 * ```php
 * $ig = Instagram::make('APP_ID', 'APP_SECRET', accessToken: 'LONG_LIVED_TOKEN');
 *
 * $ig->users()->me();
 * $ig->media()->publishPhoto('https://cdn/photo.jpg', 'Привет!');
 * $ig->messages()->sendText($igsid, 'Спасибо за сообщение!');
 * ```
 */
final class Instagram
{
    private readonly Transport $transport;
    private readonly HttpClient $httpClient;

    /** @var array<string, object> */
    private array $resources = [];

    public function __construct(
        private readonly Config $config,
        ?Transport $transport = null,
    ) {
        $this->transport = $transport ?? new CurlTransport($config->timeout);
        $this->httpClient = new HttpClient($config, $this->transport);
    }

    public static function make(
        string $appId,
        string $appSecret,
        ?string $accessToken = null,
        ?string $igUserId = null,
        ?Transport $transport = null,
    ): self {
        return new self(
            new Config($appId, $appSecret, $accessToken, $igUserId),
            $transport,
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config, ?Transport $transport = null): self
    {
        return new self(Config::fromArray($config), $transport);
    }

    public function config(): Config
    {
        return $this->config;
    }

    /**
     * Return a new instance configured with a different access token.
     */
    public function withAccessToken(string $accessToken): self
    {
        return new self($this->config->withAccessToken($accessToken), $this->transport);
    }

    /**
     * Low-level Graph HTTP client — call any endpoint not covered by a resource.
     */
    public function http(): HttpClient
    {
        return $this->httpClient;
    }

    public function oauth(): OAuthClient
    {
        return $this->resources[OAuthClient::class] ??= new OAuthClient($this->config, $this->transport);
    }

    public function webhooks(): WebhookHandler
    {
        return $this->resources[WebhookHandler::class] ??= new WebhookHandler($this->config);
    }

    public function users(): UsersClient
    {
        return $this->resource(UsersClient::class);
    }

    public function media(): MediaClient
    {
        return $this->resource(MediaClient::class);
    }

    public function comments(): CommentsClient
    {
        return $this->resource(CommentsClient::class);
    }

    public function messages(): MessagesClient
    {
        return $this->resource(MessagesClient::class);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private function resource(string $class): object
    {
        /** @var T */
        return $this->resources[$class] ??= new $class($this->httpClient, $this->config);
    }
}
