<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Responses;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Core\ResultInterface;
use Dtyq\PhpMcp\Types\Prompts\Prompt;

/**
 * Result containing a list of prompts available on the server.
 *
 * Supports pagination with cursor-based navigation.
 */
class ListPromptsResult implements ResultInterface
{
    /** @var Prompt[] */
    private array $prompts;

    private ?string $nextCursor = null;

    /** @var null|array<string, mixed> */
    private ?array $meta = null;

    /**
     * @param Prompt[] $prompts Array of prompts
     * @param null|string $nextCursor Next pagination cursor
     * @param null|array<string, mixed> $meta Optional meta information
     */
    public function __construct(array $prompts, ?string $nextCursor = null, ?array $meta = null)
    {
        $this->setPrompts($prompts);
        $this->nextCursor = $nextCursor;
        $this->meta = $meta;
    }

    public function toArray(): array
    {
        $data = [
            'prompts' => array_map(fn (Prompt $prompt) => $prompt->toArray(), $this->prompts),
        ];

        if ($this->nextCursor !== null) {
            $data['nextCursor'] = $this->nextCursor;
        }

        if ($this->meta !== null) {
            $data['_meta'] = $this->meta;
        }

        return $data;
    }

    public function hasMeta(): bool
    {
        return $this->meta !== null;
    }

    public function getMeta(): ?array
    {
        return $this->meta;
    }

    public function setMeta(?array $meta): void
    {
        $this->meta = $meta;
    }

    public function isPaginated(): bool
    {
        return $this->nextCursor !== null;
    }

    public function getNextCursor(): ?string
    {
        return $this->nextCursor;
    }

    public function setNextCursor(?string $cursor): void
    {
        $this->nextCursor = $cursor;
    }

    /**
     * @return Prompt[]
     */
    public function getPrompts(): array
    {
        return $this->prompts;
    }

    /**
     * @param Prompt[] $prompts
     * @throws ValidationError
     */
    public function setPrompts(array $prompts): void
    {
        foreach ($prompts as $index => $prompt) {
            if (! $prompt instanceof Prompt) {
                throw ValidationError::invalidFieldType(
                    "prompts[{$index}]",
                    'Prompt',
                    get_debug_type($prompt)
                );
            }
        }
        $this->prompts = $prompts;
    }

    /**
     * Add a prompt to the list.
     */
    public function addPrompt(Prompt $prompt): void
    {
        $this->prompts[] = $prompt;
    }

    /**
     * Get the number of prompts.
     */
    public function getPromptCount(): int
    {
        return count($this->prompts);
    }

    /**
     * Check if the result is empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->prompts);
    }

    /**
     * Find a prompt by name.
     */
    public function findPromptByName(string $name): ?Prompt
    {
        foreach ($this->prompts as $prompt) {
            if ($prompt->getName() === $name) {
                return $prompt;
            }
        }
        return null;
    }

    /**
     * Create from array data.
     *
     * @param array<string, mixed> $data
     * @throws ValidationError
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['prompts'])) {
            throw ValidationError::requiredFieldMissing('prompts', 'ListPromptsResult');
        }

        if (! is_array($data['prompts'])) {
            throw ValidationError::invalidFieldType('prompts', 'array', gettype($data['prompts']));
        }

        $prompts = [];
        foreach ($data['prompts'] as $index => $promptData) {
            if (! is_array($promptData)) {
                throw ValidationError::invalidFieldType(
                    "prompts[{$index}]",
                    'array',
                    gettype($promptData)
                );
            }
            $prompts[] = Prompt::fromArray($promptData);
        }

        return new self(
            $prompts,
            $data['nextCursor'] ?? null,
            $data['_meta'] ?? null
        );
    }
}
