<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Core;

/**
 * Base interface for all MCP request types.
 *
 * Represents a JSON-RPC request that expects a response.
 * Implementations should define specific method names and parameter types.
 */
interface RequestInterface
{
    /**
     * Get the JSON-RPC method name.
     */
    public function getMethod(): string;

    /**
     * Get the request parameters.
     *
     * @return null|array<string, mixed>
     */
    public function getParams(): ?array;

    /**
     * Get the request ID for correlation.
     *
     * @return int|string
     */
    public function getId();

    /**
     * Set the request ID.
     *
     * @param int|string $id
     */
    public function setId($id): void;

    /**
     * Convert to JSON-RPC 2.0 format.
     *
     * @return array<string, mixed>
     */
    public function toJsonRpc(): array;

    /**
     * Check if this request has a progress token.
     */
    public function hasProgressToken(): bool;

    /**
     * Get the progress token if available.
     *
     * @return null|int|string
     */
    public function getProgressToken();
}
