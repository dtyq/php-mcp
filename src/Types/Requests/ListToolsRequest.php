<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Requests;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Core\RequestInterface;

/**
 * Request to list tools available on the server.
 *
 * Supports pagination through cursor-based navigation.
 */
class ListToolsRequest implements RequestInterface
{
    private string $method = 'tools/list';

    /** @var int|string */
    private $id;

    /** @var null|int|string */
    private $progressToken;

    private ?string $cursor = null;

    /**
     * @param null|string $cursor Pagination cursor
     * @param null|int|string $id Request ID
     */
    public function __construct(?string $cursor = null, $id = null)
    {
        $this->cursor = $cursor;
        $this->id = $id ?? $this->generateId();
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    /** @return null|array<string, mixed> */
    public function getParams(): ?array
    {
        $params = [];

        if ($this->cursor !== null) {
            $params['cursor'] = $this->cursor;
        }

        if ($this->progressToken !== null) {
            $params['_meta'] = ['progressToken' => $this->progressToken];
        }

        return empty($params) ? null : $params;
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

    public function getCursor(): ?string
    {
        return $this->cursor;
    }

    public function setCursor(?string $cursor): void
    {
        $this->cursor = $cursor;
    }

    public function toJsonRpc(): array
    {
        $data = [
            'jsonrpc' => '2.0',
            'id' => $this->id,
            'method' => $this->method,
        ];

        $params = $this->getParams();
        if ($params !== null) {
            $data['params'] = $params;
        }

        return $data;
    }

    /**
     * Create from array data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $cursor = $data['params']['cursor'] ?? null;
        return new self($cursor, $data['id'] ?? null);
    }

    /**
     * Generate a unique request ID.
     */
    private function generateId(): string
    {
        return uniqid('list_tools_', true);
    }
}
