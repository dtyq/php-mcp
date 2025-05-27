<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Prompts;

use Dtyq\PhpMcp\Types\Core\BaseTypes;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;

/**
 * Argument definition for MCP prompts.
 *
 * Defines the structure and requirements for arguments that can be passed
 * to prompt templates when they are invoked by clients.
 */
class PromptArgument
{
    /** @var string Argument identifier */
    private string $name;

    /** @var null|string Human-readable description of the argument */
    private ?string $description;

    /** @var bool Whether this argument is required */
    private bool $required;

    public function __construct(string $name, ?string $description = null, bool $required = false)
    {
        $this->setName($name);
        $this->setDescription($description);
        $this->required = $required;
    }

    /**
     * Create from array representation.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['name'])) {
            throw ValidationError::requiredFieldMissing('name', 'PromptArgument');
        }

        if (! is_string($data['name'])) {
            throw ValidationError::invalidFieldType('name', 'string', gettype($data['name']));
        }

        $description = null;
        if (isset($data['description'])) {
            if (! is_string($data['description'])) {
                throw ValidationError::invalidFieldType('description', 'string', gettype($data['description']));
            }
            $description = $data['description'];
        }

        $required = false;
        if (isset($data['required'])) {
            if (! is_bool($data['required'])) {
                throw ValidationError::invalidFieldType('required', 'boolean', gettype($data['required']));
            }
            $required = $data['required'];
        }

        return new self($data['name'], $description, $required);
    }

    /**
     * Get the argument name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the argument name.
     */
    public function setName(string $name): void
    {
        if (empty(trim($name))) {
            throw ValidationError::emptyField('name');
        }
        $this->name = BaseTypes::sanitizeText($name);
    }

    /**
     * Get the argument description.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set the argument description.
     */
    public function setDescription(?string $description): void
    {
        if ($description !== null) {
            $description = trim($description);
            if (empty($description)) {
                $description = null;
            } else {
                $description = BaseTypes::sanitizeText($description);
            }
        }
        $this->description = $description;
    }

    /**
     * Check if this argument is required.
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * Set whether this argument is required.
     */
    public function setRequired(bool $required): void
    {
        $this->required = $required;
    }

    /**
     * Check if argument has a description.
     */
    public function hasDescription(): bool
    {
        return $this->description !== null;
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'required' => $this->required,
        ];

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        return $data;
    }

    /**
     * Convert to JSON string.
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Create a copy with different name.
     */
    public function withName(string $name): self
    {
        return new self($name, $this->description, $this->required);
    }

    /**
     * Create a copy with different description.
     */
    public function withDescription(?string $description): self
    {
        return new self($this->name, $description, $this->required);
    }

    /**
     * Create a copy with different required flag.
     */
    public function withRequired(bool $required): self
    {
        return new self($this->name, $this->description, $required);
    }
} 