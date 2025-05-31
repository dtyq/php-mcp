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
 * base URL, timeouts, retry settings, SSL verification, and session management.
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
        'retry_delay' => 1.0,                  // Base retry delay in seconds
        'session_resumable' => true,           // Whether sessions can be resumed
        'validate_ssl' => true,                // SSL certificate validation
        'user_agent' => 'php-mcp-client/1.0',  // HTTP User-Agent string
        'headers' => [],                       // Custom HTTP headers
        'auth' => null,                        // Authentication configuration
        'force_https' => false,                // Force HTTPS for security
        'min_tls_version' => '1.2',           // Minimum TLS version
        'allowed_cipher_suites' => [],        // Allowed cipher suites (empty = default)
        'verify_hostname' => true,            // Verify SSL hostname
        'max_redirects' => 3,                 // Maximum HTTP redirects
        'follow_redirects' => true,           // Whether to follow redirects
    ];

    /** @var string Base URL for the MCP server (required) */
    private string $baseUrl;

    /** @var float Timeout for HTTP requests in seconds */
    private float $timeout;

    /** @var float Timeout for SSE stream operations in seconds */
    private float $sseTimeout;

    /** @var int Maximum number of retry attempts */
    private int $maxRetries;

    /** @var float Delay between retries in seconds */
    private float $retryDelay;

    /** @var bool Whether sessions can be resumed after disconnection */
    private bool $sessionResumable;

    /** @var bool Whether to validate SSL certificates */
    private bool $validateSsl;

    /** @var string User agent string for HTTP requests */
    private string $userAgent;

    /** @var array<string, string> Additional HTTP headers */
    private array $headers;

    /** @var null|array<string, mixed> Authentication configuration */
    private ?array $auth;

    /** @var array<string, mixed> Security configuration */
    private array $securityConfig = [];

    /**
     * @param string $baseUrl Base URL for the MCP server
     * @param float $timeout Timeout for HTTP requests in seconds
     * @param float $sseTimeout Timeout for SSE stream operations in seconds
     * @param int $maxRetries Maximum number of retry attempts
     * @param float $retryDelay Delay between retries in seconds
     * @param bool $sessionResumable Whether sessions can be resumed
     * @param bool $validateSsl Whether to validate SSL certificates
     * @param string $userAgent User agent string for HTTP requests
     * @param array<string, string> $headers Additional HTTP headers
     * @param null|array<string, mixed> $auth Authentication configuration
     */
    public function __construct(
        string $baseUrl,
        float $timeout = self::DEFAULTS['timeout'],
        float $sseTimeout = self::DEFAULTS['sse_timeout'],
        int $maxRetries = self::DEFAULTS['max_retries'],
        float $retryDelay = self::DEFAULTS['retry_delay'],
        bool $sessionResumable = self::DEFAULTS['session_resumable'],
        bool $validateSsl = self::DEFAULTS['validate_ssl'],
        string $userAgent = self::DEFAULTS['user_agent'],
        array $headers = self::DEFAULTS['headers'],
        ?array $auth = self::DEFAULTS['auth']
    ) {
        $this->setBaseUrl($baseUrl);
        $this->setTimeout($timeout);
        $this->setSseTimeout($sseTimeout);
        $this->setMaxRetries($maxRetries);
        $this->setRetryDelay($retryDelay);
        $this->setSessionResumable($sessionResumable);
        $this->setValidateSsl($validateSsl);
        $this->setUserAgent($userAgent);
        $this->setHeaders($headers);
        $this->setAuth($auth);

        // Initialize security config with defaults
        $this->securityConfig = [
            'force_https' => self::DEFAULTS['force_https'],
            'min_tls_version' => self::DEFAULTS['min_tls_version'],
            'allowed_cipher_suites' => self::DEFAULTS['allowed_cipher_suites'],
            'verify_hostname' => self::DEFAULTS['verify_hostname'],
            'max_redirects' => self::DEFAULTS['max_redirects'],
            'follow_redirects' => self::DEFAULTS['follow_redirects'],
        ];
    }

    /**
     * Create configuration from array.
     *
     * @param array<string, mixed> $config Configuration array
     * @throws ValidationError If configuration is invalid
     */
    public static function fromArray(array $config): self
    {
        // base_url is required
        if (! isset($config['base_url'])) {
            throw ValidationError::emptyField('base_url');
        }

        // Merge with defaults to ensure all required keys are present
        $config = array_merge(self::DEFAULTS, $config);

        return new self(
            $config['base_url'],
            $config['timeout'],
            $config['sse_timeout'],
            $config['max_retries'],
            $config['retry_delay'],
            $config['session_resumable'],
            $config['validate_ssl'],
            $config['user_agent'],
            $config['headers'],
            $config['auth']
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
            'session_resumable' => $this->sessionResumable,
            'validate_ssl' => $this->validateSsl,
            'user_agent' => $this->userAgent,
            'headers' => $this->headers,
            'auth' => $this->auth,
        ];
    }

    // Getters
    public function getBaseUrl(): string
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

    public function isSessionResumable(): bool
    {
        return $this->sessionResumable;
    }

    public function shouldValidateSsl(): bool
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

    // Setters with validation
    public function setBaseUrl(string $baseUrl): void
    {
        if (empty($baseUrl)) {
            throw ValidationError::emptyField('base_url');
        }

        if (! filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            throw ValidationError::invalidFieldValue(
                'base_url',
                'must be a valid URL',
                ['value' => $baseUrl]
            );
        }

        $this->baseUrl = rtrim($baseUrl, '/');
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
                'must be greater than or equal to 0',
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
                'must be greater than or equal to 0',
                ['value' => $retryDelay]
            );
        }
        $this->retryDelay = $retryDelay;
    }

    public function setSessionResumable(bool $sessionResumable): void
    {
        $this->sessionResumable = $sessionResumable;
    }

    public function setValidateSsl(bool $validateSsl): void
    {
        $this->validateSsl = $validateSsl;
    }

    public function setUserAgent(string $userAgent): void
    {
        if (empty($userAgent)) {
            throw ValidationError::emptyField('user_agent');
        }
        $this->userAgent = $userAgent;
    }

    /**
     * @param array<string, string> $headers
     */
    public function setHeaders(array $headers): void
    {
        // Validate headers are string key-value pairs
        foreach ($headers as $name => $value) {
            if (! is_string($name) || ! is_string($value)) {
                throw ValidationError::invalidFieldValue(
                    'headers',
                    'must be an array of string key-value pairs',
                    ['invalid_header' => [$name => $value]]
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
        $this->auth = $auth;
    }

    /**
     * Validate the entire configuration.
     *
     * @throws ValidationError If configuration is invalid
     */
    public function validate(): void
    {
        // Base URL validation (already done in setter, but double-check)
        if (empty($this->baseUrl)) {
            throw ValidationError::emptyField('base_url');
        }

        // Check URL scheme for security
        $scheme = parse_url($this->baseUrl, PHP_URL_SCHEME);
        if (! in_array($scheme, ['http', 'https'], true)) {
            throw ValidationError::invalidFieldValue(
                'base_url',
                'must use http or https scheme',
                ['scheme' => $scheme, 'url' => $this->baseUrl]
            );
        }

        // Warn about HTTP without SSL validation disabled
        if ($scheme === 'http' && $this->validateSsl) {
            // This is just a warning, not an error
            // SSL validation doesn't apply to HTTP
        }

        // Validate timeout relationships
        if ($this->sseTimeout < $this->timeout) {
            throw ValidationError::invalidFieldValue(
                'sse_timeout',
                'should not be less than timeout',
                ['sse_timeout' => $this->sseTimeout, 'timeout' => $this->timeout]
            );
        }
    }

    /**
     * Create a new instance with modified values.
     *
     * @param array<string, mixed> $changes Changes to apply
     * @return self New instance with changes applied
     */
    public function withChanges(array $changes): self
    {
        $current = $this->toArray();
        $updated = array_merge($current, $changes);

        return self::fromArray($updated);
    }

    /**
     * Get the endpoint URL for the MCP server.
     *
     * @param string $path Optional path to append
     * @return string Complete endpoint URL
     */
    public function getEndpointUrl(string $path = ''): string
    {
        $url = $this->baseUrl;
        if (! empty($path)) {
            $url .= '/' . ltrim($path, '/');
        }
        return $url;
    }

    /**
     * Check if authentication is configured.
     *
     * @return bool True if auth is configured
     */
    public function hasAuth(): bool
    {
        return $this->auth !== null && ! empty($this->auth);
    }

    /**
     * Get authentication type if configured.
     *
     * @return null|string Authentication type or null if not configured
     */
    public function getAuthType(): ?string
    {
        if (! $this->hasAuth()) {
            return null;
        }

        return $this->auth['type'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Check if HTTPS is enforced.
     *
     * @return bool True if HTTPS is enforced
     */
    public function isHttpsForced(): bool
    {
        return $this->securityConfig['force_https'] ?? self::DEFAULTS['force_https'];
    }

    /**
     * Set HTTPS enforcement.
     *
     * @param bool $force Whether to force HTTPS
     */
    public function setForceHttps(bool $force): self
    {
        $this->securityConfig['force_https'] = $force;
        return $this;
    }

    /**
     * Get minimum TLS version.
     *
     * @return string Minimum TLS version
     */
    public function getMinTlsVersion(): string
    {
        return $this->securityConfig['min_tls_version'] ?? self::DEFAULTS['min_tls_version'];
    }

    /**
     * Set minimum TLS version.
     *
     * @param string $version TLS version (e.g., '1.2', '1.3')
     * @throws ValidationError If version is invalid
     */
    public function setMinTlsVersion(string $version): self
    {
        $validVersions = ['1.0', '1.1', '1.2', '1.3'];
        if (! in_array($version, $validVersions, true)) {
            throw ValidationError::invalidFieldValue(
                'min_tls_version',
                $version,
                'Must be one of: ' . implode(', ', $validVersions)
            );
        }
        $this->securityConfig['min_tls_version'] = $version;
        return $this;
    }

    /**
     * Get allowed cipher suites.
     *
     * @return array<string> Allowed cipher suites
     */
    public function getAllowedCipherSuites(): array
    {
        return $this->securityConfig['allowed_cipher_suites'] ?? self::DEFAULTS['allowed_cipher_suites'];
    }

    /**
     * Set allowed cipher suites.
     *
     * @param array<string> $cipherSuites Allowed cipher suites
     */
    public function setAllowedCipherSuites(array $cipherSuites): self
    {
        $this->securityConfig['allowed_cipher_suites'] = $cipherSuites;
        return $this;
    }

    /**
     * Check if hostname verification is enabled.
     *
     * @return bool True if hostname verification is enabled
     */
    public function shouldVerifyHostname(): bool
    {
        return $this->securityConfig['verify_hostname'] ?? self::DEFAULTS['verify_hostname'];
    }

    /**
     * Set hostname verification.
     *
     * @param bool $verify Whether to verify hostname
     */
    public function setVerifyHostname(bool $verify): self
    {
        $this->securityConfig['verify_hostname'] = $verify;
        return $this;
    }

    /**
     * Get maximum redirects allowed.
     *
     * @return int Maximum redirects
     */
    public function getMaxRedirects(): int
    {
        return (int) ($this->securityConfig['max_redirects'] ?? self::DEFAULTS['max_redirects']);
    }

    /**
     * Set maximum redirects.
     *
     * @param int $maxRedirects Maximum redirects (0-10)
     * @throws ValidationError If value is invalid
     */
    public function setMaxRedirects(int $maxRedirects): self
    {
        if ($maxRedirects < 0 || $maxRedirects > 10) {
            throw ValidationError::invalidFieldValue(
                'max_redirects',
                (string) $maxRedirects,
                'Must be between 0 and 10'
            );
        }
        $this->securityConfig['max_redirects'] = $maxRedirects;
        return $this;
    }

    /**
     * Check if redirects should be followed.
     *
     * @return bool True if redirects should be followed
     */
    public function shouldFollowRedirects(): bool
    {
        return $this->securityConfig['follow_redirects'] ?? self::DEFAULTS['follow_redirects'];
    }

    /**
     * Set redirect following.
     *
     * @param bool $follow Whether to follow redirects
     */
    public function setFollowRedirects(bool $follow): self
    {
        $this->securityConfig['follow_redirects'] = $follow;
        return $this;
    }
}
