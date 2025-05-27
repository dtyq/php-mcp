<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Responses;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Content\EmbeddedResource;
use Dtyq\PhpMcp\Types\Content\ImageContent;
use Dtyq\PhpMcp\Types\Content\TextContent;
use Dtyq\PhpMcp\Types\Core\ResultInterface;

/**
 * Result of calling/executing a tool.
 *
 * Contains the content returned by the tool and indicates whether an error occurred.
 */
class CallToolResult implements ResultInterface
{
    /** @var array<EmbeddedResource|ImageContent|TextContent> */
    private array $content;

    private bool $isError = false;

    /** @var null|array<string, mixed> */
    private ?array $meta = null;

    /**
     * @param array<EmbeddedResource|ImageContent|TextContent> $content Tool execution results
     * @param bool $isError Whether the tool execution resulted in an error
     * @param null|array<string, mixed> $meta Optional meta information
     */
    public function __construct(array $content, bool $isError = false, ?array $meta = null)
    {
        $this->setContent($content);
        $this->isError = $isError;
        $this->meta = $meta;
    }

    public function toArray(): array
    {
        $data = [
            'content' => array_map(fn ($item) => $item->toArray(), $this->content),
            'isError' => $this->isError,
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
        return false; // Tool results are not paginated
    }

    public function getNextCursor(): ?string
    {
        return null; // Tool results don't have pagination
    }

    /**
     * @return array<EmbeddedResource|ImageContent|TextContent>
     */
    public function getContent(): array
    {
        return $this->content;
    }

    /**
     * @param array<EmbeddedResource|ImageContent|TextContent> $content
     * @throws ValidationError
     */
    public function setContent(array $content): void
    {
        if (empty($content)) {
            throw ValidationError::emptyField('content');
        }

        foreach ($content as $index => $item) {
            // @phpstan-ignore-next-line
            if (! $item instanceof TextContent
                && ! $item instanceof ImageContent
                && ! $item instanceof EmbeddedResource) {
                throw ValidationError::invalidFieldType(
                    "content[{$index}]",
                    'TextContent, ImageContent, or EmbeddedResource',
                    get_debug_type($item)
                );
            }
        }
        $this->content = $content;
    }

    public function isError(): bool
    {
        return $this->isError;
    }

    public function setIsError(bool $isError): void
    {
        $this->isError = $isError;
    }

    /**
     * Add content item to the result.
     * @param mixed $content
     */
    public function addContent($content): void
    {
        if (! $content instanceof TextContent
            && ! $content instanceof ImageContent
            && ! $content instanceof EmbeddedResource) {
            throw ValidationError::invalidArgumentType(
                'content',
                'TextContent, ImageContent, or EmbeddedResource',
                get_class($content)
            );
        }
        $this->content[] = $content;
    }

    /**
     * Get the number of content items.
     */
    public function getContentCount(): int
    {
        return count($this->content);
    }

    /**
     * Get the first content item.
     * @return null|EmbeddedResource|ImageContent|TextContent
     */
    public function getFirstContent()
    {
        return $this->content[0] ?? null;
    }

    /**
     * Check if the result has multiple content items.
     */
    public function hasMultipleContents(): bool
    {
        return count($this->content) > 1;
    }

    /**
     * Create from array data.
     *
     * @param array<string, mixed> $data
     * @throws ValidationError
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['content'])) {
            throw ValidationError::requiredFieldMissing('content', 'CallToolResult');
        }

        if (! is_array($data['content'])) {
            throw ValidationError::invalidFieldType('content', 'array', gettype($data['content']));
        }

        if (empty($data['content'])) {
            throw ValidationError::emptyField('content');
        }

        $content = [];
        foreach ($data['content'] as $index => $contentData) {
            if (! is_array($contentData)) {
                throw ValidationError::invalidFieldType(
                    "content[{$index}]",
                    'array',
                    gettype($contentData)
                );
            }

            // Determine content type and create appropriate object
            $type = $contentData['type'] ?? null;
            switch ($type) {
                case 'text':
                    $content[] = TextContent::fromArray($contentData);
                    break;
                case 'image':
                    $content[] = ImageContent::fromArray($contentData);
                    break;
                case 'resource':
                    $content[] = EmbeddedResource::fromArray($contentData);
                    break;
                default:
                    throw ValidationError::invalidFieldValue(
                        "content[{$index}].type",
                        'must be "text", "image", or "resource"'
                    );
            }
        }

        return new self(
            $content,
            $data['isError'] ?? false,
            $data['_meta'] ?? null
        );
    }
}
