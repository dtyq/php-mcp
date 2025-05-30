<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Responses;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Core\ResultInterface;
use Dtyq\PhpMcp\Types\Resources\ResourceTemplate;

/**
 * Result containing a list of resource templates available on the server.
 *
 * Supports pagination with cursor-based navigation.
 */
class ListResourceTemplatesResult implements ResultInterface
{
    /** @var ResourceTemplate[] */
    private array $resourceTemplates;

    private ?string $nextCursor = null;

    /** @var null|array<string, mixed> */
    private ?array $meta = null;

    /**
     * @param ResourceTemplate[] $resourceTemplates Array of resource templates
     */
    public function __construct(array $resourceTemplates, ?string $nextCursor = null)
    {
        $this->setResourceTemplates($resourceTemplates);
        $this->nextCursor = $nextCursor;
    }

    /**
     * Create from array representation.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $resourceTemplates = [];
        if (isset($data['resourceTemplates'])) {
            if (! is_array($data['resourceTemplates'])) {
                throw ValidationError::invalidFieldType('resourceTemplates', 'array', gettype($data['resourceTemplates']));
            }

            foreach ($data['resourceTemplates'] as $index => $templateData) {
                if (! is_array($templateData)) {
                    throw ValidationError::invalidFieldType("resourceTemplates[{$index}]", 'array', gettype($templateData));
                }
                $resourceTemplates[] = ResourceTemplate::fromArray($templateData);
            }
        }

        $nextCursor = null;
        if (isset($data['nextCursor'])) {
            if (! is_string($data['nextCursor'])) {
                throw ValidationError::invalidFieldType('nextCursor', 'string', gettype($data['nextCursor']));
            }
            $nextCursor = $data['nextCursor'];
        }

        return new self($resourceTemplates, $nextCursor);
    }

    /**
     * Get the resource templates.
     *
     * @return ResourceTemplate[]
     */
    public function getResourceTemplates(): array
    {
        return $this->resourceTemplates;
    }

    /**
     * Set the resource templates.
     *
     * @param ResourceTemplate[] $resourceTemplates
     */
    public function setResourceTemplates(array $resourceTemplates): void
    {
        foreach ($resourceTemplates as $index => $template) {
            if (! $template instanceof ResourceTemplate) {
                throw ValidationError::invalidFieldType("resourceTemplates[{$index}]", 'ResourceTemplate', gettype($template));
            }
        }
        $this->resourceTemplates = $resourceTemplates;
    }

    /**
     * Get the next cursor for pagination.
     */
    public function getNextCursor(): ?string
    {
        return $this->nextCursor;
    }

    /**
     * Set the next cursor for pagination.
     */
    public function setNextCursor(?string $nextCursor): void
    {
        $this->nextCursor = $nextCursor;
    }

    /**
     * Check if this is a paginated result.
     */
    public function isPaginated(): bool
    {
        return $this->nextCursor !== null;
    }

    /**
     * Get the count of resource templates.
     */
    public function getCount(): int
    {
        return count($this->resourceTemplates);
    }

    /**
     * Check if there are any resource templates.
     */
    public function isEmpty(): bool
    {
        return empty($this->resourceTemplates);
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'resourceTemplates' => array_map(fn (ResourceTemplate $template) => $template->toArray(), $this->resourceTemplates),
        ];

        if ($this->nextCursor !== null) {
            $data['nextCursor'] = $this->nextCursor;
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
}
