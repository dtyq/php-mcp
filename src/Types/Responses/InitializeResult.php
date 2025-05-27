<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Responses;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Core\ResultInterface;

/**
 * Result sent from server to client after receiving an initialize request.
 *
 * Contains the server's protocol version, capabilities, and implementation info.
 */
class InitializeResult implements ResultInterface
{
    private string $protocolVersion;

    private array $capabilities;

    private array $serverInfo;

    private ?string $instructions = null;

    private ?array $meta = null;

    /**
     * @param string $protocolVersion The MCP version the server wants to use
     * @param array<string, mixed> $capabilities Server capabilities
     * @param array<string, mixed> $serverInfo Server implementation info
     * @param null|string $instructions Optional usage instructions
     * @param null|array<string, mixed> $meta Optional meta information
     */
    public function __construct(
        string $protocolVersion,
        array $capabilities,
        array $serverInfo,
        ?string $instructions = null,
        ?array $meta = null
    ) {
        $this->setProtocolVersion($protocolVersion);
        $this->capabilities = $capabilities;
        $this->serverInfo = $serverInfo;
        $this->instructions = $instructions;
        $this->meta = $meta;
    }

    public function toArray(): array
    {
        $data = [
            'protocolVersion' => $this->protocolVersion,
            'capabilities' => $this->capabilities,
            'serverInfo' => $this->serverInfo,
        ];

        if ($this->instructions !== null) {
            $data['instructions'] = $this->instructions;
        }

        if ($this->meta !== null) {
            $data['_meta'] = $this->meta;
        }

        return $data;
    }

    public function hasMeta(): bool
    {
        return $this->meta !== null;
    }

    public function getMeta(): ?array
    {
        return $this->meta;
    }

    public function setMeta(?array $meta): void
    {
        $this->meta = $meta;
    }

    public function isPaginated(): bool
    {
        return false; // Initialize results are not paginated
    }

    public function getNextCursor(): ?string
    {
        return null; // Initialize results don't have pagination
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
    public function getServerInfo(): array
    {
        return $this->serverInfo;
    }

    /**
     * @param array<string, mixed> $serverInfo
     */
    public function setServerInfo(array $serverInfo): void
    {
        $this->serverInfo = $serverInfo;
    }

    public function getInstructions(): ?string
    {
        return $this->instructions;
    }

    public function setInstructions(?string $instructions): void
    {
        $this->instructions = $instructions;
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
            throw ValidationError::requiredFieldMissing('protocolVersion', 'InitializeResult');
        }

        if (! isset($data['capabilities'])) {
            throw ValidationError::requiredFieldMissing('capabilities', 'InitializeResult');
        }

        if (! isset($data['serverInfo'])) {
            throw ValidationError::requiredFieldMissing('serverInfo', 'InitializeResult');
        }

        return new self(
            $data['protocolVersion'],
            $data['capabilities'],
            $data['serverInfo'],
            $data['instructions'] ?? null,
            $data['_meta'] ?? null
        );
    }
}
