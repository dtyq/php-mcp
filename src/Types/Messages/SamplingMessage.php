<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Messages;

use Dtyq\PhpMcp\Types\Constants\MessageConstants;
use Dtyq\PhpMcp\Types\Content\ContentInterface;
use Dtyq\PhpMcp\Types\Content\ImageContent;
use Dtyq\PhpMcp\Types\Content\TextContent;
use Dtyq\PhpMcp\Types\Core\BaseTypes;
use InvalidArgumentException;

/**
 * Sampling message for LLM interactions.
 *
 * Describes a message issued to or received from an LLM API.
 * Used in sampling requests and responses.
 */
class SamplingMessage implements MessageInterface
{
    /** @var string Message role */
    private string $role;

    /** @var ContentInterface Message content */
    private ContentInterface $content;

    public function __construct(string $role, ContentInterface $content)
    {
        $this->setRole($role);
        $this->content = $content;
    }

    /**
     * Create from array representation.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['role'])) {
            throw new InvalidArgumentException('Role field is required for SamplingMessage');
        }

        if (! is_string($data['role'])) {
            throw new InvalidArgumentException('Role field must be a string');
        }

        if (! isset($data['content'])) {
            throw new InvalidArgumentException('Content field is required for SamplingMessage');
        }

        if (! is_array($data['content'])) {
            throw new InvalidArgumentException('Content field must be an array');
        }

        $content = self::createContentFromArray($data['content']);

        return new self($data['role'], $content);
    }

    /**
     * Create a text sampling message.
     */
    public static function text(string $role, string $text): self
    {
        return new self($role, new TextContent($text));
    }

    /**
     * Create an image sampling message.
     */
    public static function image(string $role, string $data, string $mimeType): self
    {
        return new self($role, new ImageContent($data, $mimeType));
    }

    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * Set the message role.
     */
    public function setRole(string $role): void
    {
        BaseTypes::validateRole($role);
        $this->role = $role;
    }

    public function getContent(): ContentInterface
    {
        return $this->content;
    }

    public function setContent(ContentInterface $content): void
    {
        // Sampling messages only support text and image content
        if (! ($content instanceof TextContent) && ! ($content instanceof ImageContent)) {
            throw new InvalidArgumentException('SamplingMessage only supports TextContent and ImageContent');
        }
        $this->content = $content;
    }

    public function isTargetedTo(string $role): bool
    {
        return $this->content->isTargetedTo($role);
    }

    public function getPriority(): ?float
    {
        return $this->content->getPriority();
    }

    /**
     * Check if this is a text message.
     */
    public function isTextMessage(): bool
    {
        return $this->content instanceof TextContent;
    }

    /**
     * Check if this is an image message.
     */
    public function isImageMessage(): bool
    {
        return $this->content instanceof ImageContent;
    }

    /**
     * Get text content if this is a text message.
     */
    public function getText(): ?string
    {
        if ($this->content instanceof TextContent) {
            return $this->content->getText();
        }
        return null;
    }

    /**
     * Get image data if this is an image message.
     */
    public function getImageData(): ?string
    {
        if ($this->content instanceof ImageContent) {
            return $this->content->getData();
        }
        return null;
    }

    /**
     * Get image MIME type if this is an image message.
     */
    public function getImageMimeType(): ?string
    {
        if ($this->content instanceof ImageContent) {
            return $this->content->getMimeType();
        }
        return null;
    }

    public function toArray(): array
    {
        return [
            'role' => $this->role,
            'content' => $this->content->toArray(),
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Create a copy with different role.
     */
    public function withRole(string $role): self
    {
        return new self($role, $this->content);
    }

    /**
     * Create a copy with different content.
     */
    public function withContent(ContentInterface $content): self
    {
        return new self($this->role, $content);
    }

    /**
     * Create content from array data.
     *
     * @param array<string, mixed> $data
     */
    private static function createContentFromArray(array $data): ContentInterface
    {
        if (! isset($data['type'])) {
            throw new InvalidArgumentException('Content type field is required');
        }

        switch ($data['type']) {
            case MessageConstants::CONTENT_TYPE_TEXT:
                return TextContent::fromArray($data);
            case MessageConstants::CONTENT_TYPE_IMAGE:
                return ImageContent::fromArray($data);
            default:
                throw new InvalidArgumentException("Unsupported content type for SamplingMessage: {$data['type']}");
        }
    }
}
