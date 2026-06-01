<?php

declare(strict_types=1);

namespace TexHub\InstagramGraphApi;

use TexHub\InstagramGraphApi\Exceptions\ConfigurationException;

/**
 * Immutable SDK configuration for the Instagram Graph API
 * (Instagram API with Instagram Login).
 */
final class Config
{
    public const DEFAULT_GRAPH_URL = 'https://graph.instagram.com';
    public const DEFAULT_AUTHORIZE_URL = 'https://www.instagram.com/oauth/authorize';
    public const DEFAULT_TOKEN_URL = 'https://api.instagram.com/oauth/access_token';
    public const DEFAULT_VERSION = 'v23.0';

    /**
     * @param string      $appId            Instagram app (client) id.
     * @param string      $appSecret        Instagram app (client) secret.
     * @param string|null $accessToken      Access token used for API calls.
     * @param string|null $igUserId         Instagram user id ("me" is used when null).
     * @param string|null $redirectUri      OAuth redirect URI.
     * @param string|null $webhookVerifyToken Token to validate webhook subscription challenges.
     * @param string      $graphUrl         Graph API base URL.
     * @param string      $version          API version (set "" to omit from paths).
     * @param int         $timeout          HTTP timeout in seconds.
     */
    public function __construct(
        public readonly string $appId,
        public readonly string $appSecret,
        public readonly ?string $accessToken = null,
        public readonly ?string $igUserId = null,
        public readonly ?string $redirectUri = null,
        public readonly ?string $webhookVerifyToken = null,
        public readonly string $graphUrl = self::DEFAULT_GRAPH_URL,
        public readonly string $authorizeUrl = self::DEFAULT_AUTHORIZE_URL,
        public readonly string $tokenUrl = self::DEFAULT_TOKEN_URL,
        public readonly string $version = self::DEFAULT_VERSION,
        public readonly int $timeout = 30,
    ) {
        if (trim($this->appId) === '') {
            throw new ConfigurationException('Instagram app id must not be empty.');
        }

        if (trim($this->appSecret) === '') {
            throw new ConfigurationException('Instagram app secret must not be empty.');
        }

        if ($this->timeout < 1) {
            throw new ConfigurationException('Instagram timeout must be a positive number of seconds.');
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            appId: (string) ($config['app_id'] ?? ''),
            appSecret: (string) ($config['app_secret'] ?? ''),
            accessToken: self::nullableString($config['access_token'] ?? null),
            igUserId: self::nullableString($config['ig_user_id'] ?? null),
            redirectUri: self::nullableString($config['redirect_uri'] ?? null),
            webhookVerifyToken: self::nullableString($config['webhook_verify_token'] ?? null),
            graphUrl: (string) ($config['graph_url'] ?? self::DEFAULT_GRAPH_URL),
            authorizeUrl: (string) ($config['authorize_url'] ?? self::DEFAULT_AUTHORIZE_URL),
            tokenUrl: (string) ($config['token_url'] ?? self::DEFAULT_TOKEN_URL),
            version: (string) ($config['version'] ?? self::DEFAULT_VERSION),
            timeout: (int) ($config['timeout'] ?? 30),
        );
    }

    /**
     * Build a Graph API URL for a path, prefixing the API version when set.
     */
    public function url(string $path): string
    {
        $path = ltrim($path, '/');
        $base = rtrim($this->graphUrl, '/');

        if ($this->version !== '' && ! str_starts_with($path, $this->version)) {
            $base .= '/' . $this->version;
        }

        return $base . '/' . $path;
    }

    public function requireAccessToken(): string
    {
        if ($this->accessToken === null || trim($this->accessToken) === '') {
            throw new ConfigurationException('An access token is required for this call. Set it in config or via withAccessToken().');
        }

        return $this->accessToken;
    }

    public function requireIgUserId(): string
    {
        return $this->igUserId !== null && trim($this->igUserId) !== '' ? $this->igUserId : 'me';
    }

    /**
     * Return a copy with a different access token (e.g. a freshly refreshed one).
     */
    public function withAccessToken(string $accessToken): self
    {
        return new self(
            appId: $this->appId,
            appSecret: $this->appSecret,
            accessToken: $accessToken,
            igUserId: $this->igUserId,
            redirectUri: $this->redirectUri,
            webhookVerifyToken: $this->webhookVerifyToken,
            graphUrl: $this->graphUrl,
            authorizeUrl: $this->authorizeUrl,
            tokenUrl: $this->tokenUrl,
            version: $this->version,
            timeout: $this->timeout,
        );
    }

    private static function nullableString(mixed $value): ?string
    {
        return $value === null || $value === '' ? null : (string) $value;
    }
}
