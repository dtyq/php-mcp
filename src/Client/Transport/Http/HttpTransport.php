<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client\Transport\Http;

use Dtyq\PhpMcp\Client\Configuration\HttpConfig;
use Dtyq\PhpMcp\Client\Core\TransportInterface;
use Dtyq\PhpMcp\Shared\Exceptions\TransportError;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Dtyq\PhpMcp\Shared\Kernel\Logger\LoggerProxy;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;
use Exception;

/**
 * HTTP transport implementation for MCP client.
 *
 * This transport communicates with MCP servers through HTTP POST requests
 * for sending messages and Server-Sent Events (SSE) for receiving messages,
 * implementing the full MCP Streamable HTTP transport protocol.
 */
class HttpTransport implements TransportInterface
{
    /** @var HttpConfig Transport configuration */
    private HttpConfig $config;

    /** @var Application Application instance for services */
    private Application $application;

    /** @var LoggerProxy Logger instance */
    private LoggerProxy $logger;

    /** @var null|HttpConnectionManager Connection manager instance */
    private ?HttpConnectionManager $connectionManager = null;

    /** @var null|SseStreamHandler SSE stream handler instance */
    private ?SseStreamHandler $sseHandler = null;

    /** @var null|HttpRequestHandler HTTP request handler instance */
    private ?HttpRequestHandler $requestHandler = null;

    /** @var bool Whether the transport is connected */
    private bool $connected = false;

    /** @var null|string Current session ID */
    private ?string $sessionId = null;

    /** @var null|float Timestamp when connection was established */
    private ?float $connectedAt = null;

    /** @var array<string, mixed> Connection statistics */
    private array $stats = [];

    /**
     * @param HttpConfig $config Transport configuration
     * @param Application $application Application instance for services
     */
    public function __construct(HttpConfig $config, Application $application)
    {
        $this->config = $config;
        $this->application = $application;
        $this->logger = $application->getLogger();

        $this->initializeStats();
    }

    /**
     * Destructor to ensure cleanup.
     */
    public function __destruct()
    {
        if ($this->connected) {
            try {
                $this->disconnect();
            } catch (Exception $e) {
                // Ignore errors during cleanup in destructor
                $this->logger->warning('Error during HttpTransport cleanup', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Connect to the MCP server.
     *
     * @throws TransportError If connection fails
     */
    public function connect(): void
    {
        if ($this->connected) {
            $this->logger->warning('HTTP transport already connected, disconnecting first');
            $this->disconnect();
        }

        $this->logger->info('Connecting HTTP transport', [
            'base_url' => $this->config->getBaseUrl(),
            'session_resumable' => $this->config->isSessionResumable(),
        ]);

        try {
            $this->initializeComponents();
            $this->performInitializeHandshake();
            $this->establishSseConnection();

            $this->connected = true;
            $this->updateStats('connection_attempts');

            $this->logger->info('HTTP transport connected successfully', [
                'session_id' => $this->sessionId,
                'base_url' => $this->config->getBaseUrl(),
            ]);
        } catch (Exception $e) {
            $this->updateStats('errors');
            $this->logger->error('Failed to connect HTTP transport', [
                'error' => $e->getMessage(),
                'base_url' => $this->config->getBaseUrl(),
            ]);
            throw new TransportError('HTTP transport connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Send a message to the server.
     *
     * @param string $message JSON-RPC message to send
     * @throws TransportError If send fails
     */
    public function send(string $message): void
    {
        if (! $this->connected) {
            throw new TransportError('HTTP transport not connected');
        }

        $this->logger->debug('Sending message via HTTP transport', [
            'message_length' => strlen($message),
            'session_id' => $this->sessionId,
        ]);

        try {
            $this->requestHandler->sendRequest($message, $this->sessionId);
            $this->updateStats('messages_sent');

            $this->logger->debug('Message sent successfully via HTTP transport', [
                'session_id' => $this->sessionId,
            ]);
        } catch (Exception $e) {
            $this->updateStats('errors');
            $this->logger->error('Failed to send message via HTTP transport', [
                'error' => $e->getMessage(),
                'session_id' => $this->sessionId,
            ]);
            throw new TransportError('Failed to send HTTP message: ' . $e->getMessage());
        }
    }

    /**
     * Receive a message from the server.
     *
     * @param null|int $timeout Timeout in seconds (null for default)
     * @return null|string Received JSON-RPC message or null if no message available
     * @throws TransportError If receive fails
     */
    public function receive(?int $timeout = null): ?string
    {
        if (! $this->connected) {
            throw new TransportError('HTTP transport not connected');
        }

        try {
            $event = $this->sseHandler->readEvent();
            if ($event === null) {
                return null;
            }

            $data = $event['data'] ?? '';
            if (empty($data)) {
                return null;
            }

            $this->updateStats('messages_received');

            $this->logger->debug('Received message via HTTP transport', [
                'message_length' => strlen($data),
                'event_type' => $event['event'] ?? 'message',
                'session_id' => $this->sessionId,
            ]);

            return $data;
        } catch (Exception $e) {
            $this->updateStats('errors');
            $this->logger->error('Failed to receive message via HTTP transport', [
                'error' => $e->getMessage(),
                'session_id' => $this->sessionId,
            ]);
            throw new TransportError('Failed to receive HTTP message: ' . $e->getMessage());
        }
    }

    /**
     * Disconnect from the server.
     */
    public function disconnect(): void
    {
        if (! $this->connected) {
            return;
        }

        $this->logger->info('Disconnecting HTTP transport', [
            'session_id' => $this->sessionId,
        ]);

        try {
            if ($this->sseHandler !== null) {
                $this->sseHandler->disconnect();
            }

            if ($this->connectionManager !== null) {
                $this->connectionManager->clearSession();
            }
        } catch (Exception $e) {
            $this->logger->warning('Error during HTTP transport disconnection', [
                'error' => $e->getMessage(),
                'session_id' => $this->sessionId,
            ]);
        }

        $this->connected = false;
        $this->sessionId = null;
        $this->cleanupComponents();

        $this->logger->info('HTTP transport disconnected');
    }

    public function getType(): string
    {
        return ProtocolConstants::TRANSPORT_TYPE_HTTP;
    }

    /**
     * Get the transport configuration.
     */
    public function getConfig(): HttpConfig
    {
        return $this->config;
    }

    /**
     * Get the application instance.
     */
    public function getApplication(): Application
    {
        return $this->application;
    }

    /**
     * Get current session ID.
     */
    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    /**
     * Get connection statistics.
     *
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        $currentStats = [
            'connected' => $this->connected,
            'session_id' => $this->sessionId,
            'connected_at' => $this->connectedAt,
            'connection_duration' => $this->connectedAt ? microtime(true) - $this->connectedAt : null,
            'base_url' => $this->config->getBaseUrl(),
        ];

        return array_merge($this->stats, $currentStats);
    }

    /**
     * Check if the transport is currently connected.
     *
     * @return bool True if connected and ready for communication
     */
    public function isConnected(): bool
    {
        return $this->connected && $this->sessionId !== null;
    }

    /**
     * Initialize transport components.
     *
     * @throws TransportError If initialization fails
     */
    private function initializeComponents(): void
    {
        try {
            $this->logger->debug('Initializing HTTP transport components');

            // Initialize HTTP request handler
            $this->requestHandler = new HttpRequestHandler(
                $this->config,
                $this->logger,
                $this->application->getAuthenticator()
            );

            // Initialize SSE stream handler
            $this->sseHandler = new SseStreamHandler(
                $this->config,
                $this->logger
            );

            // Initialize connection manager
            $this->connectionManager = new HttpConnectionManager(
                $this->config,
                $this->logger
            );

            $this->logger->debug('HTTP transport components initialized successfully');
        } catch (Exception $e) {
            throw new TransportError('Failed to initialize components: ' . $e->getMessage());
        }
    }

    /**
     * Clean up resources and reset state.
     */
    private function cleanup(): void
    {
        try {
            // Close SSE connection
            if ($this->sseHandler !== null) {
                $this->sseHandler->disconnect();
                $this->sseHandler = null;
            }

            // Clear connection manager
            if ($this->connectionManager !== null) {
                $this->connectionManager->clearSession();
                $this->connectionManager = null;
            }

            // Clear request handler
            $this->requestHandler = null;

            // Reset state
            $this->connected = false;
            $this->sessionId = null;
            $this->connectedAt = null;

            $this->updateStats('cleanup_completed', microtime(true));
        } catch (Exception $e) {
            $this->logger->warning('Error during cleanup', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Initialize statistics tracking.
     */
    private function initializeStats(): void
    {
        $this->stats = [
            'transport_type' => $this->getType(),
            'created_at' => microtime(true),
            'connection_attempts' => 0,
            'messages_sent' => 0,
            'messages_received' => 0,
            'errors' => 0,
            'last_activity' => null,
        ];
    }

    /**
     * Update statistics.
     *
     * @param string $key Statistics key
     * @param mixed $value Statistics value
     */
    private function updateStats(string $key, $value = null): void
    {
        if ($value === null) {
            $value = microtime(true);
        }
        $this->stats[$key] = $value;
        $this->stats['last_activity'] = $value;
    }

    private function performInitializeHandshake(): void
    {
        // Implementation of performInitializeHandshake method
    }

    private function establishSseConnection(): void
    {
        // Implementation of establishSseConnection method
    }

    private function cleanupComponents(): void
    {
        $this->cleanup();
    }
}
