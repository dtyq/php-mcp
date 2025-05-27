<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Requests;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Core\RequestInterface;

/**
 * Ping request issued by either server or client to check connectivity.
 *
 * The receiver must promptly respond, or else may be disconnected.
 */
class PingRequest implements RequestInterface
{
    private string $method = 'ping';

    private $id;

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

    public function getParams(): ?array
    {
        return null; // Ping requests have no parameters
    }

    public function getId()
    {
        return $this->id;
    }

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

    public function getProgressToken()
    {
        return $this->progressToken;
    }

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
            'jsonrpc' => '2.0',
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
