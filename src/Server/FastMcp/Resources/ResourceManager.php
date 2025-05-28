<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\FastMcp\Resources;

use Dtyq\PhpMcp\Shared\Exceptions\ResourceError;
use Dtyq\PhpMcp\Types\Resources\Resource;
use Dtyq\PhpMcp\Types\Resources\ResourceContents;

/**
 * Simple resource registration manager.
 *
 * Manages resource registration and access.
 */
class ResourceManager
{
    /** @var array<string, RegisteredResource> Registered resources indexed by URI */
    private array $resources = [];

    /**
     * Register a resource.
     */
    public function register(RegisteredResource $registeredResource): void
    {
        $this->resources[$registeredResource->getUri()] = $registeredResource;
    }

    /**
     * Get a registered resource by URI.
     */
    public function get(string $uri): ?RegisteredResource
    {
        return $this->resources[$uri] ?? null;
    }

    /**
     * Check if resource exists.
     */
    public function has(string $uri): bool
    {
        return isset($this->resources[$uri]);
    }

    /**
     * Remove a resource.
     */
    public function remove(string $uri): bool
    {
        if (isset($this->resources[$uri])) {
            unset($this->resources[$uri]);
            return true;
        }
        return false;
    }

    /**
     * Get all resource URIs.
     *
     * @return array<string>
     */
    public function getUris(): array
    {
        return array_keys($this->resources);
    }

    /**
     * Get all registered resources.
     *
     * @return array<RegisteredResource>
     */
    public function getAll(): array
    {
        return array_values($this->resources);
    }

    /**
     * Get resource count.
     */
    public function count(): int
    {
        return count($this->resources);
    }

    /**
     * Clear all resources.
     */
    public function clear(): void
    {
        $this->resources = [];
    }

    /**
     * Access a resource by URI.
     */
    public function getContent(string $uri): ResourceContents
    {
        $registeredResource = $this->get($uri);
        if ($registeredResource === null) {
            throw ResourceError::unknownResource($uri);
        }

        return $registeredResource->getContent();
    }

    /**
     * Find resources by pattern matching against URI.
     *
     * @return array<RegisteredResource>
     */
    public function findByPattern(string $pattern): array
    {
        $matches = [];
        foreach ($this->resources as $uri => $resource) {
            if (fnmatch($pattern, $uri)) {
                $matches[] = $resource;
            }
        }
        return $matches;
    }

    /**
     * Find resources by MIME type.
     *
     * @return array<RegisteredResource>
     */
    public function findByMimeType(string $mimeType): array
    {
        $matches = [];
        foreach ($this->resources as $resource) {
            if ($resource->getMimeType() === $mimeType) {
                $matches[] = $resource;
            }
        }
        return $matches;
    }

    /**
     * Get all resource metadata without content.
     *
     * @return array<\Dtyq\PhpMcp\Types\Resources\Resource>
     */
    public function getResourceMetadata(): array
    {
        return array_map(
            fn (RegisteredResource $registered) => $registered->getResource(),
            $this->resources
        );
    }
}
