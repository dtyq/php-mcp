<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client\Configuration;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Constants\TransportTypes;

/**
 * Configuration for MCP client instances.
 *
 * This class holds all configuration options for client behavior,
 * transport settings, and protocol parameters.
 */
class ClientConfig
{
    /**
     * Default configuration values.
     */
    public const DEFAULTS = [
        'transport_type' => TransportTypes::TRANSPORT_TYPE_STDIO,
        'transport_config' => [],
        'default_timeout' => 30,
        'max_retries' => 3,
        'client_name' => 'php-mcp-client',
        'client_version' => '1.0.0',
        'capabilities' => [],
        'debug' => false,
    ];

    /** @var string The transport type (stdio, http, websocket, etc.) */
    private string $transportType;

    /** @var array<string, mixed> Transport-specific configuration */
    private array $transportConfig;

    /** @var int Default timeout in seconds for requests */
    private int $defaultTimeout;

    /** @var int Maximum number of connection retry attempts */
    private int $maxRetries;

    /** @var string Client name for identification */
    private string $clientName;

    /** @var string Client version */
    private string $clientVersion;

    /** @var array<string, mixed> Client capabilities */
    private array $capabilities;

    /** @var bool Enable debug logging */
    private bool $debug;

    /**
     * @param string $transportType Transport type identifier
     * @param array<string, mixed> $transportConfig Transport-specific configuration
     * @param int $defaultTimeout Default request timeout in seconds
     * @param int $maxRetries Maximum retry attempts
     * @param string $clientName Client identification name
     * @param string $clientVersion Client version string
     * @param array<string, mixed> $capabilities Client capabilities
     * @param bool $debug Enable debug mode
     */
    public function __construct(
        string $transportType = self::DEFAULTS['transport_type'],
        array $transportConfig = self::DEFAULTS['transport_config'],
        int $defaultTimeout = self::DEFAULTS['default_timeout'],
        int $maxRetries = self::DEFAULTS['max_retries'],
        string $clientName = self::DEFAULTS['client_name'],
        string $clientVersion = self::DEFAULTS['client_version'],
        array $capabilities = self::DEFAULTS['capabilities'],
        bool $debug = self::DEFAULTS['debug']
    ) {
        $this->setTransportType($transportType);
        $this->setTransportConfig($transportConfig);
        $this->setDefaultTimeout($defaultTimeout);
        $this->setMaxRetries($maxRetries);
        $this->setClientName($clientName);
        $this->setClientVersion($clientVersion);
        $this->setCapabilities($capabilities);
        $this->setDebug($debug);
    }

    /**
     * Create configuration from array.
     *
     * @param array<string, mixed> $config Configuration array
     * @throws ValidationError If configuration is invalid
     */
    public static function fromArray(array $config): self
    {
        // Merge with defaults to ensure all required keys are present
        $config = array_merge(self::DEFAULTS, $config);

        return new self(
            $config['transport_type'],
            $config['transport_config'],
            $config['default_timeout'],
            $config['max_retries'],
            $config['client_name'],
            $config['client_version'],
            $config['capabilities'],
            $config['debug']
        );
    }

    /**
     * Convert configuration to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'transport_type' => $this->transportType,
            'transport_config' => $this->transportConfig,
            'default_timeout' => $this->defaultTimeout,
            'max_retries' => $this->maxRetries,
            'client_name' => $this->clientName,
            'client_version' => $this->clientVersion,
            'capabilities' => $this->capabilities,
            'debug' => $this->debug,
        ];
    }

    // Getters
    public function getTransportType(): string
    {
        return $this->transportType;
    }

    /**
     * @return array<string, mixed>
     */
    public function getTransportConfig(): array
    {
        return $this->transportConfig;
    }

    public function getDefaultTimeout(): int
    {
        return $this->defaultTimeout;
    }

    public function getMaxRetries(): int
    {
        return $this->maxRetries;
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
    public function getCapabilities(): array
    {
        return $this->capabilities;
    }

    public function isDebug(): bool
    {
        return $this->debug;
    }

    // Setters with validation
    public function setTransportType(string $transportType): void
    {
        if (empty($transportType)) {
            throw ValidationError::emptyField('transport_type');
        }
        $this->transportType = $transportType;
    }

    /**
     * @param array<string, mixed> $transportConfig
     */
    public function setTransportConfig(array $transportConfig): void
    {
        $this->transportConfig = $transportConfig;
    }

    public function setDefaultTimeout(int $defaultTimeout): void
    {
        if ($defaultTimeout <= 0) {
            throw ValidationError::invalidFieldValue(
                'default_timeout',
                'must be greater than 0',
                ['value' => $defaultTimeout]
            );
        }
        $this->defaultTimeout = $defaultTimeout;
    }

    public function setMaxRetries(int $maxRetries): void
    {
        if ($maxRetries < 0) {
            throw ValidationError::invalidFieldValue(
                'max_retries',
                'must be non-negative',
                ['value' => $maxRetries]
            );
        }
        $this->maxRetries = $maxRetries;
    }

    public function setClientName(string $clientName): void
    {
        if (empty($clientName)) {
            throw ValidationError::emptyField('client_name');
        }
        $this->clientName = $clientName;
    }

    public function setClientVersion(string $clientVersion): void
    {
        if (empty($clientVersion)) {
            throw ValidationError::emptyField('client_version');
        }
        $this->clientVersion = $clientVersion;
    }

    /**
     * @param array<string, mixed> $capabilities
     */
    public function setCapabilities(array $capabilities): void
    {
        $this->capabilities = $capabilities;
    }

    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }

    /**
     * Validate the complete configuration.
     *
     * @throws ValidationError If configuration is invalid
     */
    public function validate(): void
    {
        // Re-validate all fields to ensure consistency
        $this->setTransportType($this->transportType);
        $this->setDefaultTimeout($this->defaultTimeout);
        $this->setMaxRetries($this->maxRetries);
        $this->setClientName($this->clientName);
        $this->setClientVersion($this->clientVersion);
    }

    /**
     * Create a copy of this configuration with modified values.
     *
     * @param array<string, mixed> $changes Values to change
     * @return self New configuration instance
     */
    public function withChanges(array $changes): self
    {
        $config = $this->toArray();
        $config = array_merge($config, $changes);
        return self::fromArray($config);
    }
}
