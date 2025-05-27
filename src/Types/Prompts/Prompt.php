<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Prompts;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Core\BaseTypes;

/**
 * A prompt template that can be invoked by clients.
 *
 * Prompts are user-controlled templates that provide standardized ways
 * to interact with LLMs. They can accept arguments and generate
 * contextual messages for LLM interactions.
 */
class Prompt
{
    /** @var string Unique identifier for the prompt */
    private string $name;

    /** @var null|string Human-readable description of the prompt */
    private ?string $description;

    /** @var array<PromptArgument> List of arguments this prompt accepts */
    private array $arguments;

    /**
     * @param array<PromptArgument> $arguments
     */
    public function __construct(string $name, ?string $description = null, array $arguments = [])
    {
        $this->setName($name);
        $this->setDescription($description);
        $this->setArguments($arguments);
    }

    /**
     * Create from array representation.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['name'])) {
            throw ValidationError::requiredFieldMissing('name', 'Prompt');
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

        $arguments = [];
        if (isset($data['arguments'])) {
            if (! is_array($data['arguments'])) {
                throw ValidationError::invalidFieldType('arguments', 'array', gettype($data['arguments']));
            }

            foreach ($data['arguments'] as $index => $argumentData) {
                if (! is_array($argumentData)) {
                    throw ValidationError::invalidFieldType("arguments[{$index}]", 'array', gettype($argumentData));
                }
                $arguments[] = PromptArgument::fromArray($argumentData);
            }
        }

        return new self($data['name'], $description, $arguments);
    }

    /**
     * Get the prompt name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the prompt name.
     */
    public function setName(string $name): void
    {
        if (empty(trim($name))) {
            throw ValidationError::emptyField('name');
        }
        $this->name = BaseTypes::sanitizeText($name);
    }

    /**
     * Get the prompt description.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set the prompt description.
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
     * Get the prompt arguments.
     *
     * @return array<PromptArgument>
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Set the prompt arguments.
     *
     * @param array<PromptArgument> $arguments
     */
    public function setArguments(array $arguments): void
    {
        foreach ($arguments as $index => $argument) {
            if (! $argument instanceof PromptArgument) {
                throw ValidationError::invalidFieldType("arguments[{$index}]", 'PromptArgument', gettype($argument));
            }
        }
        $this->arguments = $arguments;
    }

    /**
     * Add an argument to the prompt.
     */
    public function addArgument(PromptArgument $argument): void
    {
        $this->arguments[] = $argument;
    }

    /**
     * Remove an argument by name.
     */
    public function removeArgument(string $name): bool
    {
        foreach ($this->arguments as $index => $argument) {
            if ($argument->getName() === $name) {
                unset($this->arguments[$index]);
                $this->arguments = array_values($this->arguments); // Re-index
                return true;
            }
        }
        return false;
    }

    /**
     * Get an argument by name.
     */
    public function getArgument(string $name): ?PromptArgument
    {
        foreach ($this->arguments as $argument) {
            if ($argument->getName() === $name) {
                return $argument;
            }
        }
        return null;
    }

    /**
     * Check if prompt has a description.
     */
    public function hasDescription(): bool
    {
        return $this->description !== null;
    }

    /**
     * Check if prompt has arguments.
     */
    public function hasArguments(): bool
    {
        return ! empty($this->arguments);
    }

    /**
     * Get the count of arguments.
     */
    public function getArgumentCount(): int
    {
        return count($this->arguments);
    }

    /**
     * Get required arguments.
     *
     * @return array<PromptArgument>
     */
    public function getRequiredArguments(): array
    {
        return array_filter($this->arguments, fn (PromptArgument $arg) => $arg->isRequired());
    }

    /**
     * Get optional arguments.
     *
     * @return array<PromptArgument>
     */
    public function getOptionalArguments(): array
    {
        return array_filter($this->arguments, fn (PromptArgument $arg) => ! $arg->isRequired());
    }

    /**
     * Validate provided arguments against prompt requirements.
     *
     * @param array<string, mixed> $providedArgs
     * @throws ValidationError
     */
    public function validateArguments(array $providedArgs): void
    {
        // Check required arguments
        foreach ($this->getRequiredArguments() as $requiredArg) {
            if (! array_key_exists($requiredArg->getName(), $providedArgs)) {
                throw ValidationError::missingRequiredArgument($requiredArg->getName());
            }
        }

        // Check for unknown arguments
        $validArgNames = array_map(fn (PromptArgument $arg) => $arg->getName(), $this->arguments);
        foreach (array_keys($providedArgs) as $providedArgName) {
            if (! in_array($providedArgName, $validArgNames, true)) {
                throw ValidationError::invalidFieldValue('arguments', "unknown argument '{$providedArgName}'");
            }
        }
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
        ];

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if (! empty($this->arguments)) {
            $data['arguments'] = array_map(fn (PromptArgument $arg) => $arg->toArray(), $this->arguments);
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
        return new self($name, $this->description, $this->arguments);
    }

    /**
     * Create a copy with different description.
     */
    public function withDescription(?string $description): self
    {
        return new self($this->name, $description, $this->arguments);
    }

    /**
     * Create a copy with different arguments.
     *
     * @param array<PromptArgument> $arguments
     */
    public function withArguments(array $arguments): self
    {
        return new self($this->name, $this->description, $arguments);
    }
}
