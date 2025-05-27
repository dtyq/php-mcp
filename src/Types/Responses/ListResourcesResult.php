<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Responses;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Core\ResultInterface;
use Dtyq\PhpMcp\Types\Resources\Resource;

/**
 * Result containing a list of resources available on the server.
 *
 * Supports pagination with cursor-based navigation.
 */
class ListResourcesResult implements ResultInterface
{
    /** @var resource[] */
    private array $resources;

    private ?string $nextCursor = null;

    private ?array $meta = null;

    /**
     * @param resource[] $resources Array of resources
     * @param null|string $nextCursor Next pagination cursor
     * @param null|array<string, mixed> $meta Optional meta information
     */
    public function __construct(array $resources, ?string $nextCursor = null, ?array $meta = null)
    {
        $this->setResources($resources);
        $this->nextCursor = $nextCursor;
        $this->meta = $meta;
    }

    public function toArray(): array
    {
        $data = [
            'resources' => array_map(fn (Resource $resource) => $resource->toArray(), $this->resources),
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
     * @return resource[]
     */
    public function getResources(): array
    {
        return $this->resources;
    }

    /**
     * @param resource[] $resources
     * @throws ValidationError
     */
    public function setResources(array $resources): void
    {
        foreach ($resources as $index => $resource) {
            if (! $resource instanceof Resource) {
                throw ValidationError::invalidFieldType(
                    "resources[{$index}]",
                    'Resource',
                    get_class($resource)
                );
            }
        }
        $this->resources = $resources;
    }

    /**
     * Add a resource to the list.
     */
    public function addResource(Resource $resource): void
    {
        $this->resources[] = $resource;
    }

    /**
     * Get the number of resources.
     */
    public function getResourceCount(): int
    {
        return count($this->resources);
    }

    /**
     * Check if the result is empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->resources);
    }

    /**
     * Create from array data.
     *
     * @param array<string, mixed> $data
     * @throws ValidationError
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['resources'])) {
            throw ValidationError::requiredFieldMissing('resources', 'ListResourcesResult');
        }

        if (! is_array($data['resources'])) {
            throw ValidationError::invalidFieldType('resources', 'array', gettype($data['resources']));
        }

        $resources = [];
        foreach ($data['resources'] as $index => $resourceData) {
            if (! is_array($resourceData)) {
                throw ValidationError::invalidFieldType(
                    "resources[{$index}]",
                    'array',
                    gettype($resourceData)
                );
            }
            $resources[] = Resource::fromArray($resourceData);
        }

        return new self(
            $resources,
            $data['nextCursor'] ?? null,
            $data['_meta'] ?? null
        );
    }
}
