<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Message;

/**
 * A message with specific metadata for transport-specific features.
 *
 * Corresponds to Python SDK's SessionMessage class from mcp/shared/message.py.
 */
class SessionMessage
{
    /**
     * The JSON-RPC message.
     */
    private JsonRpcMessage $message;

    /**
     * Optional metadata for transport-specific features.
     *
     * @var mixed
     */
    private $metadata;

    /**
     * Initialize a SessionMessage.
     *
     * @param JsonRpcMessage $message The JSON-RPC message
     * @param mixed $metadata Optional metadata for transport-specific features
     */
    public function __construct(JsonRpcMessage $message, $metadata = null)
    {
        $this->message = $message;
        $this->metadata = $metadata;
    }

    /**
     * Get the JSON-RPC message.
     *
     * @return JsonRpcMessage The JSON-RPC message
     */
    public function getMessage(): JsonRpcMessage
    {
        return $this->message;
    }

    /**
     * Get the message metadata.
     *
     * @return mixed The metadata, or null if not provided
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Set the message metadata.
     *
     * @param mixed $metadata The metadata to set
     */
    public function setMetadata($metadata): void
    {
        $this->metadata = $metadata;
    }

    /**
     * Convert the session message to an array.
     *
     * @return array{message: array<string, mixed>, metadata?: mixed}
     */
    public function toArray(): array
    {
        $result = ['message' => $this->message->toArray()];

        if ($this->metadata !== null) {
            $result['metadata'] = $this->metadata;
        }

        return $result;
    }

    /**
     * Create SessionMessage from array.
     *
     * @param array{message: array<string, mixed>, metadata?: mixed} $data Array data
     */
    public static function fromArray(array $data): SessionMessage
    {
        $message = JsonRpcMessage::fromArray($data['message']);
        $metadata = $data['metadata'] ?? null;

        return new self($message, $metadata);
    }

    /**
     * Convert to JSON string.
     *
     * @return string JSON representation
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    /**
     * Create SessionMessage from JSON string.
     *
     * @param string $json JSON string
     */
    public static function fromJson(string $json): SessionMessage
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        return self::fromArray($data);
    }
}
