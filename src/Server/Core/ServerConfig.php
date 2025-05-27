<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Core;

/**
 * Server configuration class.
 *
 * Holds all configuration settings for the MCP server including
 * server metadata, transport settings, and operational parameters.
 */
class ServerConfig
{
    /**
     * Server name.
     */
    private string $name;

    /**
     * Server version.
     */
    private string $version;

    /**
     * Server description.
     */
    private string $description;

    /**
     * Server vendor information.
     *
     * @var array<string, string>
     */
    private array $vendor;

    /**
     * Transport configuration.
     *
     * @var array<string, mixed>
     */
    private array $transport;

    /**
     * Logging configuration.
     *
     * @var array<string, mixed>
     */
    private array $logging;

    /**
     * Performance settings.
     *
     * @var array<string, mixed>
     */
    private array $performance;

    /**
     * Security settings.
     *
     * @var array<string, mixed>
     */
    private array $security;

    /**
     * Custom settings.
     *
     * @var array<string, mixed>
     */
    private array $custom;

    /**
     * Constructor.
     *
     * @param string $name Server name
     * @param string $version Server version
     * @param string $description Server description
     * @param array<string, mixed> $options Additional configuration options
     */
    public function __construct(
        string $name,
        string $version = '1.0.0',
        string $description = '',
        array $options = []
    ) {
        $this->name = $name;
        $this->version = $version;
        $this->description = $description;

        // Set default vendor information
        $this->vendor = $options['vendor'] ?? [
            'name' => 'PHP MCP Server',
            'url' => 'https://github.com/dtyq/php-mcp',
        ];

        // Set default transport configuration
        $this->transport = $options['transport'] ?? [
            'type' => 'stdio',
            'timeout' => 30,
            'max_connections' => 100,
        ];

        // Set default logging configuration
        $this->logging = $options['logging'] ?? [
            'level' => 'info',
            'format' => 'json',
            'output' => 'stderr',
        ];

        // Set default performance settings
        $this->performance = $options['performance'] ?? [
            'max_request_size' => 1024 * 1024, // 1MB
            'request_timeout' => 30,
            'memory_limit' => 32 * 1024 * 1024, // 32MB
        ];

        // Set default security settings
        $this->security = $options['security'] ?? [
            'enable_cors' => true,
            'allowed_origins' => ['*'],
            'rate_limiting' => false,
        ];

        // Set custom settings
        $this->custom = $options['custom'] ?? [];
    }

    /**
     * Get server name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set server name.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get server version.
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Set server version.
     */
    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    /**
     * Get server description.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Set server description.
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * Get vendor information.
     *
     * @return array<string, string>
     */
    public function getVendor(): array
    {
        return $this->vendor;
    }

    /**
     * Set vendor information.
     *
     * @param array<string, string> $vendor
     */
    public function setVendor(array $vendor): void
    {
        $this->vendor = $vendor;
    }

    /**
     * Get transport configuration.
     *
     * @return array<string, mixed>
     */
    public function getTransport(): array
    {
        return $this->transport;
    }

    /**
     * Set transport configuration.
     *
     * @param array<string, mixed> $transport
     */
    public function setTransport(array $transport): void
    {
        $this->transport = array_merge($this->transport, $transport);
    }

    /**
     * Get transport type.
     */
    public function getTransportType(): string
    {
        return $this->transport['type'] ?? 'stdio';
    }

    /**
     * Get transport timeout.
     */
    public function getTransportTimeout(): int
    {
        return $this->transport['timeout'] ?? 30;
    }

    /**
     * Get max connections.
     */
    public function getMaxConnections(): int
    {
        return $this->transport['max_connections'] ?? 100;
    }

    /**
     * Get logging configuration.
     *
     * @return array<string, mixed>
     */
    public function getLogging(): array
    {
        return $this->logging;
    }

    /**
     * Set logging configuration.
     *
     * @param array<string, mixed> $logging
     */
    public function setLogging(array $logging): void
    {
        $this->logging = array_merge($this->logging, $logging);
    }

    /**
     * Get log level.
     */
    public function getLogLevel(): string
    {
        return $this->logging['level'] ?? 'info';
    }

    /**
     * Get log format.
     */
    public function getLogFormat(): string
    {
        return $this->logging['format'] ?? 'json';
    }

    /**
     * Get log output.
     */
    public function getLogOutput(): string
    {
        return $this->logging['output'] ?? 'stderr';
    }

    /**
     * Get performance settings.
     *
     * @return array<string, mixed>
     */
    public function getPerformance(): array
    {
        return $this->performance;
    }

    /**
     * Set performance settings.
     *
     * @param array<string, mixed> $performance
     */
    public function setPerformance(array $performance): void
    {
        $this->performance = array_merge($this->performance, $performance);
    }

    /**
     * Get max request size.
     */
    public function getMaxRequestSize(): int
    {
        return $this->performance['max_request_size'] ?? 1024 * 1024;
    }

    /**
     * Get request timeout.
     */
    public function getRequestTimeout(): int
    {
        return $this->performance['request_timeout'] ?? 30;
    }

    /**
     * Get memory limit.
     */
    public function getMemoryLimit(): int
    {
        return $this->performance['memory_limit'] ?? 32 * 1024 * 1024;
    }

    /**
     * Get security settings.
     *
     * @return array<string, mixed>
     */
    public function getSecurity(): array
    {
        return $this->security;
    }

    /**
     * Set security settings.
     *
     * @param array<string, mixed> $security
     */
    public function setSecurity(array $security): void
    {
        $this->security = array_merge($this->security, $security);
    }

    /**
     * Check if CORS is enabled.
     */
    public function isCorsEnabled(): bool
    {
        return $this->security['enable_cors'] ?? true;
    }

    /**
     * Get allowed origins.
     *
     * @return string[]
     */
    public function getAllowedOrigins(): array
    {
        return $this->security['allowed_origins'] ?? ['*'];
    }

    /**
     * Check if rate limiting is enabled.
     */
    public function isRateLimitingEnabled(): bool
    {
        return $this->security['rate_limiting'] ?? false;
    }

    /**
     * Get custom settings.
     *
     * @return array<string, mixed>
     */
    public function getCustom(): array
    {
        return $this->custom;
    }

    /**
     * Set custom settings.
     *
     * @param array<string, mixed> $custom
     */
    public function setCustom(array $custom): void
    {
        $this->custom = array_merge($this->custom, $custom);
    }

    /**
     * Get custom setting by key.
     */
    public function getCustomSetting(string $key, mixed $default = null): mixed
    {
        return $this->custom[$key] ?? $default;
    }

    /**
     * Set custom setting.
     */
    public function setCustomSetting(string $key, mixed $value): void
    {
        $this->custom[$key] = $value;
    }

    /**
     * Convert configuration to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'version' => $this->version,
            'description' => $this->description,
            'vendor' => $this->vendor,
            'transport' => $this->transport,
            'logging' => $this->logging,
            'performance' => $this->performance,
            'security' => $this->security,
            'custom' => $this->custom,
        ];
    }

    /**
     * Create configuration from array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'] ?? 'MCP Server',
            $data['version'] ?? '1.0.0',
            $data['description'] ?? '',
            [
                'vendor' => $data['vendor'] ?? [],
                'transport' => $data['transport'] ?? [],
                'logging' => $data['logging'] ?? [],
                'performance' => $data['performance'] ?? [],
                'security' => $data['security'] ?? [],
                'custom' => $data['custom'] ?? [],
            ]
        );
    }

    /**
     * Validate configuration.
     *
     * @return string[]
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->name)) {
            $errors[] = 'Server name cannot be empty';
        }

        if (empty($this->version)) {
            $errors[] = 'Server version cannot be empty';
        }

        if ($this->getMaxRequestSize() <= 0) {
            $errors[] = 'Max request size must be positive';
        }

        if ($this->getRequestTimeout() <= 0) {
            $errors[] = 'Request timeout must be positive';
        }

        if ($this->getMemoryLimit() <= 0) {
            $errors[] = 'Memory limit must be positive';
        }

        return $errors;
    }
}
