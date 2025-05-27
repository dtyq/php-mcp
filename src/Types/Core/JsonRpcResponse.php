<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Core;

use InvalidArgumentException;

/**
 * JSON-RPC 2.0 response implementation.
 *
 * Represents a successful JSON-RPC response message.
 * Contains the result of a successful request execution.
 */
class JsonRpcResponse
{
    /** @var string JSON-RPC version */
    private string $jsonrpc = '2.0';

    /** @var int|string Response ID matching the request */
    private $id;

    /** @var array<string, mixed> Response result */
    private array $result;

    /**
     * @param int|string $id
     * @param array<string, mixed> $result
     */
    public function __construct($id, array $result)
    {
        $this->setId($id);
        $this->result = $result;
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
            throw new InvalidArgumentException('ID is required for responses');
        }

        if (! isset($data['result'])) {
            throw new InvalidArgumentException('Result is required for successful responses');
        }

        if (! is_array($data['result'])) {
            throw new InvalidArgumentException('Result must be an array');
        }

        return new self($data['id'], $data['result']);
    }

    /**
     * Create a successful response.
     *
     * @param int|string $id
     */
    public static function success($id, ResultInterface $result): self
    {
        return new self($id, $result->toArray());
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
     * Get the response result.
     *
     * @return array<string, mixed>
     */
    public function getResult(): array
    {
        return $this->result;
    }

    /**
     * Set the response result.
     *
     * @param array<string, mixed> $result
     */
    public function setResult(array $result): void
    {
        $this->result = $result;
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
            'result' => $this->result,
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
     * Check if this response matches a request ID.
     *
     * @param int|string $requestId
     */
    public function matchesRequest($requestId): bool
    {
        return $this->id === $requestId;
    }
}
