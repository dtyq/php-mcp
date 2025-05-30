<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Tools;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Core\BaseTypes;
use stdClass;

/**
 * Definition for a tool the client can call.
 *
 * Represents a tool that can be invoked by clients, including its name,
 * description, input schema, and optional annotations.
 */
class Tool
{
    /** @var string The name of the tool */
    private string $name;

    /** @var null|string A human-readable description of the tool */
    private ?string $description;

    /** @var array<string, mixed> A JSON Schema object defining the expected parameters */
    private array $inputSchema;

    /** @var null|ToolAnnotations Optional additional tool information */
    private ?ToolAnnotations $annotations;

    /**
     * @param array<string, mixed> $inputSchema
     */
    public function __construct(
        string $name,
        array $inputSchema,
        ?string $description = null,
        ?ToolAnnotations $annotations = null
    ) {
        $this->setName($name);
        $this->setInputSchema($inputSchema);
        $this->setDescription($description);
        $this->annotations = $annotations;
    }

    /**
     * Create from array representation.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['name'])) {
            throw ValidationError::requiredFieldMissing('name', 'Tool');
        }

        if (! is_string($data['name'])) {
            throw ValidationError::invalidFieldType('name', 'string', gettype($data['name']));
        }

        if (! isset($data['inputSchema'])) {
            throw ValidationError::requiredFieldMissing('inputSchema', 'Tool');
        }

        if (! is_array($data['inputSchema'])) {
            throw ValidationError::invalidFieldType('inputSchema', 'array', gettype($data['inputSchema']));
        }

        $description = null;
        if (isset($data['description'])) {
            if (! is_string($data['description'])) {
                throw ValidationError::invalidFieldType('description', 'string', gettype($data['description']));
            }
            $description = $data['description'];
        }

        $annotations = null;
        if (isset($data['annotations']) && is_array($data['annotations'])) {
            $annotations = ToolAnnotations::fromArray($data['annotations']);
        }

        return new self(
            $data['name'],
            $data['inputSchema'],
            $description,
            $annotations
        );
    }

    /**
     * Get the name of the tool.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name of the tool.
     */
    public function setName(string $name): void
    {
        if (empty(trim($name))) {
            throw ValidationError::emptyField('name');
        }
        $this->name = BaseTypes::sanitizeText($name);
    }

    /**
     * Get the description of the tool.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set the description of the tool.
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
     * Get the input schema.
     *
     * @return array<string, mixed>
     */
    public function getInputSchema(): array
    {
        return $this->inputSchema;
    }

    /**
     * Set the input schema.
     *
     * @param array<string, mixed> $inputSchema
     */
    public function setInputSchema(array $inputSchema): void
    {
        if (empty($inputSchema)) {
            throw ValidationError::emptyField('inputSchema');
        }
        $this->inputSchema = $this->normalizeJsonSchema($inputSchema);
    }

    /**
     * Get the tool annotations.
     */
    public function getAnnotations(): ?ToolAnnotations
    {
        return $this->annotations;
    }

    /**
     * Set the tool annotations.
     */
    public function setAnnotations(?ToolAnnotations $annotations): void
    {
        $this->annotations = $annotations;
    }

    /**
     * Check if tool has a description.
     */
    public function hasDescription(): bool
    {
        return $this->description !== null;
    }

    /**
     * Check if tool has annotations.
     */
    public function hasAnnotations(): bool
    {
        return $this->annotations !== null && ! $this->annotations->isEmpty();
    }

    /**
     * Get the tool title (from annotations or name).
     */
    public function getTitle(): string
    {
        if ($this->hasAnnotations() && $this->annotations->hasTitle()) {
            return $this->annotations->getTitle();
        }
        return $this->name;
    }

    /**
     * Check if the tool is read-only.
     */
    public function isReadOnly(): bool
    {
        if (! $this->hasAnnotations()) {
            return false;
        }
        return $this->annotations->isReadOnly();
    }

    /**
     * Check if the tool is destructive.
     */
    public function isDestructive(): bool
    {
        if (! $this->hasAnnotations()) {
            return true; // Default behavior
        }
        return $this->annotations->isDestructive();
    }

    /**
     * Check if the tool is idempotent.
     */
    public function isIdempotent(): bool
    {
        if (! $this->hasAnnotations()) {
            return false; // Default behavior
        }
        return $this->annotations->isIdempotent();
    }

    /**
     * Check if the tool operates in an open world.
     */
    public function isOpenWorld(): bool
    {
        if (! $this->hasAnnotations()) {
            return true; // Default behavior
        }
        return $this->annotations->isOpenWorld();
    }

    /**
     * Validate input arguments against the schema.
     *
     * @param array<string, mixed> $arguments
     */
    public function validateArguments(array $arguments): bool
    {
        // Basic validation - check required properties
        if (isset($this->inputSchema['required']) && is_array($this->inputSchema['required'])) {
            foreach ($this->inputSchema['required'] as $required) {
                if (! isset($arguments[$required])) {
                    throw ValidationError::missingRequiredArgument($required);
                }
            }
        }

        // Check properties types if defined
        if (isset($this->inputSchema['properties']) && is_array($this->inputSchema['properties'])) {
            foreach ($arguments as $key => $value) {
                if (! isset($this->inputSchema['properties'][$key])) {
                    continue; // Allow additional properties by default
                }

                $property = $this->inputSchema['properties'][$key];
                if (isset($property['type'])) {
                    $this->validateArgumentType($key, $value, $property['type']);
                }
            }
        }

        return true;
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
            'inputSchema' => $this->inputSchema,
        ];

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->hasAnnotations()) {
            $data['annotations'] = $this->annotations->toArray();
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
        return new self(
            $name,
            $this->inputSchema,
            $this->description,
            $this->annotations
        );
    }

    /**
     * Create a copy with different input schema.
     *
     * @param array<string, mixed> $inputSchema
     */
    public function withInputSchema(array $inputSchema): self
    {
        return new self(
            $this->name,
            $inputSchema,
            $this->description,
            $this->annotations
        );
    }

    /**
     * Create a copy with different description.
     */
    public function withDescription(?string $description): self
    {
        return new self(
            $this->name,
            $this->inputSchema,
            $description,
            $this->annotations
        );
    }

    /**
     * Create a copy with different annotations.
     */
    public function withAnnotations(?ToolAnnotations $annotations): self
    {
        return new self(
            $this->name,
            $this->inputSchema,
            $this->description,
            $annotations
        );
    }

    /**
     * Normalize JSON schema by converting null properties to stdClass objects
     * This ensures MCP compatibility as MCP doesn't allow null values for properties.
     */
    private function normalizeJsonSchema(array $schema): array
    {
        $normalized = [];

        foreach ($schema as $key => $value) {
            if ($key === 'properties' && ($value === null || (is_array($value) && empty($value)))) {
                // Convert null properties or empty array to empty stdClass (serializes as {} in JSON)
                $normalized[$key] = new stdClass();
            } elseif (is_array($value)) {
                // Recursively normalize nested arrays
                $normalized[$key] = $this->normalizeJsonSchema($value);
            } else {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /**
     * Validate argument type.
     *
     * @param mixed $value
     */
    private function validateArgumentType(string $key, $value, string $expectedType): void
    {
        $actualType = gettype($value);

        switch ($expectedType) {
            case 'string':
                if (! is_string($value)) {
                    throw ValidationError::invalidArgumentType($key, 'string', $actualType);
                }
                break;
            case 'integer':
                if (! is_int($value)) {
                    throw ValidationError::invalidArgumentType($key, 'integer', $actualType);
                }
                break;
            case 'number':
                if (! is_numeric($value)) {
                    throw ValidationError::invalidArgumentType($key, 'number', $actualType);
                }
                break;
            case 'boolean':
                if (! is_bool($value)) {
                    throw ValidationError::invalidArgumentType($key, 'boolean', $actualType);
                }
                break;
            case 'array':
                if (! is_array($value)) {
                    throw ValidationError::invalidArgumentType($key, 'array', $actualType);
                }
                break;
            case 'object':
                if (! is_array($value) && ! is_object($value)) {
                    throw ValidationError::invalidArgumentType($key, 'object', $actualType);
                }
                break;
        }
    }
}
