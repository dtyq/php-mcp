<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Responses;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Core\ResultInterface;

/**
 * Result for argument autocompletion suggestions.
 *
 * Contains a list of completion suggestions for prompt or resource template arguments.
 * This is part of the completions capability introduced in MCP 2025-03-26.
 */
class CompleteResult implements ResultInterface
{
    /** @var array<string> */
    private array $completion;

    /** @var null|array<string, mixed> */
    private ?array $meta = null;

    /**
     * @param array<string> $completion List of completion suggestions
     * @param null|array<string, mixed> $meta Optional meta information
     */
    public function __construct(array $completion, ?array $meta = null)
    {
        $this->setCompletion($completion);
        $this->meta = $meta;
    }

    /** @return array<string> */
    public function getCompletion(): array
    {
        return $this->completion;
    }

    /** @param array<string> $completion */
    public function setCompletion(array $completion): void
    {
        // Validate that all items are strings
        foreach ($completion as $index => $item) {
            if (! is_string($item)) {
                throw ValidationError::invalidFieldType(
                    "completion[{$index}]",
                    'string',
                    gettype($item)
                );
            }
        }
        $this->completion = $completion;
    }

    /**
     * Add a completion suggestion.
     */
    public function addCompletion(string $suggestion): void
    {
        $this->completion[] = $suggestion;
    }

    /**
     * Get the number of completion suggestions.
     */
    public function getCompletionCount(): int
    {
        return count($this->completion);
    }

    /**
     * Check if there are any completion suggestions.
     */
    public function hasCompletions(): bool
    {
        return ! empty($this->completion);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $array = [
            'completion' => $this->completion,
        ];

        if ($this->meta !== null) {
            $array['_meta'] = $this->meta;
        }

        return $array;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    public function hasMeta(): bool
    {
        return $this->meta !== null;
    }

    /** @return null|array<string, mixed> */
    public function getMeta(): ?array
    {
        return $this->meta;
    }

    /** @param null|array<string, mixed> $meta */
    public function setMeta(?array $meta): void
    {
        $this->meta = $meta;
    }

    public function isPaginated(): bool
    {
        return false; // Completion results are not paginated
    }

    public function getNextCursor(): ?string
    {
        return null; // Completion results don't use pagination
    }

    /**
     * Create from array data.
     *
     * @param array<string, mixed> $data
     * @throws ValidationError
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['completion'])) {
            throw ValidationError::requiredFieldMissing('completion', 'CompleteResult');
        }

        if (! is_array($data['completion'])) {
            throw ValidationError::invalidFieldType('completion', 'array', gettype($data['completion']));
        }

        return new self(
            $data['completion'],
            $data['_meta'] ?? null
        );
    }
}
