<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Content;

use Dtyq\PhpMcp\Types\Core\ProtocolConstants;
use Dtyq\PhpMcp\Types\Resources\ResourceContents;
use InvalidArgumentException;

/**
 * Embedded resource content for MCP messages.
 *
 * Represents a resource that is embedded directly into a prompt or tool call result.
 * It is up to the client how best to render embedded resources for the benefit
 * of the LLM and/or the user.
 */
class EmbeddedResource implements ContentInterface
{
    /** @var string Content type identifier */
    private string $type = ProtocolConstants::CONTENT_TYPE_RESOURCE;

    /** @var ResourceContents The embedded resource contents */
    private ResourceContents $resource;

    /** @var null|Annotations Content annotations */
    private ?Annotations $annotations;

    public function __construct(ResourceContents $resource, ?Annotations $annotations = null)
    {
        $this->resource = $resource;
        $this->annotations = $annotations;
    }

    /**
     * Create from array representation.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['type']) || $data['type'] !== ProtocolConstants::CONTENT_TYPE_RESOURCE) {
            throw new InvalidArgumentException('Invalid content type for EmbeddedResource');
        }

        if (! isset($data['resource'])) {
            throw new InvalidArgumentException('Resource field is required for EmbeddedResource');
        }

        if (! is_array($data['resource'])) {
            throw new InvalidArgumentException('Resource field must be an array');
        }

        $resource = ResourceContents::fromArray($data['resource']);

        $annotations = null;
        if (isset($data['annotations']) && is_array($data['annotations'])) {
            $annotations = Annotations::fromArray($data['annotations']);
        }

        return new self($resource, $annotations);
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the embedded resource contents.
     */
    public function getResource(): ResourceContents
    {
        return $this->resource;
    }

    /**
     * Set the embedded resource contents.
     */
    public function setResource(ResourceContents $resource): void
    {
        $this->resource = $resource;
    }

    public function getAnnotations(): ?Annotations
    {
        return $this->annotations;
    }

    public function setAnnotations(?Annotations $annotations): void
    {
        $this->annotations = $annotations;
    }

    public function hasAnnotations(): bool
    {
        return $this->annotations !== null && ! $this->annotations->isEmpty();
    }

    public function isTargetedTo(string $role): bool
    {
        if (! $this->hasAnnotations()) {
            return true;
        }

        return $this->annotations->isTargetedTo($role);
    }

    public function getPriority(): ?float
    {
        if (! $this->hasAnnotations()) {
            return null;
        }

        return $this->annotations->getPriority();
    }

    /**
     * Get the URI of the embedded resource.
     */
    public function getUri(): string
    {
        return $this->resource->getUri();
    }

    /**
     * Get the MIME type of the embedded resource.
     */
    public function getMimeType(): ?string
    {
        return $this->resource->getMimeType();
    }

    /**
     * Check if the embedded resource is text content.
     */
    public function isTextResource(): bool
    {
        return $this->resource->isText();
    }

    /**
     * Check if the embedded resource is binary content.
     */
    public function isBlobResource(): bool
    {
        return $this->resource->isBlob();
    }

    /**
     * Get the text content if this is a text resource.
     */
    public function getText(): ?string
    {
        return $this->resource->getText();
    }

    /**
     * Get the blob data if this is a blob resource.
     */
    public function getBlob(): ?string
    {
        return $this->resource->getBlob();
    }

    /**
     * Get the estimated size of the resource content.
     */
    public function getEstimatedSize(): int
    {
        if ($this->isTextResource()) {
            $text = $this->getText();
            return $text ? strlen($text) : 0;
        }

        if ($this->isBlobResource()) {
            $blob = $this->getBlob();
            return $blob ? (int) (strlen($blob) * 0.75) : 0; // Base64 decoded size
        }

        return 0;
    }

    public function toArray(): array
    {
        $data = [
            'type' => $this->type,
            'resource' => $this->resource->toArray(),
        ];

        if ($this->hasAnnotations()) {
            $data['annotations'] = $this->annotations->toArray();
        }

        return $data;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Create a copy with different resource.
     */
    public function withResource(ResourceContents $resource): self
    {
        return new self($resource, $this->annotations);
    }

    /**
     * Create a copy with different annotations.
     */
    public function withAnnotations(?Annotations $annotations): self
    {
        return new self($this->resource, $annotations);
    }
}
