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

    public function connect(): void
    {
        if ($this->connected) {
            throw new TransportError('Transport is already connected');
        }

        try {
            $this->logger->info('Starting HTTP transport connection', [
                'base_url' => $this->config->getBaseUrl(),
                'config' => $this->config->toArray(),
            ]);

            // Initialize components
            $this->initializeComponents();

            // TODO: Implement connection logic
            // 1. Send initialize request
            // 2. Handle initialize response
            // 3. Extract session ID
            // 4. Send initialized notification
            // 5. Establish SSE connection

            throw new TransportError('HTTP transport connection not yet implemented');
        } catch (Exception $e) {
            $this->logger->error('Failed to connect HTTP transport', [
                'error' => $e->getMessage(),
                'base_url' => $this->config->getBaseUrl(),
            ]);
            $this->cleanup();
            throw new TransportError('Failed to connect: ' . $e->getMessage());
        }
    }

    public function send(string $message): void
    {
        $this->ensureConnected();

        try {
            $this->logger->debug('Sending message to MCP server via HTTP', [
                'direction' => 'outgoing',
                'message_length' => strlen($message),
                'message' => $message,
                'session_id' => $this->sessionId,
                'timestamp' => microtime(true),
            ]);

            // TODO: Implement message sending logic
            // 1. Validate message format
            // 2. Add session headers
            // 3. Send HTTP POST request
            // 4. Handle response
            // 5. Handle retries if needed

            throw new TransportError('HTTP transport message sending not yet implemented');
        } catch (Exception $e) {
            $this->logger->error('Failed to send message via HTTP', [
                'direction' => 'outgoing',
                'error' => $e->getMessage(),
                'message_preview' => substr($message, 0, 100),
                'session_id' => $this->sessionId,
            ]);
            throw new TransportError('Failed to send message: ' . $e->getMessage());
        }
    }

    public function receive(?int $timeout = null): ?string
    {
        $this->ensureConnected();

        try {
            $this->logger->debug('Waiting for message from MCP server via SSE', [
                'direction' => 'incoming',
                'timeout' => $timeout,
                'session_id' => $this->sessionId,
                'timestamp' => microtime(true),
            ]);

            // TODO: Implement message receiving logic
            // 1. Read from SSE stream
            // 2. Parse SSE event format
            // 3. Extract JSON-RPC message
            // 4. Handle timeout
            // 5. Return message or null

            throw new TransportError('HTTP transport message receiving not yet implemented');
        } catch (Exception $e) {
            $this->logger->error('Failed to receive message via SSE', [
                'direction' => 'incoming',
                'error' => $e->getMessage(),
                'timeout' => $timeout,
                'session_id' => $this->sessionId,
            ]);
            throw new TransportError('Failed to receive message: ' . $e->getMessage());
        }
    }

    public function isConnected(): bool
    {
        return $this->connected && $this->sessionId !== null;
    }

    public function disconnect(): void
    {
        if (! $this->connected) {
            return; // Already disconnected
        }

        try {
            $this->logger->info('Disconnecting HTTP transport', [
                'session_id' => $this->sessionId,
                'base_url' => $this->config->getBaseUrl(),
            ]);

            // TODO: Implement disconnection logic
            // 1. Close SSE connection
            // 2. Clean up resources
            // 3. Reset state

            $this->cleanup();

            $this->logger->info('HTTP transport disconnected successfully', [
                'session_id' => $this->sessionId,
            ]);
        } catch (Exception $e) {
            $this->logger->error('Error while disconnecting HTTP transport', [
                'error' => $e->getMessage(),
                'session_id' => $this->sessionId,
            ]);
            throw new TransportError('Failed to disconnect: ' . $e->getMessage());
        }
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
     * Ensure the transport is connected.
     *
     * @throws TransportError If not connected
     */
    private function ensureConnected(): void
    {
        if (! $this->connected) {
            throw new TransportError('Transport is not connected');
        }

        if ($this->sessionId === null) {
            throw new TransportError('No active session');
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
    private function updateStats(string $key, $value): void
    {
        $this->stats[$key] = $value;
        $this->stats['last_activity'] = microtime(true);
    }
}
