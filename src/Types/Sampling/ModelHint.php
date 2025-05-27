<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Sampling;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Shared\Utilities\JsonUtils;

/**
 * Represents a model hint for sampling requests.
 *
 * Model hints provide suggestions to clients about which models to use
 * for sampling. Clients can use these hints to select appropriate models
 * from their available options.
 */
class ModelHint
{
    private string $name;

    /**
     * Create a new model hint.
     *
     * @param string $name The model name or pattern
     * @throws ValidationError If name is invalid
     */
    public function __construct(string $name)
    {
        $this->setName($name);
    }

    /**
     * String representation.
     */
    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * Create a hint from array data.
     *
     * @param array<string, mixed> $data The hint data
     * @throws ValidationError If data is invalid
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['name'])) {
            throw ValidationError::requiredFieldMissing('name');
        }

        if (! is_string($data['name'])) {
            throw ValidationError::invalidFieldType('name', 'string', gettype($data['name']));
        }

        return new self($data['name']);
    }

    /**
     * Get the model name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the model name.
     *
     * @param string $name The model name
     * @throws ValidationError If name is invalid
     */
    public function setName(string $name): void
    {
        if (empty($name)) {
            throw ValidationError::emptyField('name');
        }

        $this->name = $name;
    }

    /**
     * Create a new hint with a different name.
     *
     * @param string $name The new name
     */
    public function withName(string $name): self
    {
        $new = clone $this;
        $new->setName($name);
        return $new;
    }

    /**
     * Check if this hint matches a model name.
     *
     * @param string $modelName The model name to check
     */
    public function matches(string $modelName): bool
    {
        // Simple case-insensitive substring matching
        return stripos($modelName, $this->name) !== false;
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
        ];
    }

    /**
     * Convert to JSON string.
     */
    public function toJson(): string
    {
        return JsonUtils::encode($this->toArray());
    }
}
