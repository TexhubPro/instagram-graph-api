<?php

declare(strict_types=1);

namespace TexHub\InstagramGraphApi\Responses;

use ArrayAccess;

/**
 * Generic response wrapper around a decoded Graph API payload.
 *
 * @implements ArrayAccess<string, mixed>
 */
class Response implements ArrayAccess
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        protected readonly array $attributes = [],
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public static function from(array $attributes): static
    {
        return new static($attributes);
    }

    public function id(): ?string
    {
        return isset($this->attributes['id']) ? (string) $this->attributes['id'] : null;
    }

    /**
     * Get a value by dot path, e.g. `from.username`.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        $value = $this->attributes;
        foreach (explode('.', $key) as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->attributes[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->attributes[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
    }

    public function offsetUnset(mixed $offset): void
    {
    }
}
