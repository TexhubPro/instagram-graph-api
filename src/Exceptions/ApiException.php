<?php

declare(strict_types=1);

namespace TexHub\InstagramGraphApi\Exceptions;

/**
 * Thrown when the Graph API returns an error payload:
 * `{ "error": { "message", "type", "code", "error_subcode", "fbtrace_id" } }`.
 */
class ApiException extends InstagramException
{
    /**
     * @param array<string, mixed> $payload Full decoded response body.
     */
    public function __construct(
        string $message,
        public readonly int $httpStatus,
        public readonly string $errorType = '',
        public readonly int $errorCode = 0,
        public readonly ?int $errorSubcode = null,
        public readonly ?string $fbtraceId = null,
        public readonly array $payload = [],
    ) {
        parent::__construct($message, $httpStatus);
    }

    /**
     * @param array<string, mixed> $body
     */
    public static function fromResponse(int $httpStatus, array $body): self
    {
        $error = is_array($body['error'] ?? null) ? $body['error'] : [];

        return new self(
            message: (string) ($error['message'] ?? ('Instagram Graph API error (HTTP ' . $httpStatus . ')')),
            httpStatus: $httpStatus,
            errorType: (string) ($error['type'] ?? ''),
            errorCode: (int) ($error['code'] ?? 0),
            errorSubcode: isset($error['error_subcode']) ? (int) $error['error_subcode'] : null,
            fbtraceId: isset($error['fbtrace_id']) ? (string) $error['fbtrace_id'] : null,
            payload: $body,
        );
    }

    /** Token expired/invalid (code 190). */
    public function isTokenError(): bool
    {
        return $this->errorCode === 190;
    }

    /** Rate limited (codes 4, 17, 32, 613). */
    public function isRateLimit(): bool
    {
        return in_array($this->errorCode, [4, 17, 32, 613], true);
    }
}
