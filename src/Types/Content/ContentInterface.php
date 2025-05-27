<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Content;

/**
 * Base interface for all MCP content types.
 *
 * Defines the common contract for content that can be included in messages,
 * tool results, and other MCP protocol structures.
 */
interface ContentInterface
{
    /**
     * Get the content type identifier.
     */
    public function getType(): string;

    /**
     * Get the content annotations.
     */
    public function getAnnotations(): ?Annotations;

    /**
     * Set the content annotations.
     */
    public function setAnnotations(?Annotations $annotations): void;

    /**
     * Check if content has annotations.
     */
    public function hasAnnotations(): bool;

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

    /**
     * Check if content is targeted to a specific role.
     */
    public function isTargetedTo(string $role): bool;

    /**
     * Get content priority (0.0 to 1.0).
     */
    public function getPriority(): ?float;
}
