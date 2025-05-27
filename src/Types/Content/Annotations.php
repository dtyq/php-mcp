<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Content;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Core\BaseTypes;

/**
 * Annotations for MCP content.
 *
 * Provides metadata about content including audience targeting and priority.
 * Used to help clients determine how to handle and display content.
 */
class Annotations
{
    /** @var null|array<string> Target audience roles */
    private ?array $audience;

    /** @var null|float Priority value between 0.0 and 1.0 */
    private ?float $priority;

    /**
     * @param null|array<string> $audience
     */
    public function __construct(?array $audience = null, ?float $priority = null)
    {
        $this->setAudience($audience);
        $this->setPriority($priority);
    }

    /**
     * Create from array representation.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $audience = null;
        if (isset($data['audience'])) {
            if (! is_array($data['audience'])) {
                throw ValidationError::invalidFieldType('audience', 'array', gettype($data['audience']));
            }
            $audience = $data['audience'];
        }

        $priority = null;
        if (isset($data['priority'])) {
            if (! is_float($data['priority']) && ! is_int($data['priority'])) {
                throw ValidationError::invalidFieldType('priority', 'number', gettype($data['priority']));
            }
            $priority = (float) $data['priority'];
        }

        return new self($audience, $priority);
    }

    /**
     * Get the target audience.
     *
     * @return null|array<string>
     */
    public function getAudience(): ?array
    {
        return $this->audience;
    }

    /**
     * Set the target audience.
     *
     * @param null|array<string> $audience
     */
    public function setAudience(?array $audience): void
    {
        if ($audience !== null) {
            foreach ($audience as $role) {
                if (! is_string($role)) {
                    throw ValidationError::invalidFieldValue('audience', 'all roles must be strings');
                }
                BaseTypes::validateRole($role);
            }
        }
        $this->audience = $audience;
    }

    /**
     * Get the priority.
     */
    public function getPriority(): ?float
    {
        return $this->priority;
    }

    /**
     * Set the priority.
     */
    public function setPriority(?float $priority): void
    {
        BaseTypes::validatePriority($priority);
        $this->priority = $priority;
    }

    /**
     * Check if annotations have audience targeting.
     */
    public function hasAudience(): bool
    {
        return $this->audience !== null && ! empty($this->audience);
    }

    /**
     * Check if annotations have priority.
     */
    public function hasPriority(): bool
    {
        return $this->priority !== null;
    }

    /**
     * Check if content is targeted to a specific role.
     */
    public function isTargetedTo(string $role): bool
    {
        if (! $this->hasAudience()) {
            return true; // No targeting means available to all
        }

        return in_array($role, $this->audience, true);
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->audience !== null) {
            $data['audience'] = $this->audience;
        }

        if ($this->priority !== null) {
            $data['priority'] = $this->priority;
        }

        return $data;
    }

    /**
     * Convert to JSON string.
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_SLASHES);
    }

    /**
     * Check if annotations are empty.
     */
    public function isEmpty(): bool
    {
        return ! $this->hasAudience() && ! $this->hasPriority();
    }
}
