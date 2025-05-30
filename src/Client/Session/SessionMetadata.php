<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client\Session;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;

/**
 * Container for session metadata and configuration.
 *
 * This class holds session-specific configuration such as timeouts,
 * client information, and other metadata used during session management.
 */
class SessionMetadata
{
    /**
     * Default metadata values.
     */
    public const DEFAULTS = [
        'responseTimeout' => 30.0,
        'initializationTimeout' => 60.0,
        'clientName' => 'php-mcp-client',
        'clientVersion' => '1.0.0',
        'clientCapabilities' => [],
        'additionalMetadata' => [],
    ];

    /** @var float Response timeout in seconds */
    private float $responseTimeout;

    /** @var float Maximum time to wait for initialization */
    private float $initializationTimeout;

    /** @var string Client name for identification */
    private string $clientName;

    /** @var string Client version */
    private string $clientVersion;

    /** @var array<string, mixed> Client capabilities */
    private array $clientCapabilities;

    /** @var array<string, mixed> Additional metadata */
    private array $additionalMetadata;

    /**
     * @param float $responseTimeout Response timeout in seconds
     * @param float $initializationTimeout Initialization timeout in seconds
     * @param string $clientName Client name for identification
     * @param string $clientVersion Client version
     * @param array<string, mixed> $clientCapabilities Client capabilities
     * @param array<string, mixed> $additionalMetadata Additional metadata
     */
    public function __construct(
        float $responseTimeout = self::DEFAULTS['responseTimeout'],
        float $initializationTimeout = self::DEFAULTS['initializationTimeout'],
        string $clientName = self::DEFAULTS['clientName'],
        string $clientVersion = self::DEFAULTS['clientVersion'],
        array $clientCapabilities = self::DEFAULTS['clientCapabilities'],
        array $additionalMetadata = self::DEFAULTS['additionalMetadata']
    ) {
        $this->setResponseTimeout($responseTimeout);
        $this->setInitializationTimeout($initializationTimeout);
        $this->setClientName($clientName);
        $this->setClientVersion($clientVersion);
        $this->setClientCapabilities($clientCapabilities);
        $this->setAdditionalMetadata($additionalMetadata);
    }

    /**
     * Create metadata from array.
     *
     * @param array<string, mixed> $data Metadata array
     * @throws ValidationError If data is invalid
     */
    public static function fromArray(array $data): self
    {
        // Merge with defaults to ensure all required keys are present
        $data = array_merge(self::DEFAULTS, $data);

        return new self(
            $data['responseTimeout'],
            $data['initializationTimeout'],
            $data['clientName'],
            $data['clientVersion'],
            $data['clientCapabilities'],
            $data['additionalMetadata']
        );
    }

    /**
     * Convert metadata to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'responseTimeout' => $this->responseTimeout,
            'initializationTimeout' => $this->initializationTimeout,
            'clientName' => $this->clientName,
            'clientVersion' => $this->clientVersion,
            'clientCapabilities' => $this->clientCapabilities,
            'additionalMetadata' => $this->additionalMetadata,
        ];
    }

    // Getters
    public function getResponseTimeout(): float
    {
        return $this->responseTimeout;
    }

    public function getInitializationTimeout(): float
    {
        return $this->initializationTimeout;
    }

    public function getClientName(): string
    {
        return $this->clientName;
    }

    public function getClientVersion(): string
    {
        return $this->clientVersion;
    }

    /**
     * @return array<string, mixed>
     */
    public function getClientCapabilities(): array
    {
        return $this->clientCapabilities;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAdditionalMetadata(): array
    {
        return $this->additionalMetadata;
    }

    /**
     * Get a specific metadata value.
     *
     * @param string $key The metadata key
     * @param mixed $default Default value if key not found
     * @return mixed The metadata value
     */
    public function getMetadata(string $key, $default = null)
    {
        return $this->additionalMetadata[$key] ?? $default;
    }

    /**
     * Check if a metadata key exists.
     *
     * @param string $key The metadata key
     * @return bool True if key exists
     */
    public function hasMetadata(string $key): bool
    {
        return isset($this->additionalMetadata[$key]);
    }

    // Setters with validation
    public function setResponseTimeout(float $responseTimeout): void
    {
        if ($responseTimeout <= 0) {
            throw ValidationError::invalidFieldValue(
                'responseTimeout',
                'must be greater than 0',
                ['value' => $responseTimeout]
            );
        }
        $this->responseTimeout = $responseTimeout;
    }

    public function setInitializationTimeout(float $initializationTimeout): void
    {
        if ($initializationTimeout <= 0) {
            throw ValidationError::invalidFieldValue(
                'initializationTimeout',
                'must be greater than 0',
                ['value' => $initializationTimeout]
            );
        }
        $this->initializationTimeout = $initializationTimeout;
    }

    public function setClientName(string $clientName): void
    {
        if (empty($clientName)) {
            throw ValidationError::emptyField('clientName');
        }
        $this->clientName = $clientName;
    }

    public function setClientVersion(string $clientVersion): void
    {
        if (empty($clientVersion)) {
            throw ValidationError::emptyField('clientVersion');
        }
        $this->clientVersion = $clientVersion;
    }

    /**
     * @param array<string, mixed> $clientCapabilities
     */
    public function setClientCapabilities(array $clientCapabilities): void
    {
        $this->clientCapabilities = $clientCapabilities;
    }

    /**
     * @param array<string, mixed> $additionalMetadata
     */
    public function setAdditionalMetadata(array $additionalMetadata): void
    {
        $this->additionalMetadata = $additionalMetadata;
    }

    /**
     * Set a specific metadata value.
     *
     * @param string $key The metadata key
     * @param mixed $value The metadata value
     */
    public function setMetadata(string $key, $value): void
    {
        $this->additionalMetadata[$key] = $value;
    }

    /**
     * Remove a metadata key.
     *
     * @param string $key The metadata key to remove
     */
    public function removeMetadata(string $key): void
    {
        unset($this->additionalMetadata[$key]);
    }

    /**
     * Create client info array for MCP initialization.
     *
     * @return array<string, mixed>
     */
    public function createClientInfo(): array
    {
        return [
            'name' => $this->clientName,
            'version' => $this->clientVersion,
        ];
    }

    /**
     * Validate the complete metadata.
     *
     * @throws ValidationError If metadata is invalid
     */
    public function validate(): void
    {
        // Re-validate all fields to ensure consistency
        $this->setResponseTimeout($this->responseTimeout);
        $this->setInitializationTimeout($this->initializationTimeout);
        $this->setClientName($this->clientName);
        $this->setClientVersion($this->clientVersion);
    }

    /**
     * Create a copy of this metadata with modified values.
     *
     * @param array<string, mixed> $changes Values to change
     * @return self New metadata instance
     */
    public function withChanges(array $changes): self
    {
        $data = $this->toArray();
        $data = array_merge($data, $changes);
        return self::fromArray($data);
    }
}
