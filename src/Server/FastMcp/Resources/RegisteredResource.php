<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\FastMcp\Resources;

use Closure;
use Dtyq\PhpMcp\Shared\Exceptions\ResourceError;
use Dtyq\PhpMcp\Types\Content\Annotations;
use Dtyq\PhpMcp\Types\Resources\Resource;
use Dtyq\PhpMcp\Types\Resources\ResourceContents;
use Exception;

/**
 * Registered resource definition and access class.
 *
 * Stores resource metadata and handles resource content retrieval.
 */
class RegisteredResource
{
    private Resource $resource;

    /** @var Closure The function to execute for resource access */
    private Closure $callable;

    public function __construct(Resource $resource, Closure $callable)
    {
        $this->resource = $resource;
        $this->callable = $callable;
    }

    /**
     * Access the resource content.
     */
    public function getContent(): ResourceContents
    {
        try {
            // Execute the callable
            $result = call_user_func($this->callable, $this->resource->getUri());

            // Ensure result is ResourceContents
            if (! $result instanceof ResourceContents) {
                throw ResourceError::accessFailed(
                    $this->resource->getUri(),
                    new Exception('Resource callable must return ResourceContents instance')
                );
            }

            return $result;
        } catch (Exception $e) {
            throw ResourceError::accessFailed($this->resource->getUri(), $e);
        }
    }

    /**
     * Get resource metadata.
     */
    public function getResource(): Resource
    {
        return $this->resource;
    }

    /**
     * Get resource URI.
     */
    public function getUri(): string
    {
        return $this->resource->getUri();
    }

    /**
     * Get resource name.
     */
    public function getName(): string
    {
        return $this->resource->getName();
    }

    /**
     * Get resource description.
     */
    public function getDescription(): ?string
    {
        return $this->resource->getDescription();
    }

    /**
     * Get resource MIME type.
     */
    public function getMimeType(): ?string
    {
        return $this->resource->getMimeType();
    }

    /**
     * Get resource size.
     */
    public function getSize(): ?int
    {
        return $this->resource->getSize();
    }

    /**
     * Get resource annotations.
     */
    public function getAnnotations(): ?Annotations
    {
        return $this->resource->getAnnotations();
    }

    /**
     * Check if resource has a description.
     */
    public function hasDescription(): bool
    {
        return $this->resource->hasDescription();
    }

    /**
     * Check if resource has a MIME type.
     */
    public function hasMimeType(): bool
    {
        return $this->resource->hasMimeType();
    }

    /**
     * Check if resource has size information.
     */
    public function hasSize(): bool
    {
        return $this->resource->hasSize();
    }

    /**
     * Check if resource has annotations.
     */
    public function hasAnnotations(): bool
    {
        return $this->resource->hasAnnotations();
    }
}
