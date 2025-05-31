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
 * Request to get a specific prompt with arguments.
 *
 * The prompt is identified by its name and can be invoked with arguments.
 */
class GetPromptRequest implements RequestInterface
{
    private string $method = ProtocolConstants::METHOD_PROMPTS_GET;

    /** @var int|string */
    private $id;

    /** @var null|int|string */
    private $progressToken;

    private string $name;

    /** @var null|array<string, mixed> */
    private ?array $arguments = null;

    /**
     * @param string $name The name of the prompt to get
     * @param null|array<string, mixed> $arguments Prompt arguments
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

    /** @return null|array<string, mixed> */
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
     * @return null|array<string, string>
     */
    public function getArguments(): ?array
    {
        return $this->arguments;
    }

    /**
     * @param null|array<string, string> $arguments
     * @throws ValidationError
     */
    public function setArguments(?array $arguments): void
    {
        if ($arguments !== null) {
            foreach ($arguments as $key => $value) {
                if (! is_string($key)) {
                    throw ValidationError::invalidFieldType(
                        'arguments key',
                        'string',
                        gettype($key)
                    );
                }
                if (! is_string($value)) {
                    throw ValidationError::invalidFieldType(
                        "arguments[{$key}]",
                        'string',
                        gettype($value)
                    );
                }
            }
        }
        $this->arguments = $arguments;
    }

    public function toJsonRpc(): array
    {
        return [
            'jsonrpc' => ProtocolConstants::JSONRPC_VERSION,
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
            throw ValidationError::requiredFieldMissing('name', 'GetPromptRequest');
        }

        $arguments = null;
        if (isset($data['params']['arguments'])) {
            if (! is_array($data['params']['arguments'])) {
                throw ValidationError::invalidFieldType('arguments', 'array', gettype($data['params']['arguments']));
            }
            $arguments = $data['params']['arguments'];
        }

        return new self(
            $data['params']['name'],
            $arguments,
            $data['id'] ?? null
        );
    }

    /**
     * Generate a unique request ID.
     */
    private function generateId(): string
    {
        return uniqid('get_prompt_', true);
    }
}
