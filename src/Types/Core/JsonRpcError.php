<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Core;

use Dtyq\PhpMcp\Shared\Exceptions\ErrorData;
use InvalidArgumentException;

/**
 * JSON-RPC 2.0 error response implementation.
 *
 * Represents an error response to a JSON-RPC request.
 * Contains error information following the JSON-RPC 2.0 specification.
 */
class JsonRpcError implements JsonRpcResponseInterface
{
    /** @var string JSON-RPC version */
    private string $jsonrpc = '2.0';

    /** @var int|string Response ID matching the request */
    private $id;

    /** @var ErrorData Error information */
    private ErrorData $error;

    /**
     * @param int|string $id
     */
    public function __construct($id, ErrorData $error)
    {
        $this->setId($id);
        $this->error = $error;
    }

    /**
     * Create from JSON-RPC array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['jsonrpc']) || $data['jsonrpc'] !== '2.0') {
            throw new InvalidArgumentException('Invalid JSON-RPC version');
        }

        if (! isset($data['id'])) {
            throw new InvalidArgumentException('ID is required for error responses');
        }

        if (! isset($data['error'])) {
            throw new InvalidArgumentException('Error is required for error responses');
        }

        if (! is_array($data['error'])) {
            throw new InvalidArgumentException('Error must be an array');
        }

        $errorData = ErrorData::fromArray($data['error']);
        return new self($data['id'], $errorData);
    }

    /**
     * Create error response from exception.
     *
     * @param int|string $id
     * @param mixed $data
     */
    public static function fromError($id, int $code, string $message, $data = null): self
    {
        $errorData = new ErrorData($code, $message, $data);
        return new self($id, $errorData);
    }

    /**
     * Get the response ID.
     *
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the response ID.
     *
     * @param mixed $id
     */
    public function setId($id): void
    {
        if (! is_string($id) && ! is_int($id)) {
            throw new InvalidArgumentException('ID must be string or integer');
        }
        $this->id = $id;
    }

    /**
     * Get the error data.
     */
    public function getError(): ErrorData
    {
        return $this->error;
    }

    /**
     * Set the error data.
     */
    public function setError(ErrorData $error): void
    {
        $this->error = $error;
    }

    /**
     * Get error code.
     */
    public function getCode(): int
    {
        return $this->error->getCode();
    }

    /**
     * Get error message.
     */
    public function getMessage(): string
    {
        return $this->error->getMessage();
    }

    /**
     * Get error data.
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->error->getData();
    }

    /**
     * Check if this is an error response.
     */
    public function isError(): bool
    {
        return true;
    }

    /**
     * Convert to JSON-RPC 2.0 format.
     *
     * @return array<string, mixed>
     */
    public function toJsonRpc(): array
    {
        return [
            'jsonrpc' => $this->jsonrpc,
            'id' => $this->id,
            'error' => $this->error->toArray(),
        ];
    }

    /**
     * Convert to JSON string.
     */
    public function toJson(): string
    {
        return json_encode($this->toJsonRpc(), JSON_UNESCAPED_SLASHES);
    }

    /**
     * Check if this error response matches a request ID.
     *
     * @param int|string $requestId
     */
    public function matchesRequest($requestId): bool
    {
        return $this->id === $requestId;
    }

    /**
     * Check if this is a specific error code.
     */
    public function isErrorCode(int $code): bool
    {
        return $this->error->getCode() === $code;
    }
}
