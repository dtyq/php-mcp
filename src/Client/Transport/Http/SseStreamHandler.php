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

    /** @var null|resource SSE stream resource */
    private $stream;

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
        $this->logger->debug('Event callback set');
    }

    /**
     * Connect to new protocol SSE endpoint.
     *
     * @param string $baseUrl Server base URL
     * @param null|string $sessionId Session ID for the connection
     * @throws TransportError If connection fails
     */
    public function connectNew(string $baseUrl, ?string $sessionId = null): void
    {
        $this->logger->info('Connecting to new protocol SSE', [
            'base_url' => $baseUrl,
            'has_session_id' => $sessionId !== null,
        ]);

        $this->isLegacyMode = false;
        $headers = [
            'Accept' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ];

        // Add session ID header if available (required for new protocol)
        if ($sessionId) {
            $headers['Mcp-Session-Id'] = $sessionId;
        }

        $this->stream = $this->createSseConnection($baseUrl, $headers);
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
     * @throws TransportError If connection fails
     */
    public function connectWithResumption(string $baseUrl, array $headers): void
    {
        $this->logger->info('Connecting with resumption', [
            'base_url' => $baseUrl,
            'last_event_id' => $headers['Last-Event-ID'] ?? 'none',
        ]);

        $this->isLegacyMode = false;

        // Ensure required headers for resumption
        $headers['Accept'] = 'text/event-stream';
        $headers['Cache-Control'] = 'no-cache';
        $headers['Connection'] = 'keep-alive';

        $this->stream = $this->createSseConnection($baseUrl, $headers);
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
        $headers = [
            'Accept' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ];

        $this->stream = $this->createSseConnection($baseUrl, $headers);
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
        if (! $this->connected) {
            return null;
        }

        $event = $this->readSseEvent();
        if (! $event) {
            return null;
        }

        // Skip endpoint events in legacy mode (they're for setup only)
        if ($this->isLegacyMode && $event['event'] === 'endpoint') {
            return null;
        }

        // Parse the JSON-RPC message
        $message = $this->parseJsonRpcMessage($event['data']);

        // Invoke event callback if set
        if ($this->eventCallback && $message) {
            $eventId = $event['id'] ?? null;
            call_user_func($this->eventCallback, $message, $eventId);
        }

        return $message;
    }

    /**
     * Disconnect from SSE stream.
     */
    public function disconnect(): void
    {
        if ($this->stream) {
            fclose($this->stream);
            $this->stream = null;
        }

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
            'stream_valid' => is_resource($this->stream),
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
        $this->logger->debug('Connection timeout updated', ['timeout' => $seconds]);
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
        $this->logger->debug('Read timeout updated', ['timeout_us' => $microseconds]);
    }

    /**
     * Wait for endpoint event in legacy protocol.
     *
     * @return null|array<string, mixed> Endpoint event or null if timeout
     */
    protected function waitForEndpointEvent(): ?array
    {
        $timeout = $this->config->getSseTimeout();
        $startTime = microtime(true);

        $this->logger->debug('Waiting for endpoint event', ['timeout' => $timeout]);

        while ($this->connected && (microtime(true) - $startTime) < $timeout) {
            $event = $this->readSseEvent();

            if ($event && $event['event'] === 'endpoint') {
                $this->logger->debug('Endpoint event received', $event);
                return $event;
            }

            if ($event) {
                $this->logger->debug('Non-endpoint event received while waiting', [
                    'event_type' => $event['event'],
                ]);
            }

            // Small delay to prevent busy waiting
            usleep(10000); // 10ms
        }

        $this->logger->error('Timeout waiting for endpoint event', [
            'elapsed' => microtime(true) - $startTime,
            'timeout' => $timeout,
        ]);

        return null;
    }

    /**
     * Read a single SSE event from the stream.
     *
     * @return null|array<string, mixed> Event data or null if no event available
     */
    protected function readSseEvent(): ?array
    {
        if (! $this->stream || ! $this->connected) {
            return null;
        }

        $event = [
            'event' => 'message',
            'data' => '',
            'id' => null,
            'retry' => null,
        ];

        $dataLines = [];

        while (($line = $this->readStreamLine()) !== false) {
            $line = rtrim($line, "\r\n");

            // Empty line indicates end of event
            if ($line === '') {
                if (! empty($dataLines)) {
                    $event['data'] = implode("\n", $dataLines);
                    return $event;
                }
                continue;
            }

            // Parse SSE field
            if (strpos($line, ':') === false) {
                $field = $line;
                $value = '';
            } else {
                [$field, $value] = explode(':', $line, 2);
                $value = ltrim($value, ' ');
            }

            switch ($field) {
                case 'event':
                    $event['event'] = $value;
                    break;
                case 'data':
                    $dataLines[] = $value;
                    break;
                case 'id':
                    $event['id'] = $value;
                    break;
                case 'retry':
                    $event['retry'] = (int) $value;
                    break;
                default:
                    // Ignore unknown fields
                    $this->logger->debug('Unknown SSE field', ['field' => $field, 'value' => $value]);
                    break;
            }
        }

        // Connection was closed
        $this->connected = false;
        $this->logger->info('SSE connection closed by server');
        return null;
    }

    /**
     * Parse endpoint event data.
     *
     * @param array<string, mixed> $event Endpoint event
     * @return array<string, string> Parsed endpoint information
     * @throws TransportError If event data is invalid
     */
    protected function parseEndpointEvent(array $event): array
    {
        $eventData = $event['data'];

        // First try to parse as JSON (new format)
        $data = json_decode($eventData, true);

        if ($data && isset($data['uri'])) {
            // JSON format with uri field
            return [
                'post_endpoint' => $data['uri'],
            ];
        }

        // If not JSON or no uri field, treat as direct URL string (legacy format)
        if (is_string($eventData) && ! empty($eventData)) {
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
        throw new TransportError('Invalid endpoint event data format. Expected JSON with "uri" field or direct URL string. Got: ' . json_encode($eventData));
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

        $decoded = json_decode($data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->warning('Failed to parse JSON-RPC message', [
                'error' => json_last_error_msg(),
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
     * Create SSE connection to the server.
     *
     * @param string $url Target URL
     * @param array<string, string> $headers HTTP headers
     * @return resource Stream resource
     * @throws TransportError If connection fails
     */
    protected function createSseConnection(string $url, array $headers)
    {
        $this->logger->debug('Creating SSE connection', [
            'url' => $url,
            'headers' => array_keys($headers),
        ]);

        // Build headers for stream context
        $headerLines = [];
        foreach ($headers as $key => $value) {
            $headerLines[] = $key . ': ' . $value;
        }

        // Add user agent and other config headers
        $configHeaders = $this->config->getHeaders();
        foreach ($configHeaders as $key => $value) {
            $headerLines[] = $key . ': ' . $value;
        }

        if (! in_array('User-Agent: ' . $this->config->getUserAgent(), $headerLines, true)) {
            $headerLines[] = 'User-Agent: ' . $this->config->getUserAgent();
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headerLines),
                'timeout' => $this->connectionTimeout,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => $this->config->getValidateSsl(),
                'verify_peer_name' => $this->config->getValidateSsl(),
            ],
        ]);

        $stream = fopen($url, 'r', false, $context);

        if ($stream === false) {
            throw new TransportError('Failed to open SSE connection to: ' . $url);
        }

        // Set stream to non-blocking mode
        stream_set_blocking($stream, false);

        return $stream;
    }

    /**
     * Read a line from the stream with timeout.
     *
     * @return false|string Line content or false if no data available
     */
    private function readStreamLine()
    {
        if (! $this->stream) {
            return false;
        }

        // Check if the stream is still valid/readable
        if (! is_resource($this->stream) || feof($this->stream)) {
            $this->logger->debug('Stream is no longer valid or at EOF');
            return false;
        }

        // Fallback method: use non-blocking fgets with manual timeout
        $startTime = microtime(true);
        $timeoutSeconds = $this->readTimeoutUs / 1000000;

        while (microtime(true) - $startTime < $timeoutSeconds) {
            $line = fgets($this->stream);
            if ($line !== false) {
                return $line;
            }

            // Small delay to prevent busy waiting
            usleep(1000); // 1ms
        }

        return '';
    }
}
