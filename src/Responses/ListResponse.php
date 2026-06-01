<?php

declare(strict_types=1);

namespace TexHub\InstagramGraphApi\Responses;

/**
 * A paginated list response (`{ "data": [...], "paging": {...} }`).
 */
final class ListResponse extends Response
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function data(): array
    {
        $data = $this->get('data', []);

        return is_array($data) ? $data : [];
    }

    /**
     * @return array<int, Response>
     */
    public function items(): array
    {
        return array_map(static fn (array $item) => new Response($item), $this->data());
    }

    public function nextCursor(): ?string
    {
        $after = $this->get('paging.cursors.after');

        return $after === null ? null : (string) $after;
    }

    public function nextUrl(): ?string
    {
        $next = $this->get('paging.next');

        return $next === null ? null : (string) $next;
    }

    public function hasNext(): bool
    {
        return $this->nextUrl() !== null;
    }
}
