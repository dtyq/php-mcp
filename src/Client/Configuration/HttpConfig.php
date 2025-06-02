<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client\Configuration;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use JsonSerializable;

/**
 * Configuration for HTTP transport.
 *
 * This class holds all HTTP-specific configuration options including
 * protocol version, authentication, event replay, timeouts, and other
 * transport-specific settings for MCP Streamable HTTP client.
 */
class HttpConfig implements JsonSerializable
{
    /**
     * Default configuration values.
     */
    public const DEFAULTS = [
        'base_url' => null,                     // Server base URL (required)
        'timeout' => 30.0,                     // Request timeout in seconds
        'sse_timeout' => 300.0,                // SSE stream timeout in seconds
        'max_retries' => 3,                    // Maximum retry attempts
        'retry_delay' => 1.0,                  // Initial retry delay in seconds
        'validate_ssl' => true,                // SSL certificate validation
        'user_agent' => 'php-mcp-client/1.0',  // User agent string
        'headers' => [],                       // Custom headers
        'auth' => null,                        // Authentication configuration
        'protocol_version' => 'auto',          // Protocol version: 'auto', '2025-03-26', '2024-11-05'
        'enable_resumption' => true,           // Enable event replay mechanism
        'event_store_type' => 'memory',        // Event store type: 'memory', 'file', 'redis'
        'event_store_config' => [],            // Event store configuration
        'json_response_mode' => false,         // Use JSON response instead of SSE
        'terminate_on_close' => true,          // Send termination request on close
    ];

    /** @var null|string Server base URL */
    private ?string $baseUrl;

    /** @var float Request timeout in seconds */
    private float $timeout;

    /** @var float SSE stream timeout in seconds */
    private float $sseTimeout;

    /** @var int Maximum retry attempts */
    private int $maxRetries;

    /** @var float Initial retry delay in seconds */
    private float $retryDelay;

    /** @var bool SSL certificate validation */
    private bool $validateSsl;

    /** @var string User agent string */
    private string $userAgent;

    /** @var array<string, string> Custom headers */
    private array $headers;

    /** @var null|array<string, mixed> Authentication configuration */
    private ?array $auth;

    /** @var string Protocol version */
    private string $protocolVersion;

    /** @var bool Enable event replay mechanism */
    private bool $enableResumption;

    /** @var string Event store type */
    private string $eventStoreType;

    /** @var array<string, mixed> Event store configuration */
    private array $eventStoreConfig;

    /** @var bool Use JSON response instead of SSE */
    private bool $jsonResponseMode;

    /** @var bool Send termination request on close */
    private bool $terminateOnClose;

    /**
     * @param null|string $baseUrl Server base URL
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
    public function __construct(
        ?string $baseUrl = self::DEFAULTS['base_url'],
        float $timeout = self::DEFAULTS['timeout'],
        float $sseTimeout = self::DEFAULTS['sse_timeout'],
        int $maxRetries = self::DEFAULTS['max_retries'],
        float $retryDelay = self::DEFAULTS['retry_delay'],
        bool $validateSsl = self::DEFAULTS['validate_ssl'],
        string $userAgent = self::DEFAULTS['user_agent'],
        array $headers = self::DEFAULTS['headers'],
        ?array $auth = self::DEFAULTS['auth'],
        string $protocolVersion = self::DEFAULTS['protocol_version'],
        bool $enableResumption = self::DEFAULTS['enable_resumption'],
        string $eventStoreType = self::DEFAULTS['event_store_type'],
        array $eventStoreConfig = self::DEFAULTS['event_store_config'],
        bool $jsonResponseMode = self::DEFAULTS['json_response_mode'],
        bool $terminateOnClose = self::DEFAULTS['terminate_on_close']
    ) {
        $this->setBaseUrl($baseUrl);
        $this->setTimeout($timeout);
        $this->setSseTimeout($sseTimeout);
        $this->setMaxRetries($maxRetries);
        $this->setRetryDelay($retryDelay);
        $this->setValidateSsl($validateSsl);
        $this->setUserAgent($userAgent);
        $this->setHeaders($headers);
        $this->setAuth($auth);
        $this->setProtocolVersion($protocolVersion);
        $this->setEnableResumption($enableResumption);
        $this->setEventStoreType($eventStoreType);
        $this->setEventStoreConfig($eventStoreConfig);
        $this->setJsonResponseMode($jsonResponseMode);
        $this->setTerminateOnClose($terminateOnClose);
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
            $config['base_url'],
            $config['timeout'],
            $config['sse_timeout'],
            $config['max_retries'],
            $config['retry_delay'],
            $config['validate_ssl'],
            $config['user_agent'],
            $config['headers'],
            $config['auth'],
            $config['protocol_version'],
            $config['enable_resumption'],
            $config['event_store_type'],
            $config['event_store_config'],
            $config['json_response_mode'],
            $config['terminate_on_close']
        );
    }

    /**
     * Get default configuration values.
     *
     * @return array<string, mixed>
     */
    public static function getDefaults(): array
    {
        return self::DEFAULTS;
    }

    /**
     * Convert configuration to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'base_url' => $this->baseUrl,
            'timeout' => $this->timeout,
            'sse_timeout' => $this->sseTimeout,
            'max_retries' => $this->maxRetries,
            'retry_delay' => $this->retryDelay,
            'validate_ssl' => $this->validateSsl,
            'user_agent' => $this->userAgent,
            'headers' => $this->headers,
            'auth' => $this->auth,
            'protocol_version' => $this->protocolVersion,
            'enable_resumption' => $this->enableResumption,
            'event_store_type' => $this->eventStoreType,
            'event_store_config' => $this->eventStoreConfig,
            'json_response_mode' => $this->jsonResponseMode,
            'terminate_on_close' => $this->terminateOnClose,
        ];
    }

    // Getters
    public function getBaseUrl(): ?string
    {
        return $this->baseUrl;
    }

    public function getTimeout(): float
    {
        return $this->timeout;
    }

    public function getSseTimeout(): float
    {
        return $this->sseTimeout;
    }

    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    public function getRetryDelay(): float
    {
        return $this->retryDelay;
    }

    public function getValidateSsl(): bool
    {
        return $this->validateSsl;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return null|array<string, mixed>
     */
    public function getAuth(): ?array
    {
        return $this->auth;
    }

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function isResumptionEnabled(): bool
    {
        return $this->enableResumption;
    }

    public function getEventStoreType(): string
    {
        return $this->eventStoreType;
    }

    /**
     * @return array<string, mixed>
     */
    public function getEventStoreConfig(): array
    {
        return $this->eventStoreConfig;
    }

    public function isJsonResponseMode(): bool
    {
        return $this->jsonResponseMode;
    }

    public function shouldTerminateOnClose(): bool
    {
        return $this->terminateOnClose;
    }

    // Setters with validation
    public function setBaseUrl(?string $baseUrl): void
    {
        if ($baseUrl !== null && empty(trim($baseUrl))) {
            throw ValidationError::invalidFieldValue(
                'base_url',
                'cannot be empty when provided',
                ['value' => $baseUrl]
            );
        }

        if ($baseUrl !== null && ! filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            throw ValidationError::invalidFieldValue(
                'base_url',
                'must be a valid URL',
                ['value' => $baseUrl]
            );
        }

        $this->baseUrl = $baseUrl;
    }

    public function setTimeout(float $timeout): void
    {
        if ($timeout <= 0) {
            throw ValidationError::invalidFieldValue(
                'timeout',
                'must be greater than 0',
                ['value' => $timeout]
            );
        }
        $this->timeout = $timeout;
    }

    public function setSseTimeout(float $sseTimeout): void
    {
        if ($sseTimeout <= 0) {
            throw ValidationError::invalidFieldValue(
                'sse_timeout',
                'must be greater than 0',
                ['value' => $sseTimeout]
            );
        }
        $this->sseTimeout = $sseTimeout;
    }

    public function setMaxRetries(int $maxRetries): void
    {
        if ($maxRetries < 0) {
            throw ValidationError::invalidFieldValue(
                'max_retries',
                'cannot be negative',
                ['value' => $maxRetries]
            );
        }
        $this->maxRetries = $maxRetries;
    }

    public function setRetryDelay(float $retryDelay): void
    {
        if ($retryDelay < 0) {
            throw ValidationError::invalidFieldValue(
                'retry_delay',
                'cannot be negative',
                ['value' => $retryDelay]
            );
        }
        $this->retryDelay = $retryDelay;
    }

    public function setValidateSsl(bool $validateSsl): void
    {
        $this->validateSsl = $validateSsl;
    }

    public function setUserAgent(string $userAgent): void
    {
        if (empty(trim($userAgent))) {
            throw ValidationError::invalidFieldValue(
                'user_agent',
                'cannot be empty',
                ['value' => $userAgent]
            );
        }
        $this->userAgent = $userAgent;
    }

    /**
     * @param array<string, string> $headers
     */
    public function setHeaders(array $headers): void
    {
        // Validate headers format
        foreach ($headers as $key => $value) {
            if (! is_string($key) || ! is_string($value)) {
                throw ValidationError::invalidFieldValue(
                    'headers',
                    'must be an array of string key-value pairs',
                    ['headers' => $headers]
                );
            }
        }
        $this->headers = $headers;
    }

    /**
     * @param null|array<string, mixed> $auth
     */
    public function setAuth(?array $auth): void
    {
        if ($auth !== null) {
            $this->validateAuthConfig($auth);
        }
        $this->auth = $auth;
    }

    public function setProtocolVersion(string $protocolVersion): void
    {
        $validVersions = ['auto', '2025-03-26', '2024-11-05'];
        if (! in_array($protocolVersion, $validVersions, true)) {
            throw ValidationError::invalidFieldValue(
                'protocol_version',
                'must be one of: ' . implode(', ', $validVersions),
                ['value' => $protocolVersion, 'valid' => $validVersions]
            );
        }
        $this->protocolVersion = $protocolVersion;
    }

    public function setEnableResumption(bool $enableResumption): void
    {
        $this->enableResumption = $enableResumption;
    }

    public function setEventStoreType(string $eventStoreType): void
    {
        $validTypes = ['memory', 'file', 'redis'];
        if (! in_array($eventStoreType, $validTypes, true)) {
            throw ValidationError::invalidFieldValue(
                'event_store_type',
                'must be one of: ' . implode(', ', $validTypes),
                ['value' => $eventStoreType, 'valid' => $validTypes]
            );
        }
        $this->eventStoreType = $eventStoreType;
    }

    /**
     * @param array<string, mixed> $eventStoreConfig
     */
    public function setEventStoreConfig(array $eventStoreConfig): void
    {
        $this->eventStoreConfig = $eventStoreConfig;
    }

    public function setJsonResponseMode(bool $jsonResponseMode): void
    {
        $this->jsonResponseMode = $jsonResponseMode;
    }

    public function setTerminateOnClose(bool $terminateOnClose): void
    {
        $this->terminateOnClose = $terminateOnClose;
    }

    /**
     * Validate the complete configuration.
     *
     * @throws ValidationError If configuration is invalid
     */
    public function validate(): void
    {
        // Base URL is required for HTTP transport
        if ($this->baseUrl === null) {
            throw ValidationError::emptyField('base_url');
        }

        // Validate authentication if provided
        if ($this->auth !== null) {
            $this->validateAuthConfig($this->auth);
        }

        // Additional validation can be added here
    }

    /**
     * Create a new configuration with the specified changes.
     *
     * @param array<string, mixed> $changes Changes to apply
     * @return self New configuration instance
     */
    public function withChanges(array $changes): self
    {
        $currentConfig = $this->toArray();
        $newConfig = array_merge($currentConfig, $changes);
        return self::fromArray($newConfig);
    }

    /**
     * Serialize for JSON encoding.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Validate authentication configuration.
     *
     * @param array<string, mixed> $auth Authentication configuration
     * @throws ValidationError If authentication configuration is invalid
     */
    private function validateAuthConfig(array $auth): void
    {
        if (! isset($auth['type'])) {
            throw ValidationError::emptyField('auth.type');
        }

        $validTypes = ['bearer', 'basic', 'oauth2', 'custom'];
        if (! in_array($auth['type'], $validTypes, true)) {
            throw ValidationError::invalidFieldValue(
                'auth.type',
                'must be one of: ' . implode(', ', $validTypes),
                ['value' => $auth['type'], 'valid' => $validTypes]
            );
        }

        // Type-specific validation
        switch ($auth['type']) {
            case 'bearer':
                if (! isset($auth['token']) || ! is_string($auth['token']) || empty($auth['token'])) {
                    throw ValidationError::invalidFieldValue(
                        'auth.token',
                        'is required for bearer authentication',
                        ['auth' => $auth]
                    );
                }
                break;
            case 'basic':
                if (! isset($auth['username']) || ! is_string($auth['username']) || empty($auth['username'])) {
                    throw ValidationError::invalidFieldValue(
                        'auth.username',
                        'is required for basic authentication',
                        ['auth' => $auth]
                    );
                }
                if (! isset($auth['password']) || ! is_string($auth['password'])) {
                    throw ValidationError::invalidFieldValue(
                        'auth.password',
                        'is required for basic authentication',
                        ['auth' => $auth]
                    );
                }
                break;
            case 'oauth2':
                // OAuth2 validation will be implemented when OAuth2 support is added
                // For now, just ensure basic required fields exist
                $requiredFields = ['client_id', 'client_secret'];
                foreach ($requiredFields as $field) {
                    if (! isset($auth[$field]) || ! is_string($auth[$field]) || empty($auth[$field])) {
                        throw ValidationError::invalidFieldValue(
                            "auth.{$field}",
                            'is required for OAuth2 authentication',
                            ['auth' => $auth]
                        );
                    }
                }
                break;
            case 'custom':
                if (! isset($auth['headers']) || ! is_array($auth['headers'])) {
                    throw ValidationError::invalidFieldValue(
                        'auth.headers',
                        'is required for custom authentication',
                        ['auth' => $auth]
                    );
                }
                break;
        }
    }
}
