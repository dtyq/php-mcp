<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Resources;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Content\Annotations;
use Dtyq\PhpMcp\Types\Core\BaseTypes;

/**
 * A template description for resources available on the server.
 *
 * Represents a URI template that can be used to construct resource URIs,
 * along with metadata about the type of resources it represents.
 */
class ResourceTemplate
{
    /** @var string A URI template (according to RFC 6570) */
    private string $uriTemplate;

    /** @var string A human-readable name for the type of resource */
    private string $name;

    /** @var null|string A human-readable description of what this template is for */
    private ?string $description;

    /** @var null|string The MIME type for all resources that match this template */
    private ?string $mimeType;

    /** @var null|Annotations Resource template annotations */
    private ?Annotations $annotations;

    public function __construct(
        string $uriTemplate,
        string $name,
        ?string $description = null,
        ?string $mimeType = null,
        ?Annotations $annotations = null
    ) {
        $this->setUriTemplate($uriTemplate);
        $this->setName($name);
        $this->setDescription($description);
        $this->setMimeType($mimeType);
        $this->annotations = $annotations;
    }

    /**
     * Create from array representation.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['uriTemplate'])) {
            throw ValidationError::requiredFieldMissing('uriTemplate', 'ResourceTemplate');
        }

        if (! is_string($data['uriTemplate'])) {
            throw ValidationError::invalidFieldType('uriTemplate', 'string', gettype($data['uriTemplate']));
        }

        if (! isset($data['name'])) {
            throw ValidationError::requiredFieldMissing('name', 'ResourceTemplate');
        }

        if (! is_string($data['name'])) {
            throw ValidationError::invalidFieldType('name', 'string', gettype($data['name']));
        }

        $description = null;
        if (isset($data['description'])) {
            if (! is_string($data['description'])) {
                throw ValidationError::invalidFieldType('description', 'string', gettype($data['description']));
            }
            $description = $data['description'];
        }

        $mimeType = null;
        if (isset($data['mimeType'])) {
            if (! is_string($data['mimeType'])) {
                throw ValidationError::invalidFieldType('mimeType', 'string', gettype($data['mimeType']));
            }
            $mimeType = $data['mimeType'];
        }

        $annotations = null;
        if (isset($data['annotations']) && is_array($data['annotations'])) {
            $annotations = Annotations::fromArray($data['annotations']);
        }

        return new self(
            $data['uriTemplate'],
            $data['name'],
            $description,
            $mimeType,
            $annotations
        );
    }

    /**
     * Get the URI template.
     */
    public function getUriTemplate(): string
    {
        return $this->uriTemplate;
    }

    /**
     * Set the URI template.
     */
    public function setUriTemplate(string $uriTemplate): void
    {
        if (empty(trim($uriTemplate))) {
            throw ValidationError::emptyField('uriTemplate');
        }
        $this->uriTemplate = trim($uriTemplate);
    }

    /**
     * Get the human-readable name for the type of resource.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the human-readable name for the type of resource.
     */
    public function setName(string $name): void
    {
        if (empty(trim($name))) {
            throw ValidationError::emptyField('name');
        }
        $this->name = BaseTypes::sanitizeText($name);
    }

    /**
     * Get the description of what this template is for.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set the description of what this template is for.
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
     * Get the MIME type for all resources that match this template.
     */
    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    /**
     * Set the MIME type for all resources that match this template.
     */
    public function setMimeType(?string $mimeType): void
    {
        if ($mimeType !== null) {
            BaseTypes::validateMimeType($mimeType);
        }
        $this->mimeType = $mimeType;
    }

    /**
     * Get the resource template annotations.
     */
    public function getAnnotations(): ?Annotations
    {
        return $this->annotations;
    }

    /**
     * Set the resource template annotations.
     */
    public function setAnnotations(?Annotations $annotations): void
    {
        $this->annotations = $annotations;
    }

    /**
     * Check if resource template has annotations.
     */
    public function hasAnnotations(): bool
    {
        return $this->annotations !== null && ! $this->annotations->isEmpty();
    }

    /**
     * Check if resource template is targeted to a specific role.
     */
    public function isTargetedTo(string $role): bool
    {
        if (! $this->hasAnnotations()) {
            return true;
        }

        return $this->annotations->isTargetedTo($role);
    }

    /**
     * Get resource template priority (0.0 to 1.0).
     */
    public function getPriority(): ?float
    {
        if (! $this->hasAnnotations()) {
            return null;
        }

        return $this->annotations->getPriority();
    }

    /**
     * Check if resource template has a description.
     */
    public function hasDescription(): bool
    {
        return $this->description !== null;
    }

    /**
     * Check if resource template has a MIME type.
     */
    public function hasMimeType(): bool
    {
        return $this->mimeType !== null;
    }

    /**
     * Expand the URI template with given variables.
     *
     * @param array<string, string> $variables
     */
    public function expandUri(array $variables): string
    {
        $uri = $this->uriTemplate;

        // Simple URI template expansion (RFC 6570 Level 1)
        foreach ($variables as $name => $value) {
            $uri = str_replace('{' . $name . '}', rawurlencode($value), $uri);
        }

        return $uri;
    }

    /**
     * Extract variable names from the URI template.
     *
     * @return array<string>
     */
    public function getVariableNames(): array
    {
        preg_match_all('/\{([^}]+)\}/', $this->uriTemplate, $matches);
        return $matches[1];
    }

    /**
     * Check if the URI template contains variables.
     */
    public function hasVariables(): bool
    {
        return ! empty($this->getVariableNames());
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'uriTemplate' => $this->uriTemplate,
            'name' => $this->name,
        ];

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->mimeType !== null) {
            $data['mimeType'] = $this->mimeType;
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
     * Create a copy with different URI template.
     */
    public function withUriTemplate(string $uriTemplate): self
    {
        return new self(
            $uriTemplate,
            $this->name,
            $this->description,
            $this->mimeType,
            $this->annotations
        );
    }

    /**
     * Create a copy with different name.
     */
    public function withName(string $name): self
    {
        return new self(
            $this->uriTemplate,
            $name,
            $this->description,
            $this->mimeType,
            $this->annotations
        );
    }

    /**
     * Create a copy with different description.
     */
    public function withDescription(?string $description): self
    {
        return new self(
            $this->uriTemplate,
            $this->name,
            $description,
            $this->mimeType,
            $this->annotations
        );
    }

    /**
     * Create a copy with different MIME type.
     */
    public function withMimeType(?string $mimeType): self
    {
        return new self(
            $this->uriTemplate,
            $this->name,
            $this->description,
            $mimeType,
            $this->annotations
        );
    }

    /**
     * Create a copy with different annotations.
     */
    public function withAnnotations(?Annotations $annotations): self
    {
        return new self(
            $this->uriTemplate,
            $this->name,
            $this->description,
            $this->mimeType,
            $annotations
        );
    }
}
