<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Core;

/**
 * Base interface for all MCP notification types.
 *
 * Represents a JSON-RPC notification that does not expect a response.
 * Notifications are used for event signaling and status updates.
 */
interface NotificationInterface
{
    /**
     * Get the JSON-RPC method name.
     */
    public function getMethod(): string;

    /**
     * Get the notification parameters.
     *
     * @return null|array<string, mixed>
     */
    public function getParams(): ?array;

    /**
     * Convert to JSON-RPC 2.0 notification format.
     *
     * @return array<string, mixed>
     */
    public function toJsonRpc(): array;

    /**
     * Check if this notification has meta information.
     */
    public function hasMeta(): bool;

    /**
     * Get meta information if available.
     *
     * @return null|array<string, mixed>
     */
    public function getMeta(): ?array;
}
