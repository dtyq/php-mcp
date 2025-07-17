<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Message;

use Dtyq\PhpMcp\Shared\Exceptions\ProtocolError;
use Dtyq\PhpMcp\Shared\Utilities\JsonUtils;
use JsonException;
use stdClass;

/**
 * Represents a JSON-RPC 2.0 message.
 *
 * This can be a request, response, or notification message.
 * Based on the JSON-RPC 2.0 specification.
 */
class JsonRpcMessage
{
    /**
     * JSON-RPC version (always "2.0").
     */
    public const VERSION = '2.0';

    /**
     * Message types.
     */
    public const TYPE_REQUEST = 'request';

    public const TYPE_RESPONSE = 'response';

    public const TYPE_NOTIFICATION = 'notification';

    public const TYPE_ERROR = 'error';

    /**
     * JSON-RPC version string.
     */
    private string $jsonrpc;

    /**
     * Request/notification method name.
     */
    private ?string $method;

    /**
     * Request/notification parameters.
     *
     * @var mixed
     */
    private $params;

    /**
     * Request/response ID.
     *
     * @var null|int|string
     */
    private $id;

    /**
     * Response result.
     *
     * @var mixed
     */
    private $result;

    /**
     * Error information.
     *
     * @var null|array{code: int, message: string, data?: mixed}
     */
    private ?array $error;

    /**
     * Initialize a JSON-RPC message.
     *
     * @param null|string $method Method name for requests/notifications
     * @param mixed $params Parameters for requests/notifications
     * @param null|int|string $id ID for requests/responses
     * @param mixed $result Result for successful responses
     * @param null|array{code: int, message: string, data?: mixed} $error Error for error responses
     */
    public function __construct(
        ?string $method = null,
        $params = null,
        $id = null,
        $result = null,
        ?array $error = null
    ) {
        $this->jsonrpc = self::VERSION;
        $this->method = $method;
        $this->params = $params;
        $this->id = $id;
        if (is_array($result) && empty($result)) {
            $result = new stdClass();
        }
        $this->result = $result;
        $this->error = $error;
    }

    /**
     * Create a JSON-RPC request.
     *
     * @param string $method Method name
     * @param mixed $params Request parameters
     * @param int|string $id Request ID
     */
    public static function createRequest(string $method, $params = null, $id = null): JsonRpcMessage
    {
        return new self($method, $params, $id);
    }

    /**
     * Create a JSON-RPC notification.
     *
     * @param string $method Method name
     * @param mixed $params Notification parameters
     */
    public static function createNotification(string $method, $params = null): JsonRpcMessage
    {
        return new self($method, $params);
    }

    /**
     * Create a JSON-RPC response.
     *
     * @param int|string $id Request ID being responded to
     * @param mixed $result Response result
     */
    public static function createResponse($id, $result): JsonRpcMessage
    {
        return new self(null, null, $id, $result);
    }

    /**
     * Create a JSON-RPC error response.
     *
     * @param int|string $id Request ID being responded to
     * @param array{code: int, message: string, data?: mixed} $error Error information
     */
    public static function createError($id, array $error): JsonRpcMessage
    {
        return new self(null, null, $id, null, $error);
    }

    /**
     * Get the JSON-RPC version.
     *
     * @return string Always "2.0"
     */
    public function getJsonrpc(): string
    {
        return $this->jsonrpc;
    }

    /**
     * Get the method name.
     *
     * @return null|string Method name for requests/notifications
     */
    public function getMethod(): ?string
    {
        return $this->method;
    }

    /**
     * Get the parameters.
     *
     * @return mixed Parameters for requests/notifications
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Get the ID.
     *
     * @return null|int|string ID for requests/responses
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the result.
     *
     * @return mixed Result for successful responses
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Get the error.
     *
     * @return null|array{code: int, message: string, data?: mixed} Error for error responses
     */
    public function getError(): ?array
    {
        return $this->error;
    }

    /**
     * Check if this is a request message.
     *
     * @return bool True if this is a request
     */
    public function isRequest(): bool
    {
        return $this->method !== null && $this->id !== null;
    }

    /**
     * Check if this is a notification message.
     *
     * @return bool True if this is a notification
     */
    public function isNotification(): bool
    {
        return $this->method !== null && $this->id === null;
    }

    /**
     * Check if this is a response message.
     *
     * @return bool True if this is a response
     */
    public function isResponse(): bool
    {
        return $this->method === null && $this->id !== null && $this->error === null;
    }

    /**
     * Check if this is an error response.
     *
     * @return bool True if this is an error response
     */
    public function isError(): bool
    {
        return $this->method === null && $this->id !== null && $this->error !== null;
    }

    /**
     * Get the message type.
     *
     * @return string One of the TYPE_* constants
     */
    public function getType(): string
    {
        if ($this->isRequest()) {
            return self::TYPE_REQUEST;
        }
        if ($this->isNotification()) {
            return self::TYPE_NOTIFICATION;
        }
        if ($this->isError()) {
            return self::TYPE_ERROR;
        }
        return self::TYPE_RESPONSE;
    }

    /**
     * Convert the message to an array.
     *
     * @return array<string, mixed> Array representation
     */
    public function toArray(): array
    {
        $result = ['jsonrpc' => $this->jsonrpc];

        if ($this->method !== null) {
            $result['method'] = $this->method;
        }

        if ($this->params !== null) {
            $result['params'] = $this->params;
        }

        if ($this->id !== null) {
            $result['id'] = $this->id;
        }

        if ($this->result !== null) {
            $result['result'] = $this->result;
        }

        if ($this->error !== null) {
            $result['error'] = $this->error;
        }

        return $result;
    }

    /**
     * Create JsonRpcMessage from array.
     *
     * @param array<string, mixed> $data Array data
     * @throws ProtocolError If the data is invalid
     */
    public static function fromArray(array $data): JsonRpcMessage
    {
        if (! isset($data['jsonrpc']) || $data['jsonrpc'] !== self::VERSION) {
            throw ProtocolError::invalidFormat('Invalid JSON-RPC version');
        }

        return new self(
            $data['method'] ?? null,
            $data['params'] ?? null,
            $data['id'] ?? null,
            $data['result'] ?? null,
            $data['error'] ?? null
        );
    }

    /**
     * Convert to JSON string.
     *
     * @return string JSON representation
     */
    public function toJson(): string
    {
        return JsonUtils::encode($this->toArray());
    }

    /**
     * Create JsonRpcMessage from JSON string.
     *
     * @param string $json JSON string
     * @throws JsonException If JSON is invalid
     */
    public static function fromJson(string $json): JsonRpcMessage
    {
        $data = JsonUtils::decode($json, true);
        return self::fromArray($data);
    }

    /**
     * Validate the message structure.
     *
     * @return bool True if the message is valid
     */
    public function isValid(): bool
    {
        // Must have jsonrpc field
        if ($this->jsonrpc !== self::VERSION) {
            return false;
        }

        // Request: must have method and id
        if ($this->isRequest()) {
            return $this->method !== null && $this->id !== null;
        }

        // Notification: must have method, must not have id
        if ($this->isNotification()) {
            return $this->method !== null && $this->id === null;
        }

        // Response: must have id, must have result or error (but not both)
        if ($this->id !== null) {
            return ($this->result !== null) !== ($this->error !== null);
        }

        return false;
    }
}
