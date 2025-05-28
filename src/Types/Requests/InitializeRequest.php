<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Requests;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Core\RequestInterface;

/**
 * Initialize request sent from client to server when first connecting.
 *
 * This request asks the server to begin initialization and negotiate capabilities.
 */
class InitializeRequest implements RequestInterface
{
    private string $method = 'initialize';

    private string $protocolVersion;

    /** @var array<string, mixed> */
    private array $capabilities;

    /** @var array<string, mixed> */
    private array $clientInfo;

    /** @var int|string */
    private $id;

    /** @var null|int|string */
    private $progressToken;

    /**
     * @param string $protocolVersion MCP protocol version
     * @param array<string, mixed> $capabilities Client capabilities
     * @param array<string, mixed> $clientInfo Client information
     * @param null|int|string $id Request ID
     */
    public function __construct(
        string $protocolVersion,
        array $capabilities,
        array $clientInfo,
        $id = null
    ) {
        $this->setProtocolVersion($protocolVersion);
        $this->capabilities = $capabilities;
        $this->clientInfo = $clientInfo;
        $this->id = $id ?? $this->generateId();
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    /** @return array<string, mixed> */
    public function getParams(): array
    {
        $params = [
            'protocolVersion' => $this->protocolVersion,
            'capabilities' => $this->capabilities,
            'clientInfo' => $this->clientInfo,
        ];

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

    public function toJsonRpc(): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $this->id,
            'method' => $this->method,
            'params' => $this->getParams(),
        ];
    }

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function setProtocolVersion(string $version): void
    {
        if (empty($version)) {
            throw ValidationError::emptyField('protocolVersion');
        }
        $this->protocolVersion = $version;
    }

    /**
     * @return array<string, mixed>
     */
    public function getCapabilities(): array
    {
        return $this->capabilities;
    }

    /**
     * @param array<string, mixed> $capabilities
     */
    public function setCapabilities(array $capabilities): void
    {
        $this->capabilities = $capabilities;
    }

    /**
     * @return array<string, mixed>
     */
    public function getClientInfo(): array
    {
        return $this->clientInfo;
    }

    /**
     * @param array<string, mixed> $clientInfo
     */
    public function setClientInfo(array $clientInfo): void
    {
        $this->clientInfo = $clientInfo;
    }

    /**
     * Create from array data.
     *
     * @param array<string, mixed> $data
     * @throws ValidationError
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['protocolVersion'])) {
            throw ValidationError::requiredFieldMissing('protocolVersion', 'InitializeRequest');
        }

        if (! isset($data['capabilities'])) {
            throw ValidationError::requiredFieldMissing('capabilities', 'InitializeRequest');
        }

        if (! isset($data['clientInfo'])) {
            throw ValidationError::requiredFieldMissing('clientInfo', 'InitializeRequest');
        }

        return new self(
            $data['protocolVersion'],
            $data['capabilities'],
            $data['clientInfo'],
            $data['id'] ?? null
        );
    }

    /**
     * Generate a unique request ID.
     */
    private function generateId(): string
    {
        return uniqid('init_', true);
    }
}
