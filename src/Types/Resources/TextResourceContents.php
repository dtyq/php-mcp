<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Resources;

use Dtyq\PhpMcp\Types\Core\BaseTypes;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;

/**
 * Text contents of a resource.
 *
 * Represents textual content from a resource that can be embedded
 * in prompts or tool call results.
 */
class TextResourceContents extends ResourceContents
{
    /** @var string The text content */
    private string $text;

    public function __construct(string $uri, string $text, ?string $mimeType = null)
    {
        parent::__construct($uri, $mimeType);
        $this->setText($text);
    }

    /**
     * Get the text content.
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * Set the text content.
     */
    public function setText(string $text): void
    {
        if (empty($text)) {
            throw ValidationError::emptyField('text');
        }
        $this->text = BaseTypes::sanitizeText($text);
    }

    public function isText(): bool
    {
        return true;
    }

    public function isBlob(): bool
    {
        return false;
    }

    public function getBlob(): ?string
    {
        return null;
    }

    /**
     * Get the length of the text content.
     */
    public function getLength(): int
    {
        return strlen($this->text);
    }

    /**
     * Check if the text content is empty.
     */
    public function isEmpty(): bool
    {
        return empty(trim($this->text));
    }

    /**
     * Get a truncated version of the text.
     */
    public function truncate(int $maxLength, string $suffix = '...'): string
    {
        if ($this->getLength() <= $maxLength) {
            return $this->text;
        }

        return substr($this->text, 0, $maxLength - strlen($suffix)) . $suffix;
    }

    public function getEstimatedSize(): int
    {
        return strlen($this->text);
    }

    public function toArray(): array
    {
        $data = [
            'uri' => $this->uri,
            'text' => $this->text,
        ];

        if ($this->mimeType !== null) {
            $data['mimeType'] = $this->mimeType;
        }

        return $data;
    }

    /**
     * Create a copy with different text.
     */
    public function withText(string $text): self
    {
        return new self($this->uri, $text, $this->mimeType);
    }

    /**
     * Create a copy with different URI.
     */
    public function withUri(string $uri): self
    {
        return new self($uri, $this->text, $this->mimeType);
    }

    /**
     * Create a copy with different MIME type.
     */
    public function withMimeType(?string $mimeType): self
    {
        return new self($this->uri, $this->text, $mimeType);
    }
}
