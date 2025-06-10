<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client\Transport\Http;

use Dtyq\PhpMcp\Client\Configuration\HttpConfig;
use Dtyq\PhpMcp\Shared\Exceptions\TransportError;
use Dtyq\PhpMcp\Shared\Kernel\Logger\LoggerProxy;
use Dtyq\PhpMcp\Shared\Message\JsonRpcMessage;
use Dtyq\PhpMcp\Shared\Utilities\JsonUtils;
use Dtyq\PhpMcp\Shared\Utilities\SSE\SSEClient;
use Dtyq\PhpMcp\Shared\Utilities\SSE\SSEEvent;
use Exception;

/**
 * SSE (Server-Sent Events) stream handler for MCP transport.
 *
 * This class handles SSE connections for both new protocol (2025-03-26) and
 * legacy protocol (2024-11-05). It supports event callbacks, connection resumption,
 * and automatic event parsing with proper error handling.
 */
class SseStreamHandler
{
    private HttpConfig $config;

    private LoggerProxy $logger;

    /** @var null|SSEClient SSE client instance */
    private ?SSEClient $sseClient = null;

    private bool $connected = false;

    private bool $isLegacyMode = false;

    /** @var null|callable Event callback function */
    private $eventCallback;

    /** @var int Connection timeout in seconds */
    private int $connectionTimeout = 30;

    /** @var int Read timeout in microseconds */
    private int $readTimeoutUs = 100000; // 100ms

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
     * Set event processing callback.
     *
     * The callback will be invoked for each received SSE event.
     *
     * @param callable $callback Event callback function (JsonRpcMessage, ?string $eventId)
     */
    public function setEventCallback(callable $callback): void
    {
        $this->eventCallback = $callback;
    }

    /**
     * Connect to new protocol SSE endpoint.
     *
     * @param string $baseUrl Server base URL
     * @param null|string $sessionId Session ID for the connection
     */
    public function connectNew(string $baseUrl, ?string $sessionId = null): void
    {
        $this->logger->info('Connecting to new protocol SSE', [
            'base_url' => $baseUrl,
            'has_session_id' => $sessionId !== null,
        ]);

        $this->isLegacyMode = false;
        $headers = [];

        // Add session ID header if available (required for new protocol)
        if ($sessionId) {
            $headers['Mcp-Session-Id'] = $sessionId;
        }

        $this->createSEEClient($baseUrl, $headers);

        $this->connected = true;

        $this->logger->info('New protocol SSE connection established', [
            'session_id' => $sessionId,
        ]);
    }

    /**
     * Connect with resumption capability using Last-Event-ID.
     *
     * @param string $baseUrl Server base URL
     * @param array<string, string> $headers Additional headers including Last-Event-ID
     */
    public function connectWithResumption(string $baseUrl, array $headers): void
    {
        $this->logger->info('Connecting with resumption', [
            'base_url' => $baseUrl,
            'last_event_id' => $headers['Last-Event-ID'] ?? 'none',
        ]);

        $this->isLegacyMode = false;

        $this->createSEEClient($baseUrl, $headers);

        $this->connected = true;

        $this->logger->info('SSE connection with resumption established');
    }

    /**
     * Connect to legacy protocol SSE and retrieve endpoint information.
     *
     * @param string $baseUrl Server base URL
     * @return array<string, string> Endpoint information
     * @throws TransportError If connection fails or endpoint event not received
     */
    public function connectLegacy(string $baseUrl): array
    {
        $this->logger->info('Connecting to legacy protocol SSE', ['base_url' => $baseUrl]);

        $this->isLegacyMode = true;
        $headers = [];

        $this->createSEEClient($baseUrl, $headers);

        $this->connected = true;

        // Wait for endpoint event in legacy mode
        $endpointEvent = $this->waitForEndpointEvent();
        if (! $endpointEvent) {
            throw new TransportError('Failed to receive expected endpoint event in legacy mode');
        }

        $endpointInfo = $this->parseEndpointEvent($endpointEvent);

        $this->logger->info('Legacy protocol SSE connection established', $endpointInfo);

        return $endpointInfo;
    }

    /**
     * Receive a JSON-RPC message from the SSE stream.
     *
     * @return null|JsonRpcMessage Received message or null if no message available
     */
    public function receiveMessage(): ?JsonRpcMessage
    {
        if (! $this->connected || ! $this->sseClient) {
            return null;
        }

        try {
            foreach ($this->sseClient->getEvents() as $event) {
                // Skip endpoint events in legacy mode (they're for setup only)
                if ($this->isLegacyMode && $event->event === 'endpoint') {
                    continue;
                }

                // Parse the JSON-RPC message
                $message = $this->parseJsonRpcMessage($event->data);

                // Invoke event callback if set
                if ($this->eventCallback && $message) {
                    $eventId = $event->id !== '' ? $event->id : null;
                    call_user_func($this->eventCallback, $message, $eventId);
                }

                return $message;
            }
        } catch (Exception $e) {
            $this->logger->error('Error receiving SSE events', [
                'error' => $e->getMessage(),
            ]);
            $this->connected = false;
        }

        return null;
    }

    /**
     * Disconnect from SSE stream.
     */
    public function disconnect(): void
    {
        $this->sseClient = null;
        $this->connected = false;
        $this->isLegacyMode = false;
        $this->eventCallback = null;

        $this->logger->info('SSE connection disconnected');
    }

    /**
     * Check if SSE stream is connected.
     *
     * @return bool True if connected
     */
    public function isConnected(): bool
    {
        return $this->connected;
    }

    /**
     * Check if running in legacy mode.
     *
     * @return bool True if in legacy mode
     */
    public function isLegacyMode(): bool
    {
        return $this->isLegacyMode;
    }

    /**
     * Get connection statistics.
     *
     * @return array<string, mixed> Connection statistics
     */
    public function getStats(): array
    {
        return [
            'connected' => $this->connected,
            'legacy_mode' => $this->isLegacyMode,
            'has_callback' => $this->eventCallback !== null,
            'connection_timeout' => $this->connectionTimeout,
            'read_timeout_us' => $this->readTimeoutUs,
            'has_sse_client' => $this->sseClient !== null,
        ];
    }

    /**
     * Set connection timeout.
     *
     * @param int $seconds Timeout in seconds
     */
    public function setConnectionTimeout(int $seconds): void
    {
        if ($seconds <= 0) {
            throw new TransportError('Connection timeout must be positive');
        }

        $this->connectionTimeout = $seconds;
    }

    /**
     * Set read timeout.
     *
     * @param int $microseconds Timeout in microseconds
     */
    public function setReadTimeout(int $microseconds): void
    {
        if ($microseconds <= 0) {
            throw new TransportError('Read timeout must be positive');
        }

        $this->readTimeoutUs = $microseconds;
    }

    /**
     * Wait for endpoint event in legacy protocol.
     *
     * @return null|SSEEvent Endpoint event or null if timeout
     */
    protected function waitForEndpointEvent(): ?SSEEvent
    {
        if (! $this->sseClient) {
            return null;
        }

        $timeout = $this->config->getSseTimeout();
        $startTime = microtime(true);

        try {
            foreach ($this->sseClient->getEvents() as $event) {
                if ($event->event === 'endpoint') {
                    return $event;
                }

                // Check timeout
                if ((microtime(true) - $startTime) >= $timeout) {
                    break;
                }
            }
        } catch (Exception $e) {
            $this->logger->error('Error while waiting for endpoint event', [
                'error' => $e->getMessage(),
            ]);
        }

        $this->logger->error('Timeout waiting for endpoint event', [
            'elapsed' => microtime(true) - $startTime,
            'timeout' => $timeout,
        ]);

        return null;
    }

    /**
     * Parse endpoint event data.
     *
     * @param SSEEvent $event Endpoint event
     * @return array<string, string> Parsed endpoint information
     * @throws TransportError If event data is invalid
     */
    protected function parseEndpointEvent(SSEEvent $event): array
    {
        $eventData = $event->data;

        // First try to parse as JSON (new format)
        try {
            $data = JsonUtils::decode($eventData, true);
            if ($data && isset($data['uri'])) {
                // JSON format with uri field
                return [
                    'post_endpoint' => $data['uri'],
                ];
            }
        } catch (Exception $e) {
            // Not JSON, continue to legacy format handling
        }

        // If not JSON or no uri field, treat as direct URL string (legacy format)
        if (! empty($eventData)) {
            // Remove leading/trailing whitespace
            $endpoint = trim($eventData);

            // Validate that it looks like a URL path
            if (strpos($endpoint, '/') === 0 || filter_var($endpoint, FILTER_VALIDATE_URL) !== false) {
                return [
                    'post_endpoint' => $endpoint,
                ];
            }
        }

        // If we get here, the data format is invalid
        throw new TransportError('Invalid endpoint event data format. Expected JSON with "uri" field or direct URL string. Got: ' . JsonUtils::encode($eventData));
    }

    /**
     * Parse JSON-RPC message from event data.
     *
     * @param string $data Event data
     * @return null|JsonRpcMessage Parsed message or null if invalid
     */
    protected function parseJsonRpcMessage(string $data): ?JsonRpcMessage
    {
        if (empty($data)) {
            return null;
        }

        try {
            $decoded = JsonUtils::decode($data, true);
        } catch (Exception $e) {
            $this->logger->warning('Failed to parse JSON-RPC message', [
                'error' => $e->getMessage(),
                'data_preview' => substr($data, 0, 100),
            ]);
            return null;
        }

        try {
            return JsonRpcMessage::fromArray($decoded);
        } catch (Exception $e) {
            $this->logger->warning('Failed to create JsonRpcMessage from data', [
                'error' => $e->getMessage(),
                'data' => $decoded,
            ]);
            return null;
        }
    }

    /**
     * Create SSE client with proper configuration.
     *
     * @param string $baseUrl Base URL for SSE connection
     * @param array<string, string> $headers Additional headers
     */
    private function createSEEClient(string $baseUrl, array $headers): void
    {
        // Add config headers
        $configHeaders = $this->config->getHeaders();
        $headers = array_merge($headers, $configHeaders);

        // Add user agent if not already present
        if (! isset($headers['User-Agent'])) {
            $headers['User-Agent'] = $this->config->getUserAgent();
        }

        $this->sseClient = new SSEClient($baseUrl, $this->connectionTimeout, $headers);
    }
}
