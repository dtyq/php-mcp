<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Requests;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Core\RequestInterface;

/**
 * Request to read the contents of a specific resource.
 *
 * The resource is identified by its URI.
 */
class ReadResourceRequest implements RequestInterface
{
    private string $method = 'resources/read';

    private $id;

    private $progressToken;

    private string $uri;

    /**
     * @param string $uri The URI of the resource to read
     * @param null|int|string $id Request ID
     */
    public function __construct(string $uri, $id = null)
    {
        $this->setUri($uri);
        $this->id = $id ?? $this->generateId();
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getParams(): ?array
    {
        $params = [
            'uri' => $this->uri,
        ];

        if ($this->progressToken !== null) {
            $params['_meta'] = ['progressToken' => $this->progressToken];
        }

        return $params;
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

    public function getUri(): string
    {
        return $this->uri;
    }

    public function setUri(string $uri): void
    {
        if (empty($uri)) {
            throw ValidationError::emptyField('uri');
        }
        $this->uri = $uri;
    }

    public function toJsonRpc(): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $this->id,
            'method' => $this->method,
            'params' => $this->getParams(),
        ];
    }

    /**
     * Create from array data.
     *
     * @param array<string, mixed> $data
     * @throws ValidationError
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['params']['uri'])) {
            throw ValidationError::requiredFieldMissing('uri', 'ReadResourceRequest');
        }

        return new self(
            $data['params']['uri'],
            $data['id'] ?? null
        );
    }

    /**
     * Generate a unique request ID.
     */
    private function generateId(): string
    {
        return uniqid('read_resource_', true);
    }
}
