<?php

declare(strict_types=1);

namespace TexHub\InstagramGraphApi\Exceptions;

/**
 * Thrown on a network/transport-level failure before a valid response could
 * be parsed (connection refused, timeout, unreadable body).
 */
class TransportException extends InstagramException
{
}
