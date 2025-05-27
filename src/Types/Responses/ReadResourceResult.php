<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Responses;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Core\ResultInterface;
use Dtyq\PhpMcp\Types\Resources\ResourceContents;

/**
 * Result containing the contents of a requested resource.
 *
 * Contains one or more content items representing the resource data.
 */
class ReadResourceResult implements ResultInterface
{
    /** @var ResourceContents[] */
    private array $contents;

    /** @var null|array<string, mixed> */
    private ?array $meta = null;

    /**
     * @param ResourceContents[] $contents Array of resource contents
     * @param null|array<string, mixed> $meta Optional meta information
     */
    public function __construct(array $contents, ?array $meta = null)
    {
        $this->setContents($contents);
        $this->meta = $meta;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = [
            'contents' => array_map(fn (ResourceContents $content) => $content->toArray(), $this->contents),
        ];

        if ($this->meta !== null) {
            $data['_meta'] = $this->meta;
        }

        return $data;
    }

    public function hasMeta(): bool
    {
        return $this->meta !== null;
    }

    /** @return null|array<string, mixed> */
    public function getMeta(): ?array
    {
        return $this->meta;
    }

    /** @param null|array<string, mixed> $meta */
    public function setMeta(?array $meta): void
    {
        $this->meta = $meta;
    }

    public function isPaginated(): bool
    {
        return false; // Resource contents are not paginated
    }

    public function getNextCursor(): ?string
    {
        return null; // Resource contents don't have pagination
    }

    /**
     * @return ResourceContents[]
     */
    public function getContents(): array
    {
        return $this->contents;
    }

    /**
     * @param ResourceContents[] $contents
     * @throws ValidationError
     */
    public function setContents(array $contents): void
    {
        if (empty($contents)) {
            throw ValidationError::emptyField('contents');
        }

        foreach ($contents as $index => $content) {
            if (! $content instanceof ResourceContents) {
                throw ValidationError::invalidFieldType(
                    "contents[{$index}]",
                    'ResourceContents',
                    get_debug_type($content)
                );
            }
        }
        $this->contents = $contents;
    }

    /**
     * Add content to the result.
     */
    public function addContent(ResourceContents $content): void
    {
        $this->contents[] = $content;
    }

    /**
     * Get the number of content items.
     */
    public function getContentCount(): int
    {
        return count($this->contents);
    }

    /**
     * Get the first content item.
     */
    public function getFirstContent(): ?ResourceContents
    {
        return $this->contents[0] ?? null;
    }

    /**
     * Check if the result has multiple content items.
     */
    public function hasMultipleContents(): bool
    {
        return count($this->contents) > 1;
    }

    /**
     * Create from array data.
     *
     * @param array<string, mixed> $data
     * @throws ValidationError
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['contents'])) {
            throw ValidationError::requiredFieldMissing('contents', 'ReadResourceResult');
        }

        if (! is_array($data['contents'])) {
            throw ValidationError::invalidFieldType('contents', 'array', gettype($data['contents']));
        }

        if (empty($data['contents'])) {
            throw ValidationError::emptyField('contents');
        }

        $contents = [];
        foreach ($data['contents'] as $index => $contentData) {
            if (! is_array($contentData)) {
                throw ValidationError::invalidFieldType(
                    "contents[{$index}]",
                    'array',
                    gettype($contentData)
                );
            }
            $contents[] = ResourceContents::fromArray($contentData);
        }

        return new self(
            $contents,
            $data['_meta'] ?? null
        );
    }
}
