<?php

declare(strict_types=1);

namespace TexHub\InstagramGraphApi\OAuth;

use TexHub\InstagramGraphApi\Config;
use TexHub\InstagramGraphApi\Exceptions\ApiException;
use TexHub\InstagramGraphApi\Exceptions\ConfigurationException;
use TexHub\InstagramGraphApi\Exceptions\InstagramException;
use TexHub\InstagramGraphApi\Http\RawResponse;
use TexHub\InstagramGraphApi\Http\Transport;

/**
 * OAuth flow & token management for Instagram Login.
 *
 * Flow:
 *   1. {@see authorizationUrl()} — send the user to authorize.
 *   2. {@see requestShortLivedToken()} — exchange the returned `code`.
 *   3. {@see exchangeForLongLivedToken()} — upgrade to a 60-day token.
 *   4. {@see refreshLongLivedToken()} — refresh before it expires.
 */
final class OAuthClient
{
    public function __construct(
        private readonly Config $config,
        private readonly Transport $transport,
    ) {
    }

    /**
     * Build the URL to redirect the user to for authorization.
     *
     * @param array<int, string> $scopes e.g. ['instagram_business_basic',
     *        'instagram_business_content_publish', 'instagram_business_manage_messages',
     *        'instagram_business_manage_comments']
     */
    public function authorizationUrl(array $scopes, ?string $state = null, ?string $redirectUri = null): string
    {
        $redirectUri ??= $this->config->redirectUri;
        if ($redirectUri === null) {
            throw new ConfigurationException('A redirect URI is required to build the authorization URL.');
        }

        $params = [
            'client_id' => $this->config->appId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(',', $scopes),
        ];

        if ($state !== null) {
            $params['state'] = $state;
        }

        return $this->config->authorizeUrl . '?' . http_build_query($params);
    }

    /**
     * Exchange an authorization code for a short-lived access token.
     */
    public function requestShortLivedToken(string $code, ?string $redirectUri = null): AccessToken
    {
        $redirectUri ??= $this->config->redirectUri;
        if ($redirectUri === null) {
            throw new ConfigurationException('A redirect URI is required to exchange the authorization code.');
        }

        $data = $this->decode($this->transport->request('POST', $this->config->tokenUrl, form: [
            'client_id' => $this->config->appId,
            'client_secret' => $this->config->appSecret,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri,
            'code' => $code,
        ]));

        return AccessToken::fromArray($data, time());
    }

    /**
     * Exchange a short-lived token for a long-lived (≈60-day) token.
     */
    public function exchangeForLongLivedToken(string $shortLivedToken): AccessToken
    {
        $url = $this->graphRoot() . '/access_token?' . http_build_query([
            'grant_type' => 'ig_exchange_token',
            'client_secret' => $this->config->appSecret,
            'access_token' => $shortLivedToken,
        ]);

        return AccessToken::fromArray($this->decode($this->transport->request('GET', $url)), time());
    }

    /**
     * Refresh a long-lived token (must be at least 24h old and unexpired).
     */
    public function refreshLongLivedToken(?string $longLivedToken = null): AccessToken
    {
        $longLivedToken ??= $this->config->accessToken;
        if ($longLivedToken === null) {
            throw new ConfigurationException('A long-lived token is required to refresh.');
        }

        $url = $this->graphRoot() . '/refresh_access_token?' . http_build_query([
            'grant_type' => 'ig_refresh_token',
            'access_token' => $longLivedToken,
        ]);

        return AccessToken::fromArray($this->decode($this->transport->request('GET', $url)), time());
    }

    private function graphRoot(): string
    {
        return rtrim($this->config->graphUrl, '/');
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(RawResponse $response): array
    {
        $decoded = $response->body === '' ? [] : json_decode($response->body, true);

        if (! is_array($decoded)) {
            throw new InstagramException('Unexpected non-JSON OAuth response: ' . substr($response->body, 0, 200));
        }

        if (! $response->isSuccessful() || isset($decoded['error']) || isset($decoded['error_type'])) {
            $message = $decoded['error_message'] ?? ($decoded['error']['message'] ?? 'Instagram OAuth error');

            throw new ApiException((string) $message, $response->statusCode, payload: $decoded);
        }

        return $decoded;
    }
}
