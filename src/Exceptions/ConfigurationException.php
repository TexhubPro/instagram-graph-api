<?php

declare(strict_types=1);

namespace TexHub\InstagramGraphApi\Exceptions;

/**
 * Thrown when the SDK is misconfigured (missing app id/secret/token, etc.).
 */
class ConfigurationException extends InstagramException
{
}
