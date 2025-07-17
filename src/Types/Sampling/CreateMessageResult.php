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
 * Represents the result of a message creation request through LLM sampling.
 *
 * This class encapsulates the response from a language model including
 * the generated content, model information, and completion metadata.
 */
class CreateMessageResult
{
    private string $model;

    private string $role;

    private ContentInterface $content;

    private ?string $stopReason;

    /**
     * Create a new message creation result.
     *
     * @param string $model The model that generated the message
     * @param string $role The role of the generated message
     * @param ContentInterface $content The generated content
     * @param null|string $stopReason The reason generation stopped
     * @throws ValidationError If parameters are invalid
     */
    public function __construct(
        string $model,
        string $role,
        ContentInterface $content,
        ?string $stopReason = null
    ) {
        $this->setModel($model);
        $this->setRole($role);
        $this->setContent($content);
        $this->setStopReason($stopReason);
    }

    /**
     * Create a result from array data.
     *
     * @param array<string, mixed> $data The result data
     * @throws ValidationError If data is invalid
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['model'])) {
            throw ValidationError::requiredFieldMissing('model');
        }

        if (! isset($data['role'])) {
            throw ValidationError::requiredFieldMissing('role');
        }

        if (! isset($data['content'])) {
            throw ValidationError::requiredFieldMissing('content');
        }

        if (! is_string($data['model'])) {
            throw ValidationError::invalidFieldType('model', 'string', gettype($data['model']));
        }

        if (! is_string($data['role'])) {
            throw ValidationError::invalidFieldType('role', 'string', gettype($data['role']));
        }

        if (! is_array($data['content'])) {
            throw ValidationError::invalidFieldType('content', 'array', gettype($data['content']));
        }

        // Create content based on type
        $content = self::createContentFromArray($data['content']);

        return new self(
            $data['model'],
            $data['role'],
            $content,
            $data['stopReason'] ?? null
        );
    }

    /**
     * Create a text result.
     *
     * @param string $model The model name
     * @param string $text The generated text
     * @param null|string $stopReason The stop reason
     */
    public static function createTextResult(string $model, string $text, ?string $stopReason = null): self
    {
        return new self(
            $model,
            MessageConstants::ROLE_ASSISTANT,
            new TextContent($text),
            $stopReason
        );
    }

    /**
     * Create an image result.
     *
     * @param string $model The model name
     * @param string $data Base64-encoded image data
     * @param string $mimeType The image MIME type
     * @param null|string $stopReason The stop reason
     */
    public static function createImageResult(
        string $model,
        string $data,
        string $mimeType,
        ?string $stopReason = null
    ): self {
        return new self(
            $model,
            MessageConstants::ROLE_ASSISTANT,
            new ImageContent($data, $mimeType),
            $stopReason
        );
    }

    /**
     * Get the model name.
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Get the message role.
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * Get the generated content.
     */
    public function getContent(): ContentInterface
    {
        return $this->content;
    }

    /**
     * Get the stop reason.
     */
    public function getStopReason(): ?string
    {
        return $this->stopReason;
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
     * Check if generation was stopped by end of turn.
     */
    public function isEndTurn(): bool
    {
        return $this->stopReason === 'endTurn';
    }

    /**
     * Check if generation was stopped by a stop sequence.
     */
    public function isStopSequence(): bool
    {
        return $this->stopReason === 'stopSequence';
    }

    /**
     * Check if generation was stopped by max tokens.
     */
    public function isMaxTokens(): bool
    {
        return $this->stopReason === 'maxTokens';
    }

    /**
     * Check if a stop reason is set.
     */
    public function hasStopReason(): bool
    {
        return $this->stopReason !== null;
    }

    /**
     * Set the model name.
     *
     * @param string $model The model name
     * @throws ValidationError If model is invalid
     */
    public function setModel(string $model): void
    {
        if (empty($model)) {
            throw ValidationError::emptyField('model');
        }

        $this->model = $model;
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
     * Set the generated content.
     *
     * @param ContentInterface $content The content
     */
    public function setContent(ContentInterface $content): void
    {
        $this->content = $content;
    }

    /**
     * Set the stop reason.
     *
     * @param null|string $stopReason The stop reason
     */
    public function setStopReason(?string $stopReason): void
    {
        $this->stopReason = $stopReason;
    }

    /**
     * Create a new result with a different model.
     *
     * @param string $model The new model
     */
    public function withModel(string $model): self
    {
        $new = clone $this;
        $new->setModel($model);
        return $new;
    }

    /**
     * Create a new result with a different role.
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
     * Create a new result with different content.
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
     * Create a new result with a different stop reason.
     *
     * @param null|string $stopReason The new stop reason
     */
    public function withStopReason(?string $stopReason): self
    {
        $new = clone $this;
        $new->setStopReason($stopReason);
        return $new;
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'model' => $this->model,
            'role' => $this->role,
            'content' => $this->content->toArray(),
        ];

        if ($this->stopReason !== null) {
            $result['stopReason'] = $this->stopReason;
        }

        return $result;
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
