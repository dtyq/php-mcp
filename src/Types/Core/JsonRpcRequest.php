<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Core;

use InvalidArgumentException;

/**
 * JSON-RPC 2.0 request implementation.
 *
 * Represents a complete JSON-RPC request message that expects a response.
 * Provides validation and serialization for the JSON-RPC 2.0 specification.
 */
class JsonRpcRequest implements RequestInterface
{
    /** @var string JSON-RPC version */
    private string $jsonrpc = '2.0';

    /** @var int|string Request ID for correlation */
    private $id;

    /** @var string Method name */
    private string $method;

    /** @var null|array<string, mixed> Request parameters */
    private ?array $params;

    /** @var null|int|string Progress token for tracking */
    private $progressToken;

    /**
     * @param null|array<string, mixed> $params
     * @param null|int|string $id
     */
    public function __construct(string $method, ?array $params = null, $id = null)
    {
        $this->setMethod($method);
        $this->params = $params;
        $this->id = $id ?? 0;

        // Extract progress token from params meta
        $this->extractProgressToken();
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

        if (! isset($data['method'])) {
            throw new InvalidArgumentException('Method is required');
        }

        if (! isset($data['id'])) {
            throw new InvalidArgumentException('ID is required for requests');
        }

        return new self(
            $data['method'],
            $data['params'] ?? null,
            $data['id']
        );
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): void
    {
        if (empty($method)) {
            throw new InvalidArgumentException('Method cannot be empty');
        }
        $this->method = $method;
    }

    public function getParams(): ?array
    {
        return $this->params;
    }

    /**
     * Set the request parameters.
     *
     * @param null|array<string, mixed> $params
     */
    public function setParams(?array $params): void
    {
        $this->params = $params;
        $this->extractProgressToken();
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the request ID.
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

    public function hasProgressToken(): bool
    {
        return $this->progressToken !== null;
    }

    public function getProgressToken()
    {
        return $this->progressToken;
    }

    /**
     * Set progress token.
     *
     * @param mixed $token
     */
    public function setProgressToken($token): void
    {
        if ($token !== null && ! is_string($token) && ! is_int($token)) {
            throw new InvalidArgumentException('Progress token must be string, integer, or null');
        }
        $this->progressToken = $token;

        // Update params with progress token
        if ($token !== null) {
            if ($this->params === null) {
                $this->params = [];
            }
            if (! isset($this->params['_meta'])) {
                $this->params['_meta'] = [];
            }
            $this->params['_meta']['progressToken'] = $token;
        }
    }

    public function toJsonRpc(): array
    {
        $data = [
            'jsonrpc' => $this->jsonrpc,
            'id' => (int) $this->id,
            'method' => $this->method,
        ];

        if ($this->params !== null) {
            $data['params'] = $this->params;
        }

        return $data;
    }

    /**
     * Convert to JSON string.
     */
    public function toJson(): string
    {
        return json_encode($this->toJsonRpc(), JSON_UNESCAPED_SLASHES);
    }

    /**
     * Extract progress token from params meta.
     */
    private function extractProgressToken(): void
    {
        if ($this->params !== null && isset($this->params['_meta']['progressToken'])) {
            $token = $this->params['_meta']['progressToken'];
            if (is_string($token) || is_int($token)) {
                $this->progressToken = $token;
            }
        }
    }
}
