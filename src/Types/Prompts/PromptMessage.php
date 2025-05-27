<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Prompts;

use Dtyq\PhpMcp\Types\Content\ContentInterface;
use Dtyq\PhpMcp\Types\Content\TextContent;
use Dtyq\PhpMcp\Types\Content\ImageContent;
use Dtyq\PhpMcp\Types\Content\EmbeddedResource;
use Dtyq\PhpMcp\Types\Core\BaseTypes;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;

/**
 * A message within a prompt template.
 *
 * Represents a single message in a prompt conversation, containing
 * a role (user/assistant) and content that can be text, images,
 * or embedded resources.
 */
class PromptMessage
{
    /** @var string The role of the message sender */
    private string $role;

    /** @var ContentInterface The content of the message */
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
            throw ValidationError::requiredFieldMissing('role', 'PromptMessage');
        }

        if (! is_string($data['role'])) {
            throw ValidationError::invalidFieldType('role', 'string', gettype($data['role']));
        }

        if (! isset($data['content'])) {
            throw ValidationError::requiredFieldMissing('content', 'PromptMessage');
        }

        if (! is_array($data['content'])) {
            throw ValidationError::invalidFieldType('content', 'array', gettype($data['content']));
        }

        // Determine content type and create appropriate content object
        $content = self::createContentFromArray($data['content']);

        return new self($data['role'], $content);
    }

    /**
     * Create content object from array data.
     *
     * @param array<string, mixed> $contentData
     */
    private static function createContentFromArray(array $contentData): ContentInterface
    {
        if (! isset($contentData['type'])) {
            throw ValidationError::requiredFieldMissing('type', 'content');
        }

        $type = $contentData['type'];

        switch ($type) {
            case ProtocolConstants::CONTENT_TYPE_TEXT:
                return TextContent::fromArray($contentData);

            case ProtocolConstants::CONTENT_TYPE_IMAGE:
                return ImageContent::fromArray($contentData);

            case ProtocolConstants::CONTENT_TYPE_RESOURCE:
                return EmbeddedResource::fromArray($contentData);

            default:
                throw ValidationError::invalidContentType('text, image, or resource', $type);
        }
    }

    /**
     * Get the message role.
     */
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

    /**
     * Get the message content.
     */
    public function getContent(): ContentInterface
    {
        return $this->content;
    }

    /**
     * Set the message content.
     */
    public function setContent(ContentInterface $content): void
    {
        $this->content = $content;
    }

    /**
     * Check if this is a user message.
     */
    public function isUserMessage(): bool
    {
        return $this->role === ProtocolConstants::ROLE_USER;
    }

    /**
     * Check if this is an assistant message.
     */
    public function isAssistantMessage(): bool
    {
        return $this->role === ProtocolConstants::ROLE_ASSISTANT;
    }

    /**
     * Check if content is text.
     */
    public function isTextContent(): bool
    {
        return $this->content instanceof TextContent;
    }

    /**
     * Check if content is an image.
     */
    public function isImageContent(): bool
    {
        return $this->content instanceof ImageContent;
    }

    /**
     * Check if content is an embedded resource.
     */
    public function isResourceContent(): bool
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
     * Get content type.
     */
    public function getContentType(): string
    {
        return $this->content->getType();
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
     * Factory method to create a user text message.
     */
    public static function createUserMessage(string $text): self
    {
        return new self(
            ProtocolConstants::ROLE_USER,
            new TextContent($text)
        );
    }

    /**
     * Factory method to create an assistant text message.
     */
    public static function createAssistantMessage(string $text): self
    {
        return new self(
            ProtocolConstants::ROLE_ASSISTANT,
            new TextContent($text)
        );
    }

    /**
     * Factory method to create a user message with embedded resource.
     */
    public static function createUserResourceMessage(EmbeddedResource $resource): self
    {
        return new self(
            ProtocolConstants::ROLE_USER,
            $resource
        );
    }

    /**
     * Factory method to create a user message with image.
     */
    public static function createUserImageMessage(ImageContent $image): self
    {
        return new self(
            ProtocolConstants::ROLE_USER,
            $image
        );
    }
} 