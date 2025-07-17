<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Requests;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Constants\MethodConstants;
use Dtyq\PhpMcp\Types\Constants\ProtocolVersions;
use Dtyq\PhpMcp\Types\Core\RequestInterface;

/**
 * Request for argument autocompletion suggestions.
 *
 * Used to get completion suggestions for prompt or resource template arguments.
 * This is part of the completions capability introduced in MCP 2025-03-26.
 */
class CompleteRequest implements RequestInterface
{
    private string $method = MethodConstants::METHOD_COMPLETION_COMPLETE;

    /** @var int|string */
    private $id;

    private string $ref;

    private string $argument;

    /** @var null|array<string, mixed> */
    private ?array $meta = null;

    /**
     * @param int|string $id Request ID
     * @param string $ref Reference to the prompt or resource template
     * @param string $argument The argument name to complete
     * @param null|array<string, mixed> $meta Optional meta information
     */
    public function __construct($id, string $ref, string $argument, ?array $meta = null)
    {
        $this->setId($id);
        $this->setRef($ref);
        $this->setArgument($argument);
        $this->meta = $meta;
    }

    public function getMethod(): string
    {
        return $this->method;
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

    public function getRef(): string
    {
        return $this->ref;
    }

    public function setRef(string $ref): void
    {
        if (empty($ref)) {
            throw ValidationError::emptyField('ref');
        }
        $this->ref = $ref;
    }

    public function getArgument(): string
    {
        return $this->argument;
    }

    public function setArgument(string $argument): void
    {
        if (empty($argument)) {
            throw ValidationError::emptyField('argument');
        }
        $this->argument = $argument;
    }

    /** @return null|array<string, mixed> */
    public function getParams(): ?array
    {
        $params = [
            'ref' => $this->ref,
            'argument' => $this->argument,
        ];

        if ($this->meta !== null) {
            $params['_meta'] = $this->meta;
        }

        return $params;
    }

    /** @return array<string, mixed> */
    public function toJsonRpc(): array
    {
        return [
            'jsonrpc' => ProtocolVersions::JSONRPC_VERSION,
            'id' => $this->id,
            'method' => $this->method,
            'params' => $this->getParams(),
        ];
    }

    public function hasMeta(): bool
    {
        return $this->meta !== null;
    }

    /** @return null|array<string, mixed> */
    public function getMeta(): ?array
    {
        return $this->meta;
    }

    /** @param null|array<string, mixed> $meta */
    public function setMeta(?array $meta): void
    {
        $this->meta = $meta;
    }

    public function hasProgressToken(): bool
    {
        return false; // Completion requests don't use progress tokens
    }

    /** @return null|int|string */
    public function getProgressToken()
    {
        return null; // Completion requests don't use progress tokens
    }

    /**
     * Create from array data.
     *
     * @param array<string, mixed> $data
     * @throws ValidationError
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['id'])) {
            throw ValidationError::requiredFieldMissing('id', 'CompleteRequest');
        }

        if (! isset($data['params']['ref'])) {
            throw ValidationError::requiredFieldMissing('ref', 'CompleteRequest');
        }

        if (! isset($data['params']['argument'])) {
            throw ValidationError::requiredFieldMissing('argument', 'CompleteRequest');
        }

        if (! is_string($data['params']['ref'])) {
            throw ValidationError::invalidFieldType('ref', 'string', gettype($data['params']['ref']));
        }

        if (! is_string($data['params']['argument'])) {
            throw ValidationError::invalidFieldType('argument', 'string', gettype($data['params']['argument']));
        }

        return new self(
            $data['id'],
            $data['params']['ref'],
            $data['params']['argument'],
            $data['params']['_meta'] ?? null
        );
    }
}
