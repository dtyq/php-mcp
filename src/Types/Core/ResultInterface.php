<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Core;

/**
 * Base interface for all MCP result types.
 *
 * Represents the result payload of a successful JSON-RPC response.
 * Results contain the actual data returned by the server.
 */
interface ResultInterface
{
    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * Check if this result has meta information.
     */
    public function hasMeta(): bool;

    /**
     * Get meta information if available.
     *
     * @return null|array<string, mixed>
     */
    public function getMeta(): ?array;

    /**
     * Set meta information.
     *
     * @param null|array<string, mixed> $meta
     */
    public function setMeta(?array $meta): void;

    /**
     * Check if this is a paginated result.
     */
    public function isPaginated(): bool;

    /**
     * Get the next cursor for pagination if available.
     */
    public function getNextCursor(): ?string;
}
