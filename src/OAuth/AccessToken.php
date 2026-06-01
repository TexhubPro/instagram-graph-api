<?php

declare(strict_types=1);

namespace TexHub\InstagramGraphApi\OAuth;

/**
 * An access token together with its metadata.
 *
 * Short-lived tokens last ~1 hour; long-lived tokens last ~60 days and can be
 * refreshed (see {@see OAuthClient::refreshLongLivedToken()}).
 */
final class AccessToken
{
    /**
     * @param array<int, string> $permissions
     */
    public function __construct(
        public readonly string $token,
        public readonly string $type = 'bearer',
        public readonly ?int $expiresIn = null,
        public readonly ?string $userId = null,
        public readonly array $permissions = [],
        public readonly ?int $obtainedAt = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, ?int $obtainedAt = null): self
    {
        $permissions = $data['permissions'] ?? [];
        if (is_string($permissions)) {
            $permissions = array_values(array_filter(array_map('trim', explode(',', $permissions))));
        }

        return new self(
            token: (string) ($data['access_token'] ?? ''),
            type: (string) ($data['token_type'] ?? 'bearer'),
            expiresIn: isset($data['expires_in']) ? (int) $data['expires_in'] : null,
            userId: isset($data['user_id']) ? (string) $data['user_id'] : null,
            permissions: is_array($permissions) ? $permissions : [],
            obtainedAt: $obtainedAt,
        );
    }

    /**
     * UNIX timestamp when the token expires, if known.
     */
    public function expiresAt(): ?int
    {
        if ($this->expiresIn === null) {
            return null;
        }

        return ($this->obtainedAt ?? time()) + $this->expiresIn;
    }

    /**
     * Whether the token expires within the given number of days.
     */
    public function expiresWithinDays(int $days): bool
    {
        $expiresAt = $this->expiresAt();

        return $expiresAt !== null && ($expiresAt - time()) < ($days * 86400);
    }

    public function isLongLived(): bool
    {
        return $this->expiresIn !== null && $this->expiresIn > 3600;
    }

    public function __toString(): string
    {
        return $this->token;
    }
}
