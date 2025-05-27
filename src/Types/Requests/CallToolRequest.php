<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Requests;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Core\RequestInterface;

/**
 * Request to call/execute a specific tool.
 *
 * Contains the tool name and arguments for execution.
 */
class CallToolRequest implements RequestInterface
{
    private string $method = 'tools/call';

    private $id;

    private $progressToken;

    private string $name;

    private ?array $arguments = null;

    /**
     * @param string $name The name of the tool to call
     * @param null|array<string, mixed> $arguments Tool arguments
     * @param null|int|string $id Request ID
     */
    public function __construct(string $name, ?array $arguments = null, $id = null)
    {
        $this->setName($name);
        $this->arguments = $arguments;
        $this->id = $id ?? $this->generateId();
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getParams(): ?array
    {
        $params = [
            'name' => $this->name,
        ];

        if ($this->arguments !== null) {
            $params['arguments'] = $this->arguments;
        }

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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        if (empty($name)) {
            throw ValidationError::emptyField('name');
        }
        $this->name = $name;
    }

    /**
     * @return null|array<string, mixed>
     */
    public function getArguments(): ?array
    {
        return $this->arguments;
    }

    /**
     * @param null|array<string, mixed> $arguments
     */
    public function setArguments(?array $arguments): void
    {
        $this->arguments = $arguments;
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
        if (! isset($data['params']['name'])) {
            throw ValidationError::requiredFieldMissing('name', 'CallToolRequest');
        }

        return new self(
            $data['params']['name'],
            $data['params']['arguments'] ?? null,
            $data['id'] ?? null
        );
    }

    /**
     * Generate a unique request ID.
     */
    private function generateId(): string
    {
        return uniqid('call_tool_', true);
    }
}
