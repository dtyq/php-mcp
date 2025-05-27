<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Responses;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Core\ResultInterface;
use Dtyq\PhpMcp\Types\Tools\Tool;

/**
 * Result containing a list of tools available on the server.
 *
 * Supports pagination with cursor-based navigation.
 */
class ListToolsResult implements ResultInterface
{
    /** @var Tool[] */
    private array $tools;

    private ?string $nextCursor = null;

    /** @var null|array<string, mixed> */
    private ?array $meta = null;

    /**
     * @param Tool[] $tools Array of tools
     * @param null|string $nextCursor Next pagination cursor
     * @param null|array<string, mixed> $meta Optional meta information
     */
    public function __construct(array $tools, ?string $nextCursor = null, ?array $meta = null)
    {
        $this->setTools($tools);
        $this->nextCursor = $nextCursor;
        $this->meta = $meta;
    }

    public function toArray(): array
    {
        $data = [
            'tools' => array_map(fn (Tool $tool) => $tool->toArray(), $this->tools),
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
     * @return Tool[]
     */
    public function getTools(): array
    {
        return $this->tools;
    }

    /**
     * @param Tool[] $tools
     * @throws ValidationError
     */
    public function setTools(array $tools): void
    {
        foreach ($tools as $index => $tool) {
            if (! $tool instanceof Tool) {
                throw ValidationError::invalidFieldType(
                    "tools[{$index}]",
                    'Tool',
                    get_debug_type($tool)
                );
            }
        }
        $this->tools = $tools;
    }

    /**
     * Add a tool to the list.
     */
    public function addTool(Tool $tool): void
    {
        $this->tools[] = $tool;
    }

    /**
     * Get the number of tools.
     */
    public function getToolCount(): int
    {
        return count($this->tools);
    }

    /**
     * Check if the result is empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->tools);
    }

    /**
     * Find a tool by name.
     */
    public function findToolByName(string $name): ?Tool
    {
        foreach ($this->tools as $tool) {
            if ($tool->getName() === $name) {
                return $tool;
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
        if (! isset($data['tools'])) {
            throw ValidationError::requiredFieldMissing('tools', 'ListToolsResult');
        }

        if (! is_array($data['tools'])) {
            throw ValidationError::invalidFieldType('tools', 'array', gettype($data['tools']));
        }

        $tools = [];
        foreach ($data['tools'] as $index => $toolData) {
            if (! is_array($toolData)) {
                throw ValidationError::invalidFieldType(
                    "tools[{$index}]",
                    'array',
                    gettype($toolData)
                );
            }
            $tools[] = Tool::fromArray($toolData);
        }

        return new self(
            $tools,
            $data['nextCursor'] ?? null,
            $data['_meta'] ?? null
        );
    }
}
