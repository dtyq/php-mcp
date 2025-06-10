<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Core;

/**
 * Interface for JSON-RPC 2.0 responses.
 *
 * This interface represents both successful responses (with result)
 * and error responses (with error information).
 */
interface JsonRpcResponseInterface
{
    /**
     * Get the response ID.
     *
     * @return int|string
     */
    public function getId();

    /**
     * Check if this is an error response.
     */
    public function isError(): bool;

    /**
     * Check if this response matches a request ID.
     *
     * @param int|string $requestId
     */
    public function matchesRequest($requestId): bool;

    /**
     * Convert to JSON-RPC 2.0 format.
     *
     * @return array<string, mixed>
     */
    public function toJsonRpc(): array;

    /**
     * Convert to JSON string.
     */
    public function toJson(): string;
}
