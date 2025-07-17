<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Sampling;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Shared\Utilities\JsonUtils;
use Dtyq\PhpMcp\Types\Constants\MessageConstants;
use Dtyq\PhpMcp\Types\Content\ContentInterface;
use Dtyq\PhpMcp\Types\Content\EmbeddedResource;
use Dtyq\PhpMcp\Types\Content\ImageContent;
use Dtyq\PhpMcp\Types\Content\TextContent;

/**
 * Represents a message used in LLM sampling requests.
 *
 * Sampling messages are part of the conversation history sent to language models
 * for completion generation. They support text, image, and embedded resource content.
 */
class SamplingMessage
{
    private string $role;

    private ContentInterface $content;

    /**
     * Create a new sampling message.
     *
     * @param string $role The role of the message sender (user or assistant)
     * @param ContentInterface $content The message content
     * @throws ValidationError If role or content is invalid
     */
    public function __construct(string $role, ContentInterface $content)
    {
        $this->setRole($role);
        $this->setContent($content);
    }

    /**
     * Create a sampling message from array data.
     *
     * @param array<string, mixed> $data The message data
     * @throws ValidationError If data is invalid
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['role'])) {
            throw ValidationError::requiredFieldMissing('role');
        }

        if (! isset($data['content'])) {
            throw ValidationError::requiredFieldMissing('content');
        }

        if (! is_string($data['role'])) {
            throw ValidationError::invalidFieldType('role', 'string', gettype($data['role']));
        }

        if (! is_array($data['content'])) {
            throw ValidationError::invalidFieldType('content', 'array', gettype($data['content']));
        }

        // Create content based on type
        $content = self::createContentFromArray($data['content']);

        return new self($data['role'], $content);
    }

    /**
     * Create a user message with text content.
     *
     * @param string $text The message text
     */
    public static function createUserMessage(string $text): self
    {
        return new self(MessageConstants::ROLE_USER, new TextContent($text));
    }

    /**
     * Create an assistant message with text content.
     *
     * @param string $text The message text
     */
    public static function createAssistantMessage(string $text): self
    {
        return new self(MessageConstants::ROLE_ASSISTANT, new TextContent($text));
    }

    /**
     * Create a user message with image content.
     *
     * @param string $data Base64-encoded image data
     * @param string $mimeType The image MIME type
     */
    public static function createUserImageMessage(string $data, string $mimeType): self
    {
        return new self(MessageConstants::ROLE_USER, new ImageContent($data, $mimeType));
    }

    /**
     * Get the message role.
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * Get the message content.
     */
    public function getContent(): ContentInterface
    {
        return $this->content;
    }

    /**
     * Check if this is a user message.
     */
    public function isUserMessage(): bool
    {
        return $this->role === MessageConstants::ROLE_USER;
    }

    /**
     * Check if this is an assistant message.
     */
    public function isAssistantMessage(): bool
    {
        return $this->role === MessageConstants::ROLE_ASSISTANT;
    }

    /**
     * Check if the content is text.
     */
    public function isTextContent(): bool
    {
        return $this->content instanceof TextContent;
    }

    /**
     * Check if the content is an image.
     */
    public function isImageContent(): bool
    {
        return $this->content instanceof ImageContent;
    }

    /**
     * Check if the content is an embedded resource.
     */
    public function isEmbeddedResourceContent(): bool
    {
        return $this->content instanceof EmbeddedResource;
    }

    /**
     * Get text content if available.
     */
    public function getTextContent(): ?string
    {
        if ($this->content instanceof TextContent) {
            return $this->content->getText();
        }
        return null;
    }

    /**
     * Get image data if available.
     *
     * @return null|string Base64-encoded image data
     */
    public function getImageData(): ?string
    {
        if ($this->content instanceof ImageContent) {
            return $this->content->getData();
        }
        return null;
    }

    /**
     * Get image MIME type if available.
     */
    public function getImageMimeType(): ?string
    {
        if ($this->content instanceof ImageContent) {
            return $this->content->getMimeType();
        }
        return null;
    }

    /**
     * Set the message role.
     *
     * @param string $role The role
     * @throws ValidationError If role is invalid
     */
    public function setRole(string $role): void
    {
        if (empty($role)) {
            throw ValidationError::emptyField('role');
        }

        if (! in_array($role, [MessageConstants::ROLE_USER, MessageConstants::ROLE_ASSISTANT], true)) {
            throw ValidationError::invalidFieldValue('role', 'must be either "user" or "assistant"');
        }

        $this->role = $role;
    }

    /**
     * Set the message content.
     *
     * @param ContentInterface $content The content
     */
    public function setContent(ContentInterface $content): void
    {
        $this->content = $content;
    }

    /**
     * Create a new message with a different role.
     *
     * @param string $role The new role
     */
    public function withRole(string $role): self
    {
        $new = clone $this;
        $new->setRole($role);
        return $new;
    }

    /**
     * Create a new message with different content.
     *
     * @param ContentInterface $content The new content
     */
    public function withContent(ContentInterface $content): self
    {
        $new = clone $this;
        $new->setContent($content);
        return $new;
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'role' => $this->role,
            'content' => $this->content->toArray(),
        ];
    }

    /**
     * Convert to JSON string.
     */
    public function toJson(): string
    {
        return JsonUtils::encode($this->toArray());
    }

    /**
     * Create content from array data.
     *
     * @param array<string, mixed> $data The content data
     * @throws ValidationError If content type is invalid
     */
    private static function createContentFromArray(array $data): ContentInterface
    {
        if (! isset($data['type'])) {
            throw ValidationError::requiredFieldMissing('content.type');
        }

        if (! is_string($data['type'])) {
            throw ValidationError::invalidFieldType('content.type', 'string', gettype($data['type']));
        }

        switch ($data['type']) {
            case 'text':
                return TextContent::fromArray($data);
            case 'image':
                return ImageContent::fromArray($data);
            case 'resource':
                return EmbeddedResource::fromArray($data);
            default:
                throw ValidationError::unsupportedContentType($data['type']);
        }
    }
}
