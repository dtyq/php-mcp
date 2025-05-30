<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Prompts;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Core\BaseTypes;
use Dtyq\PhpMcp\Types\Core\ResultInterface;

/**
 * Result of getting a prompt template.
 *
 * Contains the generated messages and optional description for a prompt
 * that has been invoked with specific arguments.
 */
class GetPromptResult implements ResultInterface
{
    /** @var null|string Optional description of the prompt result */
    private ?string $description;

    /** @var array<PromptMessage> The generated messages */
    private array $messages;

    /** @var null|array<string, mixed> */
    private ?array $meta = null;

    /**
     * @param array<PromptMessage> $messages
     */
    public function __construct(?string $description = null, array $messages = [])
    {
        $this->setDescription($description);
        $this->setMessages($messages);
    }

    /**
     * Create from array representation.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $description = null;
        if (isset($data['description'])) {
            if (! is_string($data['description'])) {
                throw ValidationError::invalidFieldType('description', 'string', gettype($data['description']));
            }
            $description = $data['description'];
        }

        $messages = [];
        if (isset($data['messages'])) {
            if (! is_array($data['messages'])) {
                throw ValidationError::invalidFieldType('messages', 'array', gettype($data['messages']));
            }

            foreach ($data['messages'] as $index => $messageData) {
                if (! is_array($messageData)) {
                    throw ValidationError::invalidFieldType("messages[{$index}]", 'array', gettype($messageData));
                }
                $messages[] = PromptMessage::fromArray($messageData);
            }
        }

        return new self($description, $messages);
    }

    /**
     * Get the result description.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set the result description.
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
     * Get the messages.
     *
     * @return array<PromptMessage>
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Set the messages.
     *
     * @param array<PromptMessage> $messages
     */
    public function setMessages(array $messages): void
    {
        foreach ($messages as $index => $message) {
            if (! $message instanceof PromptMessage) {
                throw ValidationError::invalidFieldType("messages[{$index}]", 'PromptMessage', gettype($message));
            }
        }
        $this->messages = $messages;
    }

    /**
     * Add a message to the result.
     */
    public function addMessage(PromptMessage $message): void
    {
        $this->messages[] = $message;
    }

    /**
     * Remove a message by index.
     */
    public function removeMessage(int $index): bool
    {
        if (isset($this->messages[$index])) {
            unset($this->messages[$index]);
            $this->messages = array_values($this->messages); // Re-index
            return true;
        }
        return false;
    }

    /**
     * Get a message by index.
     */
    public function getMessage(int $index): ?PromptMessage
    {
        return $this->messages[$index] ?? null;
    }

    /**
     * Check if result has a description.
     */
    public function hasDescription(): bool
    {
        return $this->description !== null;
    }

    /**
     * Check if result has messages.
     */
    public function hasMessages(): bool
    {
        return ! empty($this->messages);
    }

    /**
     * Get the count of messages.
     */
    public function getMessageCount(): int
    {
        return count($this->messages);
    }

    /**
     * Get user messages only.
     *
     * @return array<PromptMessage>
     */
    public function getUserMessages(): array
    {
        return array_filter($this->messages, fn (PromptMessage $msg) => $msg->isUserMessage());
    }

    /**
     * Get assistant messages only.
     *
     * @return array<PromptMessage>
     */
    public function getAssistantMessages(): array
    {
        return array_filter($this->messages, fn (PromptMessage $msg) => $msg->isAssistantMessage());
    }

    /**
     * Get text messages only.
     *
     * @return array<PromptMessage>
     */
    public function getTextMessages(): array
    {
        return array_filter($this->messages, fn (PromptMessage $msg) => $msg->isTextContent());
    }

    /**
     * Get image messages only.
     *
     * @return array<PromptMessage>
     */
    public function getImageMessages(): array
    {
        return array_filter($this->messages, fn (PromptMessage $msg) => $msg->isImageContent());
    }

    /**
     * Get resource messages only.
     *
     * @return array<PromptMessage>
     */
    public function getResourceMessages(): array
    {
        return array_filter($this->messages, fn (PromptMessage $msg) => $msg->isResourceContent());
    }

    /**
     * Get all text content from messages.
     *
     * @return array<string>
     */
    public function getAllTextContent(): array
    {
        $textContent = [];
        foreach ($this->messages as $message) {
            $text = $message->getTextContent();
            if ($text !== null) {
                $textContent[] = $text;
            }
        }
        return $textContent;
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'messages' => array_map(fn (PromptMessage $msg) => $msg->toArray(), $this->messages),
        ];

        if ($this->description !== null) {
            $data['description'] = $this->description;
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
     * Create a copy with different description.
     */
    public function withDescription(?string $description): self
    {
        return new self($description, $this->messages);
    }

    /**
     * Create a copy with different messages.
     *
     * @param array<PromptMessage> $messages
     */
    public function withMessages(array $messages): self
    {
        return new self($this->description, $messages);
    }

    /**
     * Create a copy with an additional message.
     */
    public function withAddedMessage(PromptMessage $message): self
    {
        $messages = $this->messages;
        $messages[] = $message;
        return new self($this->description, $messages);
    }

    /**
     * Factory method to create a simple text result.
     */
    public static function createTextResult(string $text, ?string $description = null): self
    {
        return new self($description, [
            PromptMessage::createUserMessage($text),
        ]);
    }

    /**
     * Factory method to create a conversation result.
     *
     * @param array<array{role: string, text: string}> $conversation
     */
    public static function createConversationResult(array $conversation, ?string $description = null): self
    {
        $messages = [];
        foreach ($conversation as $turn) {
            if ($turn['role'] === 'user') {
                $messages[] = PromptMessage::createUserMessage($turn['text']);
            } elseif ($turn['role'] === 'assistant') {
                $messages[] = PromptMessage::createAssistantMessage($turn['text']);
            }
        }
        return new self($description, $messages);
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
        return false;
    }

    public function getNextCursor(): ?string
    {
        return null;
    }
}
