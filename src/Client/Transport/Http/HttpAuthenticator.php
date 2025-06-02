<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client\Transport\Http;

use Dtyq\PhpMcp\Client\Configuration\HttpConfig;
use Dtyq\PhpMcp\Shared\Exceptions\TransportError;
use Dtyq\PhpMcp\Shared\Kernel\Logger\LoggerProxy;

/**
 * HTTP authenticator for various authentication methods.
 *
 * This class handles different authentication schemes including Bearer tokens,
 * Basic authentication, OAuth2 (with placeholder implementation), and custom
 * authentication headers. It also provides caching mechanisms to improve
 * performance for repeated authentication operations.
 */
class HttpAuthenticator
{
    private HttpConfig $config;

    private LoggerProxy $logger;

    /** @var null|array<string, mixed> Cached authentication data */
    private ?array $authCache = null;

    /** @var int Cache expiration timestamp */
    private int $cacheExpiresAt = 0;

    /** @var int Default cache TTL in seconds */
    private int $cacheTtl = 3600; // 1 hour

    /**
     * @param HttpConfig $config HTTP configuration
     * @param LoggerProxy $logger Logger instance
     */
    public function __construct(HttpConfig $config, LoggerProxy $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Add authentication headers to the request.
     *
     * This method processes the authentication configuration and adds the
     * appropriate headers to the HTTP request based on the auth type.
     *
     * @param array<string, string> $headers Existing headers
     * @return array<string, string> Headers with authentication added
     * @throws TransportError If authentication fails
     */
    public function addAuthHeaders(array $headers): array
    {
        $authConfig = $this->config->getAuth();
        if (! $authConfig) {
            return $headers;
        }

        $this->logger->debug('Adding authentication headers', ['type' => $authConfig['type'] ?? 'unknown']);

        switch ($authConfig['type'] ?? '') {
            case 'bearer':
                return $this->addBearerAuth($headers, $authConfig);
            case 'basic':
                return $this->addBasicAuth($headers, $authConfig);
            case 'oauth2':
                return $this->addOAuth2Auth($headers, $authConfig);
            case 'custom':
                return $this->addCustomAuth($headers, $authConfig);
            default:
                $this->logger->warning('Unknown authentication type', ['type' => $authConfig['type'] ?? 'null']);
                return $headers;
        }
    }

    /**
     * Get current authentication status.
     *
     * @return array<string, mixed> Authentication status information
     */
    public function getAuthStatus(): array
    {
        $authConfig = $this->config->getAuth();

        return [
            'has_auth' => $authConfig !== null,
            'auth_type' => $authConfig['type'] ?? null,
            'has_cache' => $this->authCache !== null,
            'cache_valid' => $this->isAuthCacheValid(),
            'cache_expires_at' => $this->cacheExpiresAt,
            'cache_ttl' => $this->cacheTtl,
        ];
    }

    /**
     * Set cache TTL.
     *
     * @param int $ttl Cache TTL in seconds
     */
    public function setCacheTtl(int $ttl): void
    {
        if ($ttl < 0) {
            throw new TransportError('Cache TTL cannot be negative');
        }

        $this->cacheTtl = $ttl;
        $this->logger->debug('Updated cache TTL', ['ttl' => $ttl]);
    }

    /**
     * Get cache TTL.
     *
     * @return int Cache TTL in seconds
     */
    public function getCacheTtl(): int
    {
        return $this->cacheTtl;
    }

    /**
     * Force refresh authentication (clear cache).
     */
    public function refreshAuth(): void
    {
        $this->clearAuthCache();
        $this->logger->info('Authentication cache forcefully refreshed');
    }

    /**
     * Add Bearer token authentication.
     *
     * @param array<string, string> $headers Existing headers
     * @param array<string, mixed> $authConfig Authentication configuration
     * @return array<string, string> Updated headers
     */
    private function addBearerAuth(array $headers, array $authConfig): array
    {
        if (! isset($authConfig['token']) || ! is_string($authConfig['token'])) {
            throw new TransportError('Bearer token is required but not provided');
        }

        $headers['Authorization'] = 'Bearer ' . $authConfig['token'];
        $this->logger->debug('Added Bearer authentication header');

        return $headers;
    }

    /**
     * Add Basic authentication.
     *
     * @param array<string, string> $headers Existing headers
     * @param array<string, mixed> $authConfig Authentication configuration
     * @return array<string, string> Updated headers
     */
    private function addBasicAuth(array $headers, array $authConfig): array
    {
        if (! isset($authConfig['username'], $authConfig['password'])) {
            throw new TransportError('Username and password are required for Basic authentication');
        }

        if (! is_string($authConfig['username']) || ! is_string($authConfig['password'])) {
            throw new TransportError('Username and password must be strings');
        }

        $credentials = base64_encode($authConfig['username'] . ':' . $authConfig['password']);
        $headers['Authorization'] = 'Basic ' . $credentials;

        $this->logger->debug('Added Basic authentication header', ['username' => $authConfig['username']]);

        return $headers;
    }

    /**
     * Add OAuth2 authentication.
     *
     * Currently implements a placeholder for OAuth2. Full OAuth2 implementation
     * including authorization code flow, PKCE, and token refresh will be added
     * in future versions.
     *
     * @param array<string, string> $headers Existing headers
     * @param array<string, mixed> $authConfig Authentication configuration
     * @return array<string, string> Updated headers
     * @throws TransportError If OAuth2 token acquisition fails
     */
    private function addOAuth2Auth(array $headers, array $authConfig): array
    {
        // Check cache first
        if ($this->isAuthCacheValid()) {
            $cachedToken = $this->authCache['access_token'] ?? null;
            if ($cachedToken) {
                $headers['Authorization'] = 'Bearer ' . $cachedToken;
                $this->logger->debug('Using cached OAuth2 token');
                return $headers;
            }
        }

        // Attempt to get OAuth2 token
        $token = $this->getOAuth2Token($authConfig);
        if ($token) {
            $headers['Authorization'] = 'Bearer ' . $token;
            $this->logger->debug('Added OAuth2 authentication header');
            return $headers;
        }

        throw new TransportError('Failed to obtain OAuth2 access token');
    }

    /**
     * Add custom authentication headers.
     *
     * @param array<string, string> $headers Existing headers
     * @param array<string, mixed> $authConfig Authentication configuration
     * @return array<string, string> Updated headers
     */
    private function addCustomAuth(array $headers, array $authConfig): array
    {
        if (! isset($authConfig['headers']) || ! is_array($authConfig['headers'])) {
            throw new TransportError('Custom headers are required for custom authentication');
        }

        foreach ($authConfig['headers'] as $key => $value) {
            if (! is_string($key) || ! is_string($value)) {
                throw new TransportError('Custom headers must be string key-value pairs');
            }
            $headers[$key] = $value;
        }

        $this->logger->debug('Added custom authentication headers', ['count' => count($authConfig['headers'])]);

        return $headers;
    }

    /**
     * Get OAuth2 access token.
     *
     * This is a placeholder implementation. A full OAuth2 implementation would
     * include:
     * - Authorization code flow
     * - PKCE (Proof Key for Code Exchange) support
     * - Token refresh mechanisms
     * - Secure token storage
     * - Callback URL handling
     *
     * @param array<string, mixed> $config OAuth2 configuration
     * @return null|string Access token or null if acquisition fails
     */
    private function getOAuth2Token(array $config): ?string
    {
        // TODO: Implement full OAuth2 support
        // For now, this is a placeholder that logs a warning

        $this->logger->warning(
            'OAuth2 authentication is not fully implemented yet. Please use bearer token authentication for now.',
            [
                'client_id' => $config['client_id'] ?? 'not_provided',
                'has_client_secret' => isset($config['client_secret']),
                'authorization_url' => $config['authorization_url'] ?? 'not_provided',
                'token_url' => $config['token_url'] ?? 'not_provided',
            ]
        );

        // If a manual access_token is provided in config, use it
        if (isset($config['access_token']) && is_string($config['access_token'])) {
            $this->cacheAuthToken($config['access_token'], $config['expires_in'] ?? 3600);
            return $config['access_token'];
        }

        return null;
    }

    /**
     * Cache authentication token.
     *
     * @param string $token Access token
     * @param int $expiresIn Token lifetime in seconds
     */
    private function cacheAuthToken(string $token, int $expiresIn): void
    {
        $this->authCache = [
            'access_token' => $token,
            'expires_in' => $expiresIn,
            'cached_at' => time(),
        ];

        $this->cacheExpiresAt = time() + $expiresIn;

        $this->logger->debug('Cached authentication token', ['expires_in' => $expiresIn]);
    }

    /**
     * Check if cached authentication is still valid.
     *
     * @return bool True if cache is valid
     */
    private function isAuthCacheValid(): bool
    {
        if (! $this->authCache) {
            return false;
        }

        if (time() >= $this->cacheExpiresAt) {
            $this->clearAuthCache();
            return false;
        }

        return true;
    }

    /**
     * Clear authentication cache.
     */
    private function clearAuthCache(): void
    {
        $this->authCache = null;
        $this->cacheExpiresAt = 0;
        $this->logger->debug('Cleared authentication cache');
    }
}
