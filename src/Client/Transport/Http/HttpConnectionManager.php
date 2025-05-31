<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client\Transport\Http;

use Dtyq\PhpMcp\Client\Configuration\HttpConfig;
use Dtyq\PhpMcp\Shared\Kernel\Logger\LoggerProxy;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;

/**
 * HTTP connection manager for transport.
 *
 * This class manages HTTP connections, session state, and provides
 * centralized connection tracking for MCP HTTP transport.
 */
class HttpConnectionManager
{
    /** @var HttpConfig Transport configuration */
    private HttpConfig $config;

    /** @var LoggerProxy Logger instance */
    private LoggerProxy $logger;

    /** @var null|string Current session ID */
    private ?string $sessionId = null;

    /** @var array<string, string> Default headers for all requests */
    private array $defaultHeaders = [];

    /** @var array<string, mixed> Connection statistics */
    private array $stats = [];

    /** @var array<string, float> Connection pool for future use */
    private array $connectionPool = [];

    /** @var array<string, mixed> Performance metrics */
    private array $performanceMetrics = [];

    /** @var int Maximum connections in pool */
    private int $maxPoolSize = 5;

    /** @var bool Whether the connection is initialized */
    private bool $initialized = false;

    /** @var null|float Timestamp when session was created */
    private ?float $sessionCreatedAt = null;

    /** @var null|float Timestamp of last activity */
    private ?float $lastActivityAt = null;

    /**
     * @param HttpConfig $config Transport configuration
     * @param LoggerProxy $logger Logger instance
     */
    public function __construct(HttpConfig $config, LoggerProxy $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->initializeStats();
        $this->buildDefaultHeaders();
    }

    /**
     * Initialize the connection manager.
     *
     * @param null|string $sessionId Optional session ID to restore
     */
    public function initialize(?string $sessionId = null): void
    {
        $startTime = microtime(true);

        $this->logger->info('Initializing HTTP connection manager', [
            'base_url' => $this->config->getBaseUrl(),
            'existing_session_id' => $sessionId,
        ]);

        if ($sessionId !== null) {
            $this->sessionId = $sessionId;
            $this->logger->info('Restored existing session', [
                'session_id' => $sessionId,
            ]);
        }

        $this->initialized = true;
        $this->sessionCreatedAt = microtime(true);
        $this->updateActivity();
        $this->updateStats('initializations');

        // Track performance
        $duration = microtime(true) - $startTime;
        $this->trackPerformance('initialize', $duration);

        $this->logger->debug('HTTP connection manager initialized', [
            'session_id' => $this->sessionId,
            'initialized' => $this->initialized,
            'init_duration' => $duration,
        ]);
    }

    /**
     * Get the current session ID.
     *
     * @return null|string Session ID or null if not set
     */
    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    /**
     * Set the session ID.
     *
     * @param null|string $sessionId Session ID to set
     */
    public function setSessionId(?string $sessionId): void
    {
        $startTime = microtime(true);
        $oldSessionId = $this->sessionId;
        $this->sessionId = $sessionId;

        if ($sessionId !== null) {
            if ($oldSessionId === null) {
                $this->sessionCreatedAt = microtime(true);
                $this->updateStats('sessions_created');
            }
            $this->updateActivity();
            $this->updateSessionHeaders();
        }

        // Track performance
        $duration = microtime(true) - $startTime;
        $this->trackPerformance('set_session_id', $duration);

        $this->logger->debug('Session ID updated', [
            'old_session_id' => $oldSessionId,
            'new_session_id' => $sessionId,
            'operation_duration' => $duration,
        ]);
    }

    /**
     * Get default headers for requests.
     *
     * @return array<string, string> Default headers
     */
    public function getDefaultHeaders(): array
    {
        $this->updateActivity();
        return $this->defaultHeaders;
    }

    /**
     * Update session-specific headers.
     */
    public function updateSessionHeaders(): void
    {
        if ($this->sessionId !== null) {
            $this->defaultHeaders[ProtocolConstants::HTTP_HEADER_SESSION_ID] = $this->sessionId;
        } else {
            unset($this->defaultHeaders[ProtocolConstants::HTTP_HEADER_SESSION_ID]);
        }

        $this->logger->debug('Updated session headers', [
            'session_id' => $this->sessionId,
            'has_session_header' => isset($this->defaultHeaders[ProtocolConstants::HTTP_HEADER_SESSION_ID]),
        ]);
    }

    /**
     * Check if the session is valid and not expired.
     *
     * @param null|float $maxAge Maximum session age in seconds (null for no limit)
     * @return bool True if session is valid
     */
    public function isSessionValid(?float $maxAge = null): bool
    {
        if ($this->sessionId === null || $this->sessionCreatedAt === null) {
            return false;
        }

        if ($maxAge !== null) {
            $age = microtime(true) - $this->sessionCreatedAt;
            if ($age > $maxAge) {
                $this->logger->warning('Session expired due to age', [
                    'session_id' => $this->sessionId,
                    'age' => $age,
                    'max_age' => $maxAge,
                ]);
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the connection manager is initialized.
     *
     * @return bool True if initialized
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * Get session metadata.
     *
     * @return array<string, mixed> Session metadata
     */
    public function getSessionMetadata(): array
    {
        return [
            'session_id' => $this->sessionId,
            'created_at' => $this->sessionCreatedAt,
            'last_activity_at' => $this->lastActivityAt,
            'age' => $this->sessionCreatedAt !== null ? microtime(true) - $this->sessionCreatedAt : null,
            'idle_time' => $this->lastActivityAt !== null ? microtime(true) - $this->lastActivityAt : null,
            'base_url' => $this->config->getBaseUrl(),
            'session_resumable' => $this->config->isSessionResumable(),
        ];
    }

    /**
     * Clear session data.
     */
    public function clearSession(): void
    {
        $oldSessionId = $this->sessionId;

        $this->sessionId = null;
        $this->sessionCreatedAt = null;
        $this->lastActivityAt = null;
        $this->updateSessionHeaders();
        $this->updateStats('sessions_cleared');

        $this->logger->info('Session cleared', [
            'old_session_id' => $oldSessionId,
        ]);
    }

    /**
     * Reset the connection manager to initial state.
     */
    public function reset(): void
    {
        $this->logger->debug('Resetting HTTP connection manager');

        $this->clearSession();
        $this->initialized = false;
        $this->initializeStats();
        $this->buildDefaultHeaders();

        $this->logger->info('HTTP connection manager reset');
    }

    /**
     * Get connection statistics.
     *
     * @return array<string, mixed> Connection statistics
     */
    public function getStats(): array
    {
        return array_merge($this->stats, [
            'initialized' => $this->initialized,
            'has_session' => $this->sessionId !== null,
            'session_metadata' => $this->getSessionMetadata(),
        ]);
    }

    /**
     * Get performance metrics.
     *
     * @return array<string, mixed>
     */
    public function getPerformanceMetrics(): array
    {
        return array_merge($this->performanceMetrics, [
            'connection_pool_size' => count($this->connectionPool),
            'max_pool_size' => $this->maxPoolSize,
            'session_valid' => $this->isSessionValid(),
            'session_age' => $this->getSessionAge(),
        ]);
    }

    /**
     * Build default headers for all requests.
     */
    private function buildDefaultHeaders(): void
    {
        $this->defaultHeaders = [
            ProtocolConstants::HTTP_HEADER_CONTENT_TYPE => ProtocolConstants::HTTP_CONTENT_TYPE_JSON,
            ProtocolConstants::HTTP_HEADER_ACCEPT => ProtocolConstants::HTTP_ACCEPT_SSE_JSON,
            ProtocolConstants::HTTP_HEADER_USER_AGENT => $this->config->getUserAgent(),
        ];

        // Add custom headers from config
        foreach ($this->config->getHeaders() as $name => $value) {
            $this->defaultHeaders[$name] = $value;
        }

        // Update session headers if we have a session
        $this->updateSessionHeaders();

        $this->logger->debug('Built default headers', [
            'header_count' => count($this->defaultHeaders),
            'headers' => array_keys($this->defaultHeaders),
        ]);
    }

    /**
     * Update last activity timestamp.
     */
    private function updateActivity(): void
    {
        $this->lastActivityAt = microtime(true);
    }

    /**
     * Initialize statistics tracking.
     */
    private function initializeStats(): void
    {
        $this->stats = [
            'initializations' => 0,
            'sessions_created' => 0,
            'sessions_cleared' => 0,
            'activity_updates' => 0,
            'created_at' => microtime(true),
        ];
    }

    /**
     * Update statistics.
     *
     * @param string $key Statistics key
     * @param mixed $value Optional value to set
     */
    private function updateStats(string $key, $value = null): void
    {
        if ($value !== null) {
            $this->stats[$key] = $value;
        } else {
            // Increment counter
            if (isset($this->stats[$key])) {
                ++$this->stats[$key];
            }
        }
    }

    /**
     * Get session age in seconds.
     *
     * @return float Session age in seconds
     */
    private function getSessionAge(): float
    {
        if ($this->sessionCreatedAt === null) {
            return 0.0;
        }
        return microtime(true) - $this->sessionCreatedAt;
    }

    /**
     * Track connection performance.
     *
     * @param string $operation Operation name
     * @param float $duration Operation duration
     */
    private function trackPerformance(string $operation, float $duration): void
    {
        if (! isset($this->performanceMetrics[$operation])) {
            $this->performanceMetrics[$operation] = [
                'count' => 0,
                'total_duration' => 0.0,
                'avg_duration' => 0.0,
                'min_duration' => null,
                'max_duration' => null,
            ];
        }

        $metric = &$this->performanceMetrics[$operation];
        ++$metric['count'];
        $metric['total_duration'] += $duration;
        $metric['avg_duration'] = $metric['total_duration'] / $metric['count'];

        if ($metric['min_duration'] === null || $duration < $metric['min_duration']) {
            $metric['min_duration'] = $duration;
        }
        if ($metric['max_duration'] === null || $duration > $metric['max_duration']) {
            $metric['max_duration'] = $duration;
        }
    }
}
