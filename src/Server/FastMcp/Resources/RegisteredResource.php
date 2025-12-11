<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\FastMcp\Resources;

use Closure;
use Dtyq\PhpMcp\Shared\Exceptions\ResourceError;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Shared\Utilities\StaticMethodCall;
use Dtyq\PhpMcp\Types\Content\Annotations;
use Dtyq\PhpMcp\Types\Resources\Resource;
use Dtyq\PhpMcp\Types\Resources\ResourceContents;
use Dtyq\PhpMcp\Types\Resources\TextResourceContents;
use Exception;
use Opis\Closure\SerializableClosure;

/**
 * Registered resource definition and access class.
 *
 * Stores resource metadata and handles resource content retrieval.
 *
 * Supports two execution modes:
 * 1. Closure/Callable mode: Traditional closure-based execution
 * 2. Static method mode: Serialization-safe static method call
 *
 * For HTTP transport with session persistence, prefer static method mode
 * to avoid closure serialization issues with complex objects.
 */
class RegisteredResource
{
    private Resource $resource;

    /** @var null|Closure|SerializableClosure The function to execute for resource access */
    private $callable;

    /** @var null|StaticMethodCall Static method call for serialization-safe execution */
    private ?StaticMethodCall $staticMethod;

    /**
     * Create a registered resource with either a callable or static method.
     *
     * Either $callable or $staticMethod must be provided, but not both empty.
     * If both are provided, staticMethod takes precedence during execution.
     *
     * @param resource $resource Resource metadata
     * @param null|Closure|SerializableClosure $callable The function to execute
     * @param null|StaticMethodCall $staticMethod Static method call for serialization-safe execution
     * @throws ValidationError If neither callable nor staticMethod is provided
     */
    public function __construct(Resource $resource, $callable = null, ?StaticMethodCall $staticMethod = null)
    {
        if ($callable === null && $staticMethod === null) {
            throw ValidationError::invalidFieldValue(
                'callable/staticMethod',
                'null',
                'Either callable or staticMethod must be provided'
            );
        }

        $this->resource = $resource;
        $this->callable = $callable;
        $this->staticMethod = $staticMethod;
    }

    /**
     * Create a registered resource with a callable.
     *
     * @param resource $resource Resource metadata
     * @param Closure|SerializableClosure $callable The function to execute
     */
    public static function withCallable(Resource $resource, $callable): self
    {
        return new self($resource, $callable, null);
    }

    /**
     * Create a registered resource with a static method.
     *
     * @param resource $resource Resource metadata
     * @param StaticMethodCall $staticMethod Static method call
     */
    public static function withStaticMethod(Resource $resource, StaticMethodCall $staticMethod): self
    {
        return new self($resource, null, $staticMethod);
    }

    /**
     * Access the resource content.
     *
     * If staticMethod is set, it takes precedence over callable.
     */
    public function getContent(): ResourceContents
    {
        try {
            // Prefer static method if available (serialization-safe)
            if ($this->staticMethod !== null) {
                $result = $this->staticMethod->invoke(['uri' => $this->resource->getUri()]);
            } elseif ($this->callable !== null) {
                // Execute the callable
                $result = call_user_func($this->callable, $this->resource->getUri());
            } else {
                // This should never happen due to constructor validation
                throw ResourceError::accessFailed(
                    $this->resource->getUri(),
                    new Exception('No callable or static method configured')
                );
            }

            if (is_array($result)) {
                $result = json_encode($result, JSON_UNESCAPED_UNICODE);
            }
            if (is_string($result)) {
                $result = new TextResourceContents($this->resource->getUri(), $result);
            }

            // Ensure result is ResourceContents
            if (! $result instanceof ResourceContents) {
                throw ResourceError::accessFailed(
                    $this->resource->getUri(),
                    new Exception('Resource callable must return ResourceContents instance')
                );
            }

            return $result;
        } catch (Exception $e) {
            if ($e instanceof ResourceError) {
                throw $e;
            }
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

    /**
     * Check if this resource uses static method mode.
     */
    public function hasStaticMethod(): bool
    {
        return $this->staticMethod !== null;
    }

    /**
     * Check if this resource uses callable mode.
     */
    public function hasCallable(): bool
    {
        return $this->callable !== null;
    }

    /**
     * Get the static method call (if set).
     */
    public function getStaticMethod(): ?StaticMethodCall
    {
        return $this->staticMethod;
    }
}
