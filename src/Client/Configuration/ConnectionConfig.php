<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client\Configuration;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Constants\TransportTypes;
use JsonSerializable;

/**
 * Unified connection configuration for MCP client.
 * MCP 客户端的统一连接配置。
 *
 * This class provides a type-safe way to configure MCP client connections
 * for different transport types (stdio, http) while maintaining backward
 * compatibility with array-based configuration.
 * 此类提供了一种类型安全的方式来配置不同传输类型（stdio、http）的 MCP 客户端连接，
 * 同时保持与基于数组的配置的向后兼容性。
 */
class ConnectionConfig implements JsonSerializable
{
    /**
     * Transport type identifier.
     * 传输类型标识符。
     *
     * Specifies which transport protocol to use for the connection.
     * Currently supports 'stdio' and 'http' transport types.
     * 指定连接使用的传输协议。目前支持 'stdio' 和 'http' 传输类型。
     */
    private string $transportType;

    /**
     * Transport-specific configuration.
     * 传输特定配置。
     *
     * Contains the configuration object specific to the chosen transport type.
     * This will be either a StdioConfig or HttpConfig instance.
     * 包含特定于所选传输类型的配置对象。这将是 StdioConfig 或 HttpConfig 实例。
     */
    private TransportConfigInterface $transportConfig;

    /**
     * @param string $transportType Transport type (stdio, http)
     * @param TransportConfigInterface $transportConfig Transport-specific configuration
     */
    public function __construct(string $transportType, TransportConfigInterface $transportConfig)
    {
        $this->setTransportType($transportType);
        $this->setTransportConfig($transportConfig);
    }

    /**
     * Create stdio connection configuration.
     *
     * @param array<string>|string $command Command to execute
     * @param null|array<string> $args Command arguments (optional if command is array)
     * @param float $readTimeout Read timeout in seconds
     * @param float $writeTimeout Write timeout in seconds
     * @param float $shutdownTimeout Shutdown timeout in seconds
     * @param int $bufferSize Buffer size for stream operations
     * @param bool $inheritEnvironment Whether to inherit environment variables
     * @param bool $validateMessages Whether to validate messages
     * @param bool $captureStderr Whether to capture stderr
     * @param array<string, string> $env Custom environment variables
     */
    public static function stdio(
        $command,
        ?array $args = null,
        float $readTimeout = 30.0,
        float $writeTimeout = 10.0,
        float $shutdownTimeout = 5.0,
        int $bufferSize = 8192,
        bool $inheritEnvironment = true,
        bool $validateMessages = true,
        bool $captureStderr = true,
        array $env = []
    ): self {
        // Create stdio config with all settings including command
        $stdioConfig = new StdioConfig(
            $command,
            $args,
            $readTimeout,
            $writeTimeout,
            $shutdownTimeout,
            $bufferSize,
            $inheritEnvironment,
            $validateMessages,
            $captureStderr,
            $env
        );

        return new self(TransportTypes::TRANSPORT_TYPE_STDIO, $stdioConfig);
    }

    /**
     * Create HTTP connection configuration.
     *
     * @param string $baseUrl Server base URL
     * @param float $timeout Request timeout in seconds
     * @param float $sseTimeout SSE stream timeout in seconds
     * @param int $maxRetries Maximum retry attempts
     * @param float $retryDelay Initial retry delay in seconds
     * @param bool $validateSsl SSL certificate validation
     * @param string $userAgent User agent string
     * @param array<string, string> $headers Custom headers
     * @param null|array<string, mixed> $auth Authentication configuration
     * @param string $protocolVersion Protocol version
     * @param bool $enableResumption Enable event replay mechanism
     * @param string $eventStoreType Event store type
     * @param array<string, mixed> $eventStoreConfig Event store configuration
     * @param bool $jsonResponseMode Use JSON response instead of SSE
     * @param bool $terminateOnClose Send termination request on close
     */
    public static function http(
        string $baseUrl,
        float $timeout = 30.0,
        float $sseTimeout = 300.0,
        int $maxRetries = 3,
        float $retryDelay = 1.0,
        bool $validateSsl = true,
        string $userAgent = 'PHP-MCP-Client/1.0',
        array $headers = [],
        ?array $auth = null,
        string $protocolVersion = '2024-11-05',
        bool $enableResumption = false,
        string $eventStoreType = 'memory',
        array $eventStoreConfig = [],
        bool $jsonResponseMode = false,
        bool $terminateOnClose = true
    ): self {
        $httpConfig = new HttpConfig(
            $baseUrl,
            $timeout,
            $sseTimeout,
            $maxRetries,
            $retryDelay,
            $validateSsl,
            $userAgent,
            $headers,
            $auth,
            $protocolVersion,
            $enableResumption,
            $eventStoreType,
            $eventStoreConfig,
            $jsonResponseMode,
            $terminateOnClose
        );

        return new self(TransportTypes::TRANSPORT_TYPE_HTTP, $httpConfig);
    }

    /**
     * Create configuration from transport type and config array.
     *
     * @param string $transportType Transport type
     * @param array<string, mixed> $config Configuration array
     * @throws ValidationError If transport type is invalid or config is malformed
     */
    public static function fromArray(string $transportType, array $config): self
    {
        switch ($transportType) {
            case TransportTypes::TRANSPORT_TYPE_STDIO:
                return new self($transportType, StdioConfig::fromArray($config));
            case TransportTypes::TRANSPORT_TYPE_HTTP:
                return new self($transportType, HttpConfig::fromArray($config));
            default:
                throw ValidationError::invalidFieldValue(
                    'transportType',
                    'Unsupported transport type',
                    ['type' => $transportType]
                );
        }
    }

    /**
     * Get transport type.
     */
    public function getTransportType(): string
    {
        return $this->transportType;
    }

    /**
     * Get transport configuration.
     */
    public function getTransportConfig(): TransportConfigInterface
    {
        return $this->transportConfig;
    }

    /**
     * Get stdio configuration (if applicable).
     * 获取 stdio 配置（如果适用）。
     *
     * @throws ValidationError If not stdio transport / 如果不是 stdio 传输
     */
    public function getStdioConfig(): StdioConfig
    {
        if ($this->transportType !== TransportTypes::TRANSPORT_TYPE_STDIO) {
            throw ValidationError::invalidFieldValue(
                'transportType',
                'Expected stdio transport',
                ['actual' => $this->transportType]
            );
        }
        if (! $this->transportConfig instanceof StdioConfig) {
            throw ValidationError::invalidFieldValue(
                'transportConfig',
                'Expected StdioConfig',
                ['actual' => get_class($this->transportConfig)]
            );
        }

        /* @var StdioConfig */
        return $this->transportConfig;
    }

    /**
     * Get HTTP configuration (if applicable).
     * 获取 HTTP 配置（如果适用）。
     *
     * @throws ValidationError If not HTTP transport / 如果不是 HTTP 传输
     */
    public function getHttpConfig(): HttpConfig
    {
        if ($this->transportType !== TransportTypes::TRANSPORT_TYPE_HTTP) {
            throw ValidationError::invalidFieldValue(
                'transportType',
                'Expected HTTP transport',
                ['actual' => $this->transportType]
            );
        }
        if (! $this->transportConfig instanceof HttpConfig) {
            throw ValidationError::invalidFieldValue(
                'transportConfig',
                'Expected HttpConfig',
                ['actual' => get_class($this->transportConfig)]
            );
        }

        /* @var HttpConfig */
        return $this->transportConfig;
    }

    /**
     * Check if this is a stdio configuration.
     */
    public function isStdio(): bool
    {
        return $this->transportType === TransportTypes::TRANSPORT_TYPE_STDIO;
    }

    /**
     * Check if this is an HTTP configuration.
     */
    public function isHttp(): bool
    {
        return $this->transportType === TransportTypes::TRANSPORT_TYPE_HTTP;
    }

    /**
     * Convert to array format (for backward compatibility).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->transportConfig->toArray();
    }

    /**
     * JSON serialization support.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'transport_type' => $this->transportType,
            'transport_config' => $this->transportConfig->toArray(),
        ];
    }

    /**
     * Set transport type with validation.
     *
     * @param string $transportType Transport type
     * @throws ValidationError If transport type is invalid
     */
    private function setTransportType(string $transportType): void
    {
        if (! TransportTypes::isValidTransportType($transportType)) {
            throw ValidationError::invalidFieldValue(
                'transportType',
                'Invalid transport type',
                [
                    'type' => $transportType,
                    'supported' => TransportTypes::getSupportedTransportTypes(),
                ]
            );
        }

        $this->transportType = $transportType;
    }

    /**
     * Set transport configuration with validation.
     *
     * @param TransportConfigInterface $transportConfig Transport configuration
     * @throws ValidationError If configuration type doesn't match transport type
     */
    private function setTransportConfig(TransportConfigInterface $transportConfig): void
    {
        // Validate configuration type matches transport type
        if ($this->transportType === TransportTypes::TRANSPORT_TYPE_STDIO && ! ($transportConfig instanceof StdioConfig)) {
            throw ValidationError::invalidFieldType(
                'transportConfig',
                'StdioConfig',
                get_class($transportConfig)
            );
        }

        if ($this->transportType === TransportTypes::TRANSPORT_TYPE_HTTP && ! ($transportConfig instanceof HttpConfig)) {
            throw ValidationError::invalidFieldType(
                'transportConfig',
                'HttpConfig',
                get_class($transportConfig)
            );
        }

        $this->transportConfig = $transportConfig;
    }
}
