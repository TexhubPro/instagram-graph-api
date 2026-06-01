<?php

declare(strict_types=1);

namespace TexHub\InstagramGraphApi\Exceptions;

/**
 * Thrown when a webhook payload signature (X-Hub-Signature-256) is invalid.
 */
class InvalidSignatureException extends InstagramException
{
}
