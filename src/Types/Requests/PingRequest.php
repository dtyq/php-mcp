<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Requests;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;
use Dtyq\PhpMcp\Types\Core\RequestInterface;

/**
 * Ping request issued by either server or client to check connectivity.
 *
 * The receiver must promptly respond, or else may be disconnected.
 */
class PingRequest implements RequestInterface
{
    private string $method = 'ping';

    /** @var int|string */
    private $id;

    /** @var null|int|string */
    private $progressToken;

    /**
     * @param null|int|string $id Request ID
     */
    public function __construct($id = null)
    {
        $this->id = $id ?? $this->generateId();
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    /** @return null|array<string, mixed> */
    public function getParams(): ?array
    {
        if ($this->progressToken !== null) {
            return ['_meta' => ['progressToken' => $this->progressToken]];
        }

        return null;
    }

    /** @return int|string */
    public function getId()
    {
        return $this->id;
    }

    /** @param int|string $id */
    public function setId($id): void
    {
        if (! is_string($id) && ! is_int($id)) {
            throw ValidationError::invalidArgumentType('id', 'string or integer', gettype($id));
        }
        $this->id = $id;
    }

    public function hasProgressToken(): bool
    {
        return $this->progressToken !== null;
    }

    /** @return null|int|string */
    public function getProgressToken()
    {
        return $this->progressToken;
    }

    /** @param null|int|string $token */
    public function setProgressToken($token): void
    {
        if ($token !== null && ! is_string($token) && ! is_int($token)) {
            throw ValidationError::invalidArgumentType('progressToken', 'string, integer, or null', gettype($token));
        }
        $this->progressToken = $token;
    }

    public function toJsonRpc(): array
    {
        return [
            'jsonrpc' => ProtocolConstants::JSONRPC_VERSION,
            'id' => $this->id,
            'method' => $this->method,
        ];
    }

    /**
     * Create from array data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data['id'] ?? null);
    }

    /**
     * Generate a unique request ID.
     */
    private function generateId(): string
    {
        return uniqid('ping_', true);
    }
}
