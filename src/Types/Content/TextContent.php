<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Content;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Core\BaseTypes;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;

/**
 * Text content for MCP messages.
 *
 * Represents textual content that can be included in messages, tool results,
 * and other MCP protocol structures.
 */
class TextContent implements ContentInterface
{
    /** @var string Content type identifier */
    private string $type = ProtocolConstants::CONTENT_TYPE_TEXT;

    /** @var string The text content */
    private string $text;

    /** @var null|Annotations Content annotations */
    private ?Annotations $annotations;

    public function __construct(string $text, ?Annotations $annotations = null)
    {
        $this->setText($text);
        $this->annotations = $annotations;
    }

    /**
     * Create from array representation.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['type']) || $data['type'] !== ProtocolConstants::CONTENT_TYPE_TEXT) {
            throw ValidationError::invalidContentType(ProtocolConstants::CONTENT_TYPE_TEXT, $data['type'] ?? 'unknown');
        }

        if (! isset($data['text'])) {
            throw ValidationError::requiredFieldMissing('text', 'TextContent');
        }

        if (! is_string($data['text'])) {
            throw ValidationError::invalidFieldType('text', 'string', gettype($data['text']));
        }

        $annotations = null;
        if (isset($data['annotations']) && is_array($data['annotations'])) {
            $annotations = Annotations::fromArray($data['annotations']);
        }

        return new self($data['text'], $annotations);
    }

    public function getType(): string
    {
        return $this->type;
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
        if ($text === '') {
            throw ValidationError::emptyField('text');
        }
        $this->text = BaseTypes::sanitizeText($text);
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

    public function toArray(): array
    {
        $data = [
            'type' => $this->type,
            'text' => $this->text,
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
     * Create a copy with different text.
     */
    public function withText(string $text): self
    {
        return new self($text, $this->annotations);
    }

    /**
     * Create a copy with different annotations.
     */
    public function withAnnotations(?Annotations $annotations): self
    {
        return new self($this->text, $annotations);
    }
}
