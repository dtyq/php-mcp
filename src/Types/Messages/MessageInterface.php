<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Messages;

use Dtyq\PhpMcp\Types\Content\ContentInterface;

/**
 * Base interface for MCP messages.
 *
 * Defines the common contract for messages that can be exchanged
 * between clients and servers in the MCP protocol.
 */
interface MessageInterface
{
    /**
     * Get the message role.
     */
    public function getRole(): string;

    /**
     * Get the message content.
     */
    public function getContent(): ContentInterface;

    /**
     * Set the message content.
     */
    public function setContent(ContentInterface $content): void;

    /**
     * Check if content is targeted to a specific role.
     */
    public function isTargetedTo(string $role): bool;

    /**
     * Get content priority (0.0 to 1.0).
     */
    public function getPriority(): ?float;

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * Convert to JSON string.
     */
    public function toJson(): string;
}
