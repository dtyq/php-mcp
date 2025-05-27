<?php

declare(strict_types=1);

namespace Dtyq\PhpMcp\Types\Sampling;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Shared\Utilities\JsonUtils;

/**
 * Represents a request for creating a message through LLM sampling.
 * 
 * This class encapsulates all the parameters needed to request message generation
 * from a language model, including conversation history, model preferences,
 * system prompts, and sampling parameters.
 */
class CreateMessageRequest
{
    /** @var SamplingMessage[] */
    private array $messages;
    private ?ModelPreferences $modelPreferences;
    private ?string $systemPrompt;
    private ?string $includeContext;
    private ?float $temperature;
    private int $maxTokens;
    /** @var string[] */
    private array $stopSequences;
    /** @var array<string, mixed> */
    private array $metadata;

    /**
     * Create a new message creation request.
     *
     * @param SamplingMessage[] $messages The conversation messages
     * @param int $maxTokens Maximum tokens to generate
     * @param ModelPreferences|null $modelPreferences Model selection preferences
     * @param string|null $systemPrompt System prompt for the model
     * @param string|null $includeContext Context inclusion setting
     * @param float|null $temperature Sampling temperature (0.0-1.0)
     * @param string[] $stopSequences Stop sequences for generation
     * @param array<string, mixed> $metadata Additional metadata
     * @throws ValidationError If parameters are invalid
     */
    public function __construct(
        array $messages,
        int $maxTokens,
        ?ModelPreferences $modelPreferences = null,
        ?string $systemPrompt = null,
        ?string $includeContext = null,
        ?float $temperature = null,
        array $stopSequences = [],
        array $metadata = []
    ) {
        $this->setMessages($messages);
        $this->setMaxTokens($maxTokens);
        $this->setModelPreferences($modelPreferences);
        $this->setSystemPrompt($systemPrompt);
        $this->setIncludeContext($includeContext);
        $this->setTemperature($temperature);
        $this->setStopSequences($stopSequences);
        $this->setMetadata($metadata);
    }

    /**
     * Create a request from array data.
     *
     * @param array<string, mixed> $data The request data
     * @return self
     * @throws ValidationError If data is invalid
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['messages'])) {
            throw ValidationError::requiredFieldMissing('messages');
        }

        if (!isset($data['maxTokens'])) {
            throw ValidationError::requiredFieldMissing('maxTokens');
        }

        if (!is_array($data['messages'])) {
            throw ValidationError::invalidFieldType('messages', 'array', gettype($data['messages']));
        }

        if (!is_int($data['maxTokens'])) {
            throw ValidationError::invalidFieldType('maxTokens', 'integer', gettype($data['maxTokens']));
        }

        // Convert message arrays to SamplingMessage objects
        $messages = [];
        foreach ($data['messages'] as $index => $messageData) {
            if (!is_array($messageData)) {
                throw ValidationError::invalidFieldType("messages[{$index}]", 'array', gettype($messageData));
            }
            $messages[] = SamplingMessage::fromArray($messageData);
        }

        // Parse model preferences if present
        $modelPreferences = null;
        if (isset($data['modelPreferences'])) {
            if (!is_array($data['modelPreferences'])) {
                throw ValidationError::invalidFieldType('modelPreferences', 'array', gettype($data['modelPreferences']));
            }
            $modelPreferences = ModelPreferences::fromArray($data['modelPreferences']);
        }

        return new self(
            $messages,
            $data['maxTokens'],
            $modelPreferences,
            $data['systemPrompt'] ?? null,
            $data['includeContext'] ?? null,
            $data['temperature'] ?? null,
            $data['stopSequences'] ?? [],
            $data['metadata'] ?? []
        );
    }

    /**
     * Create a simple text request.
     *
     * @param string $text The user message text
     * @param int $maxTokens Maximum tokens to generate
     * @param string|null $systemPrompt Optional system prompt
     * @return self
     */
    public static function createTextRequest(string $text, int $maxTokens, ?string $systemPrompt = null): self
    {
        $messages = [SamplingMessage::createUserMessage($text)];
        return new self($messages, $maxTokens, null, $systemPrompt);
    }

    /**
     * Create a conversation request.
     *
     * @param SamplingMessage[] $messages The conversation messages
     * @param int $maxTokens Maximum tokens to generate
     * @param ModelPreferences|null $modelPreferences Model preferences
     * @return self
     */
    public static function createConversationRequest(
        array $messages,
        int $maxTokens,
        ?ModelPreferences $modelPreferences = null
    ): self {
        return new self($messages, $maxTokens, $modelPreferences);
    }

    /**
     * Get the conversation messages.
     *
     * @return SamplingMessage[]
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Get the model preferences.
     *
     * @return ModelPreferences|null
     */
    public function getModelPreferences(): ?ModelPreferences
    {
        return $this->modelPreferences;
    }

    /**
     * Get the system prompt.
     *
     * @return string|null
     */
    public function getSystemPrompt(): ?string
    {
        return $this->systemPrompt;
    }

    /**
     * Get the context inclusion setting.
     *
     * @return string|null
     */
    public function getIncludeContext(): ?string
    {
        return $this->includeContext;
    }

    /**
     * Get the sampling temperature.
     *
     * @return float|null
     */
    public function getTemperature(): ?float
    {
        return $this->temperature;
    }

    /**
     * Get the maximum tokens to generate.
     *
     * @return int
     */
    public function getMaxTokens(): int
    {
        return $this->maxTokens;
    }

    /**
     * Get the stop sequences.
     *
     * @return string[]
     */
    public function getStopSequences(): array
    {
        return $this->stopSequences;
    }

    /**
     * Get the metadata.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Check if model preferences are set.
     *
     * @return bool
     */
    public function hasModelPreferences(): bool
    {
        return $this->modelPreferences !== null;
    }

    /**
     * Check if system prompt is set.
     *
     * @return bool
     */
    public function hasSystemPrompt(): bool
    {
        return $this->systemPrompt !== null;
    }

    /**
     * Check if temperature is set.
     *
     * @return bool
     */
    public function hasTemperature(): bool
    {
        return $this->temperature !== null;
    }

    /**
     * Get the number of messages.
     *
     * @return int
     */
    public function getMessageCount(): int
    {
        return count($this->messages);
    }

    /**
     * Get user messages only.
     *
     * @return SamplingMessage[]
     */
    public function getUserMessages(): array
    {
        return array_values(array_filter($this->messages, fn($msg) => $msg->isUserMessage()));
    }

    /**
     * Get assistant messages only.
     *
     * @return SamplingMessage[]
     */
    public function getAssistantMessages(): array
    {
        return array_values(array_filter($this->messages, fn($msg) => $msg->isAssistantMessage()));
    }

    /**
     * Set the messages.
     *
     * @param SamplingMessage[] $messages The messages
     * @throws ValidationError If messages are invalid
     */
    public function setMessages(array $messages): void
    {
        if (empty($messages)) {
            throw ValidationError::emptyField('messages');
        }

        foreach ($messages as $index => $message) {
            if (!$message instanceof SamplingMessage) {
                throw ValidationError::invalidFieldType(
                    "messages[{$index}]",
                    SamplingMessage::class,
                    gettype($message)
                );
            }
        }

        $this->messages = array_values($messages);
    }

    /**
     * Set the model preferences.
     *
     * @param ModelPreferences|null $modelPreferences The preferences
     */
    public function setModelPreferences(?ModelPreferences $modelPreferences): void
    {
        $this->modelPreferences = $modelPreferences;
    }

    /**
     * Set the system prompt.
     *
     * @param string|null $systemPrompt The system prompt
     */
    public function setSystemPrompt(?string $systemPrompt): void
    {
        $this->systemPrompt = $systemPrompt;
    }

    /**
     * Set the context inclusion setting.
     *
     * @param string|null $includeContext The context setting
     * @throws ValidationError If context setting is invalid
     */
    public function setIncludeContext(?string $includeContext): void
    {
        if ($includeContext !== null) {
            $validContexts = ['none', 'thisServer', 'allServers'];
            if (!in_array($includeContext, $validContexts, true)) {
                throw ValidationError::invalidFieldValue(
                    'includeContext',
                    'must be one of: ' . implode(', ', $validContexts)
                );
            }
        }

        $this->includeContext = $includeContext;
    }

    /**
     * Set the sampling temperature.
     *
     * @param float|null $temperature The temperature (0.0-1.0)
     * @throws ValidationError If temperature is invalid
     */
    public function setTemperature(?float $temperature): void
    {
        if ($temperature !== null && ($temperature < 0.0 || $temperature > 1.0)) {
            throw ValidationError::invalidFieldValue('temperature', 'must be between 0.0 and 1.0');
        }

        $this->temperature = $temperature;
    }

    /**
     * Set the maximum tokens to generate.
     *
     * @param int $maxTokens The maximum tokens
     * @throws ValidationError If maxTokens is invalid
     */
    public function setMaxTokens(int $maxTokens): void
    {
        if ($maxTokens <= 0) {
            throw ValidationError::invalidFieldValue('maxTokens', 'must be greater than 0');
        }

        $this->maxTokens = $maxTokens;
    }

    /**
     * Set the stop sequences.
     *
     * @param string[] $stopSequences The stop sequences
     * @throws ValidationError If stop sequences are invalid
     */
    public function setStopSequences(array $stopSequences): void
    {
        foreach ($stopSequences as $index => $sequence) {
            if (!is_string($sequence)) {
                throw ValidationError::invalidFieldType(
                    "stopSequences[{$index}]",
                    'string',
                    gettype($sequence)
                );
            }
        }

        $this->stopSequences = array_values($stopSequences);
    }

    /**
     * Set the metadata.
     *
     * @param array<string, mixed> $metadata The metadata
     */
    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    /**
     * Add a message to the conversation.
     *
     * @param SamplingMessage $message The message to add
     */
    public function addMessage(SamplingMessage $message): void
    {
        $this->messages[] = $message;
    }

    /**
     * Add a stop sequence.
     *
     * @param string $sequence The stop sequence
     */
    public function addStopSequence(string $sequence): void
    {
        $this->stopSequences[] = $sequence;
    }

    /**
     * Create a new request with different messages.
     *
     * @param SamplingMessage[] $messages The new messages
     * @return self
     */
    public function withMessages(array $messages): self
    {
        $new = clone $this;
        $new->setMessages($messages);
        return $new;
    }

    /**
     * Create a new request with different model preferences.
     *
     * @param ModelPreferences|null $modelPreferences The new preferences
     * @return self
     */
    public function withModelPreferences(?ModelPreferences $modelPreferences): self
    {
        $new = clone $this;
        $new->setModelPreferences($modelPreferences);
        return $new;
    }

    /**
     * Create a new request with a different system prompt.
     *
     * @param string|null $systemPrompt The new system prompt
     * @return self
     */
    public function withSystemPrompt(?string $systemPrompt): self
    {
        $new = clone $this;
        $new->setSystemPrompt($systemPrompt);
        return $new;
    }

    /**
     * Create a new request with different temperature.
     *
     * @param float|null $temperature The new temperature
     * @return self
     */
    public function withTemperature(?float $temperature): self
    {
        $new = clone $this;
        $new->setTemperature($temperature);
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
            'messages' => array_map(fn($msg) => $msg->toArray(), $this->messages),
            'maxTokens' => $this->maxTokens,
        ];

        if ($this->modelPreferences !== null) {
            $result['modelPreferences'] = $this->modelPreferences->toArray();
        }

        if ($this->systemPrompt !== null) {
            $result['systemPrompt'] = $this->systemPrompt;
        }

        if ($this->includeContext !== null) {
            $result['includeContext'] = $this->includeContext;
        }

        if ($this->temperature !== null) {
            $result['temperature'] = $this->temperature;
        }

        if (!empty($this->stopSequences)) {
            $result['stopSequences'] = $this->stopSequences;
        }

        if (!empty($this->metadata)) {
            $result['metadata'] = $this->metadata;
        }

        return $result;
    }

    /**
     * Convert to JSON string.
     *
     * @return string
     */
    public function toJson(): string
    {
        return JsonUtils::encode($this->toArray());
    }
} 