<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Tools;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Content\ContentInterface;
use Dtyq\PhpMcp\Types\Content\EmbeddedResource;
use Dtyq\PhpMcp\Types\Content\ImageContent;
use Dtyq\PhpMcp\Types\Content\TextContent;

/**
 * The server's response to a tool call.
 *
 * Represents the result of executing a tool, including content
 * and whether the execution resulted in an error.
 */
class ToolResult
{
    /** @var array<ContentInterface> The content returned by the tool */
    private array $content;

    /** @var bool Whether the tool execution resulted in an error */
    private bool $isError;

    /**
     * @param array<ContentInterface> $content
     */
    public function __construct(array $content, bool $isError = false)
    {
        $this->setContent($content);
        $this->isError = $isError;
    }

    /**
     * Create from array representation.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['content'])) {
            throw ValidationError::requiredFieldMissing('content', 'ToolResult');
        }

        if (! is_array($data['content'])) {
            throw ValidationError::invalidFieldType('content', 'array', gettype($data['content']));
        }

        $content = [];
        foreach ($data['content'] as $item) {
            if (! is_array($item)) {
                throw ValidationError::invalidFieldValue('content', 'all items must be arrays');
            }

            if (! isset($item['type'])) {
                throw ValidationError::requiredFieldMissing('type', 'content item');
            }

            switch ($item['type']) {
                case 'text':
                    $content[] = TextContent::fromArray($item);
                    break;
                case 'image':
                    $content[] = ImageContent::fromArray($item);
                    break;
                case 'resource':
                    $content[] = EmbeddedResource::fromArray($item);
                    break;
                default:
                    throw ValidationError::unsupportedContentType($item['type'], 'ToolResult');
            }
        }

        $isError = false;
        if (isset($data['isError'])) {
            if (! is_bool($data['isError'])) {
                throw ValidationError::invalidFieldType('isError', 'boolean', gettype($data['isError']));
            }
            $isError = $data['isError'];
        }

        return new self($content, $isError);
    }

    /**
     * Create a successful result with text content.
     */
    public static function success(string $text): self
    {
        return new self([new TextContent($text)], false);
    }

    /**
     * Create an error result with text content.
     */
    public static function error(string $errorMessage): self
    {
        return new self([new TextContent($errorMessage)], true);
    }

    /**
     * Create a result with multiple content items.
     *
     * @param array<ContentInterface> $content
     */
    public static function createWithContent(array $content, bool $isError = false): self
    {
        return new self($content, $isError);
    }

    /**
     * Get the content returned by the tool.
     *
     * @return array<ContentInterface>
     */
    public function getContent(): array
    {
        return $this->content;
    }

    /**
     * Set the content returned by the tool.
     *
     * @param array<ContentInterface> $content
     */
    public function setContent(array $content): void
    {
        if (empty($content)) {
            throw ValidationError::emptyField('content');
        }

        foreach ($content as $item) {
            if (! $item instanceof ContentInterface) {
                throw ValidationError::invalidFieldValue('content', 'all items must implement ContentInterface');
            }
        }

        $this->content = $content;
    }

    /**
     * Check if the tool execution resulted in an error.
     */
    public function isError(): bool
    {
        return $this->isError;
    }

    /**
     * Set whether the tool execution resulted in an error.
     */
    public function setIsError(bool $isError): void
    {
        $this->isError = $isError;
    }

    /**
     * Check if the result is successful.
     */
    public function isSuccess(): bool
    {
        return ! $this->isError;
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
     */
    public function getFirstContent(): ?ContentInterface
    {
        return $this->content[0] ?? null;
    }

    /**
     * Get the last content item.
     */
    public function getLastContent(): ?ContentInterface
    {
        return end($this->content) ?: null;
    }

    /**
     * Add a content item to the result.
     */
    public function addContent(ContentInterface $content): void
    {
        $this->content[] = $content;
    }

    /**
     * Get all text content as a single string.
     */
    public function getTextContent(): string
    {
        $texts = [];
        foreach ($this->content as $item) {
            if ($item instanceof TextContent) {
                $texts[] = $item->getText();
            }
        }
        return implode("\n", $texts);
    }

    /**
     * Get all text content items.
     *
     * @return array<TextContent>
     */
    public function getTextContentItems(): array
    {
        $textItems = [];
        foreach ($this->content as $item) {
            if ($item instanceof TextContent) {
                $textItems[] = $item;
            }
        }
        return $textItems;
    }

    /**
     * Get all image content items.
     *
     * @return array<ImageContent>
     */
    public function getImageContentItems(): array
    {
        $imageItems = [];
        foreach ($this->content as $item) {
            if ($item instanceof ImageContent) {
                $imageItems[] = $item;
            }
        }
        return $imageItems;
    }

    /**
     * Get all embedded resource content items.
     *
     * @return array<EmbeddedResource>
     */
    public function getEmbeddedResourceItems(): array
    {
        $resourceItems = [];
        foreach ($this->content as $item) {
            if ($item instanceof EmbeddedResource) {
                $resourceItems[] = $item;
            }
        }
        return $resourceItems;
    }

    /**
     * Check if result contains text content.
     */
    public function hasTextContent(): bool
    {
        foreach ($this->content as $item) {
            if ($item instanceof TextContent) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if result contains image content.
     */
    public function hasImageContent(): bool
    {
        foreach ($this->content as $item) {
            if ($item instanceof ImageContent) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if result contains embedded resource content.
     */
    public function hasEmbeddedResourceContent(): bool
    {
        foreach ($this->content as $item) {
            if ($item instanceof EmbeddedResource) {
                return true;
            }
        }
        return false;
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $content = [];
        foreach ($this->content as $item) {
            $content[] = $item->toArray();
        }

        $data = [
            'content' => $content,
        ];

        if ($this->isError) {
            $data['isError'] = $this->isError;
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
     * Create a copy with different content.
     *
     * @param array<ContentInterface> $content
     */
    public function withContent(array $content): self
    {
        return new self($content, $this->isError);
    }

    /**
     * Create a copy with different error status.
     */
    public function withIsError(bool $isError): self
    {
        return new self($this->content, $isError);
    }

    /**
     * Create a copy with additional content.
     */
    public function withAdditionalContent(ContentInterface $content): self
    {
        $newContent = $this->content;
        $newContent[] = $content;
        return new self($newContent, $this->isError);
    }
}
