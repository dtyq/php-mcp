<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Resources;

use Dtyq\PhpMcp\Types\Content\Annotations;
use Dtyq\PhpMcp\Types\Core\BaseTypes;
use InvalidArgumentException;

/**
 * A known resource that the server is capable of reading.
 *
 * Represents a resource definition with URI, name, description,
 * MIME type, size information, and optional annotations.
 */
class Resource
{
    /** @var string The URI of this resource */
    private string $uri;

    /** @var string A human-readable name for this resource */
    private string $name;

    /** @var null|string A description of what this resource represents */
    private ?string $description;

    /** @var null|string The MIME type of this resource, if known */
    private ?string $mimeType;

    /** @var null|int The size of the raw resource content, in bytes */
    private ?int $size;

    /** @var null|Annotations Resource annotations */
    private ?Annotations $annotations;

    public function __construct(
        string $uri,
        string $name,
        ?string $description = null,
        ?string $mimeType = null,
        ?int $size = null,
        ?Annotations $annotations = null
    ) {
        $this->setUri($uri);
        $this->setName($name);
        $this->setDescription($description);
        $this->setMimeType($mimeType);
        $this->setSize($size);
        $this->annotations = $annotations;
    }

    /**
     * Create from array representation.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['uri'])) {
            throw new InvalidArgumentException('URI field is required for Resource');
        }

        if (! is_string($data['uri'])) {
            throw new InvalidArgumentException('URI field must be a string');
        }

        if (! isset($data['name'])) {
            throw new InvalidArgumentException('Name field is required for Resource');
        }

        if (! is_string($data['name'])) {
            throw new InvalidArgumentException('Name field must be a string');
        }

        $description = null;
        if (isset($data['description'])) {
            if (! is_string($data['description'])) {
                throw new InvalidArgumentException('Description field must be a string');
            }
            $description = $data['description'];
        }

        $mimeType = null;
        if (isset($data['mimeType'])) {
            if (! is_string($data['mimeType'])) {
                throw new InvalidArgumentException('MimeType field must be a string');
            }
            $mimeType = $data['mimeType'];
        }

        $size = null;
        if (isset($data['size'])) {
            if (! is_int($data['size'])) {
                throw new InvalidArgumentException('Size field must be an integer');
            }
            $size = $data['size'];
        }

        $annotations = null;
        if (isset($data['annotations']) && is_array($data['annotations'])) {
            $annotations = Annotations::fromArray($data['annotations']);
        }

        return new self(
            $data['uri'],
            $data['name'],
            $description,
            $mimeType,
            $size,
            $annotations
        );
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
     * Get the human-readable name for this resource.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the human-readable name for this resource.
     */
    public function setName(string $name): void
    {
        if (empty(trim($name))) {
            throw new InvalidArgumentException('Resource name cannot be empty');
        }
        $this->name = BaseTypes::sanitizeText($name);
    }

    /**
     * Get the description of what this resource represents.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set the description of what this resource represents.
     */
    public function setDescription(?string $description): void
    {
        if ($description !== null) {
            $description = trim($description);
            if (empty($description)) {
                $description = null;
            } else {
                $description = BaseTypes::sanitizeText($description);
            }
        }
        $this->description = $description;
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
     * Get the size of the raw resource content, in bytes.
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * Set the size of the raw resource content, in bytes.
     */
    public function setSize(?int $size): void
    {
        if ($size !== null && $size < 0) {
            throw new InvalidArgumentException('Resource size cannot be negative');
        }
        $this->size = $size;
    }

    /**
     * Get the resource annotations.
     */
    public function getAnnotations(): ?Annotations
    {
        return $this->annotations;
    }

    /**
     * Set the resource annotations.
     */
    public function setAnnotations(?Annotations $annotations): void
    {
        $this->annotations = $annotations;
    }

    /**
     * Check if resource has annotations.
     */
    public function hasAnnotations(): bool
    {
        return $this->annotations !== null && ! $this->annotations->isEmpty();
    }

    /**
     * Check if resource is targeted to a specific role.
     */
    public function isTargetedTo(string $role): bool
    {
        if (! $this->hasAnnotations()) {
            return true;
        }

        return $this->annotations->isTargetedTo($role);
    }

    /**
     * Get resource priority (0.0 to 1.0).
     */
    public function getPriority(): ?float
    {
        if (! $this->hasAnnotations()) {
            return null;
        }

        return $this->annotations->getPriority();
    }

    /**
     * Check if resource has a description.
     */
    public function hasDescription(): bool
    {
        return $this->description !== null;
    }

    /**
     * Check if resource has a MIME type.
     */
    public function hasMimeType(): bool
    {
        return $this->mimeType !== null;
    }

    /**
     * Check if resource has size information.
     */
    public function hasSize(): bool
    {
        return $this->size !== null;
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'uri' => $this->uri,
            'name' => $this->name,
        ];

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->mimeType !== null) {
            $data['mimeType'] = $this->mimeType;
        }

        if ($this->size !== null) {
            $data['size'] = $this->size;
        }

        if ($this->hasAnnotations()) {
            $data['annotations'] = $this->annotations->toArray();
        }

        return $data;
    }

    /**
     * Convert to JSON string.
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Create a copy with different URI.
     */
    public function withUri(string $uri): self
    {
        return new self(
            $uri,
            $this->name,
            $this->description,
            $this->mimeType,
            $this->size,
            $this->annotations
        );
    }

    /**
     * Create a copy with different name.
     */
    public function withName(string $name): self
    {
        return new self(
            $this->uri,
            $name,
            $this->description,
            $this->mimeType,
            $this->size,
            $this->annotations
        );
    }

    /**
     * Create a copy with different description.
     */
    public function withDescription(?string $description): self
    {
        return new self(
            $this->uri,
            $this->name,
            $description,
            $this->mimeType,
            $this->size,
            $this->annotations
        );
    }

    /**
     * Create a copy with different MIME type.
     */
    public function withMimeType(?string $mimeType): self
    {
        return new self(
            $this->uri,
            $this->name,
            $this->description,
            $mimeType,
            $this->size,
            $this->annotations
        );
    }

    /**
     * Create a copy with different size.
     */
    public function withSize(?int $size): self
    {
        return new self(
            $this->uri,
            $this->name,
            $this->description,
            $this->mimeType,
            $size,
            $this->annotations
        );
    }

    /**
     * Create a copy with different annotations.
     */
    public function withAnnotations(?Annotations $annotations): self
    {
        return new self(
            $this->uri,
            $this->name,
            $this->description,
            $this->mimeType,
            $this->size,
            $annotations
        );
    }
}
