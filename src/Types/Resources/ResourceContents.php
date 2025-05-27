<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Resources;

use Dtyq\PhpMcp\Types\Core\BaseTypes;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;

/**
 * Base class for resource contents.
 *
 * Represents the contents of a specific resource or sub-resource.
 * Can be either text or binary (blob) content.
 */
abstract class ResourceContents
{
    /** @var string The URI of this resource */
    protected string $uri;

    /** @var null|string The MIME type of this resource */
    protected ?string $mimeType;

    public function __construct(string $uri, ?string $mimeType = null)
    {
        $this->setUri($uri);
        $this->setMimeType($mimeType);
    }

    /**
     * Create from array representation.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['uri'])) {
            throw ValidationError::requiredFieldMissing('uri', 'ResourceContents');
        }

        if (! is_string($data['uri'])) {
            throw ValidationError::invalidFieldType('uri', 'string', gettype($data['uri']));
        }

        $mimeType = null;
        if (isset($data['mimeType'])) {
            if (! is_string($data['mimeType'])) {
                throw ValidationError::invalidFieldType('mimeType', 'string', gettype($data['mimeType']));
            }
            $mimeType = $data['mimeType'];
        }

        // Determine the type based on available fields
        if (isset($data['text'])) {
            if (! is_string($data['text'])) {
                throw ValidationError::invalidFieldType('text', 'string', gettype($data['text']));
            }
            return new TextResourceContents($data['uri'], $data['text'], $mimeType);
        }

        if (isset($data['blob'])) {
            if (! is_string($data['blob'])) {
                throw ValidationError::invalidFieldType('blob', 'string', gettype($data['blob']));
            }
            return new BlobResourceContents($data['uri'], $data['blob'], $mimeType);
        }

        throw ValidationError::invalidFieldValue('content', 'must have either text or blob field');
    }

    /**
     * Get the URI of this resource.
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Set the URI of this resource.
     */
    public function setUri(string $uri): void
    {
        BaseTypes::validateUri($uri);
        $this->uri = $uri;
    }

    /**
     * Get the MIME type of this resource.
     */
    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    /**
     * Set the MIME type of this resource.
     */
    public function setMimeType(?string $mimeType): void
    {
        BaseTypes::validateMimeType($mimeType);
        $this->mimeType = $mimeType;
    }

    /**
     * Check if this is text content.
     */
    abstract public function isText(): bool;

    /**
     * Check if this is blob content.
     */
    abstract public function isBlob(): bool;

    /**
     * Get text content if available.
     */
    abstract public function getText(): ?string;

    /**
     * Get blob content if available.
     */
    abstract public function getBlob(): ?string;

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;

    /**
     * Convert to JSON string.
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get the estimated size of the content.
     */
    abstract public function getEstimatedSize(): int;
}
