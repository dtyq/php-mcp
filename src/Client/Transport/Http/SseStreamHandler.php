<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client\Transport\Http;

use Dtyq\PhpMcp\Client\Configuration\HttpConfig;
use Dtyq\PhpMcp\Shared\Exceptions\TransportError;
use Dtyq\PhpMcp\Shared\Kernel\Logger\LoggerProxy;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;
use Exception;

/**
 * SSE stream handler for HTTP transport.
 *
 * This class handles Server-Sent Events connections and message parsing
 * for receiving server-to-client messages in MCP HTTP transport.
 */
class SseStreamHandler
{
    /** @var HttpConfig Transport configuration */
    private HttpConfig $config;

    /** @var LoggerProxy Logger instance */
    private LoggerProxy $logger;

    /** @var null|resource SSE stream resource */
    private $stream;

    /** @var bool Whether the stream is connected */
    private bool $connected = false;

    /** @var array<string, mixed> Connection statistics */
    private array $stats = [];

    /** @var string Buffer for incomplete SSE events */
    private string $eventBuffer = '';

    /** @var array<string, mixed> Performance metrics */
    private array $performanceMetrics = [];

    /** @var int Optimized buffer size for reading */
    private int $readBufferSize = 8192;

    /** @var null|string Current session ID */
    private ?string $sessionId = null;

    /** @var int Connection timeout in seconds */
    private int $connectionTimeout;

    /** @var int Read timeout in seconds */
    private int $readTimeout;

    /**
     * @param HttpConfig $config Transport configuration
     * @param LoggerProxy $logger Logger instance
     */
    public function __construct(HttpConfig $config, LoggerProxy $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->connectionTimeout = (int) $this->config->getTimeout();
        $this->readTimeout = (int) $this->config->getSseTimeout();

        $this->initializeStats();
    }

    /**
     * Connect to SSE stream.
     *
     * @param null|string $sessionId Session ID for the connection
     * @throws TransportError If connection fails
     */
    public function connect(?string $sessionId = null): void
    {
        if ($this->connected) {
            $this->logger->warning('SSE stream already connected, disconnecting first');
            $this->disconnect();
        }

        $this->sessionId = $sessionId;

        $this->logger->info('Connecting to SSE stream', [
            'base_url' => $this->config->getBaseUrl(),
            'session_id' => $sessionId,
            'timeout' => $this->connectionTimeout,
        ]);

        try {
            $this->establishConnection();
            $this->connected = true;
            $this->updateStats('connections_established');

            $this->logger->info('SSE stream connected successfully', [
                'session_id' => $sessionId,
            ]);
        } catch (Exception $e) {
            $this->updateStats('connection_errors');
            $this->logger->error('Failed to connect to SSE stream', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
            ]);
            throw new TransportError('SSE connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Read an event from the SSE stream.
     *
     * @return null|array<string, mixed> Parsed SSE event or null if no event available
     * @throws TransportError If stream is not connected or read fails
     */
    public function readEvent(): ?array
    {
        if (! $this->connected || $this->stream === null) {
            throw new TransportError('SSE stream not connected');
        }

        $startTime = microtime(true);

        try {
            $eventData = $this->readSseEventOptimized();
            if ($eventData === null) {
                return null;
            }

            // Track performance metrics
            $duration = microtime(true) - $startTime;
            $this->trackEventPerformance($duration, strlen($eventData['data'] ?? ''));

            $this->updateStats('events_received');

            $this->logger->debug('Received SSE event', [
                'event_type' => $eventData['event'] ?? 'message',
                'data_length' => strlen($eventData['data'] ?? ''),
                'session_id' => $this->sessionId,
                'parse_duration' => $duration,
            ]);

            return $eventData;
        } catch (Exception $e) {
            $this->updateStats('read_errors');
            $this->logger->error('Failed to read SSE event', [
                'error' => $e->getMessage(),
                'session_id' => $this->sessionId,
                'read_duration' => microtime(true) - $startTime,
            ]);

            // Check if connection is still alive
            if (! $this->isStreamAlive()) {
                $this->handleConnectionLoss();
            }

            throw new TransportError('Failed to read SSE event: ' . $e->getMessage());
        }
    }

    /**
     * Check if the SSE stream is connected.
     *
     * @return bool True if connected
     */
    public function isConnected(): bool
    {
        return $this->connected && $this->stream !== null && $this->isStreamAlive();
    }

    /**
     * Disconnect from SSE stream.
     */
    public function disconnect(): void
    {
        if ($this->stream !== null) {
            $this->logger->debug('Closing SSE stream', [
                'session_id' => $this->sessionId,
            ]);

            fclose($this->stream);
            $this->stream = null;
        }

        $this->connected = false;
        $this->sessionId = null;

        $this->logger->info('SSE stream disconnected');
    }

    /**
     * Get connection statistics.
     *
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        return array_merge($this->stats, [
            'connected' => $this->connected,
            'session_id' => $this->sessionId,
            'stream_alive' => $this->stream !== null ? $this->isStreamAlive() : false,
        ]);
    }

    /**
     * Get performance metrics.
     *
     * @return array<string, mixed>
     */
    public function getPerformanceMetrics(): array
    {
        return $this->performanceMetrics;
    }

    /**
     * Establish SSE connection.
     *
     * @throws TransportError If connection fails
     */
    private function establishConnection(): void
    {
        $url = $this->config->getEndpointUrl();
        $headers = $this->buildHeaders();

        // Create HTTP context for SSE connection
        $context = [
            'http' => [
                'method' => 'GET',
                'header' => $this->formatHeaders($headers),
                'timeout' => $this->connectionTimeout,
                'ignore_errors' => true,
            ],
        ];

        // Add SSL context if needed
        if ($this->config->shouldValidateSsl()) {
            $context['ssl'] = [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
            ];
        } else {
            $context['ssl'] = [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ];
        }

        $streamContext = stream_context_create($context);

        // Open SSE stream
        $this->stream = @fopen($url, 'r', false, $streamContext);

        if ($this->stream === false) {
            $error = error_get_last();
            throw new TransportError('Failed to open SSE stream: ' . ($error['message'] ?? 'Unknown error'));
        }

        // Set stream timeout
        stream_set_timeout($this->stream, $this->readTimeout);

        // Make stream non-blocking for event reading
        stream_set_blocking($this->stream, false);
    }

    /**
     * Build headers for SSE connection.
     *
     * @return array<string, string>
     */
    private function buildHeaders(): array
    {
        $headers = [
            ProtocolConstants::HTTP_HEADER_ACCEPT => ProtocolConstants::HTTP_ACCEPT_SSE,
            ProtocolConstants::HTTP_HEADER_CACHE_CONTROL => 'no-cache',
            ProtocolConstants::HTTP_HEADER_USER_AGENT => $this->config->getUserAgent(),
        ];

        // Add session ID if available
        if ($this->sessionId !== null) {
            $headers[ProtocolConstants::HTTP_HEADER_SESSION_ID] = $this->sessionId;
        }

        // Add custom headers from config
        foreach ($this->config->getHeaders() as $name => $value) {
            $headers[$name] = $value;
        }

        return $headers;
    }

    /**
     * Format headers for HTTP context.
     *
     * @param array<string, string> $headers
     */
    private function formatHeaders(array $headers): string
    {
        $formatted = [];
        foreach ($headers as $name => $value) {
            $formatted[] = $name . ': ' . $value;
        }
        return implode("\r\n", $formatted);
    }

    /**
     * Read a single SSE event from the stream with optimization.
     *
     * @return null|array<string, mixed> Parsed event data or null if no complete event
     * @throws TransportError If read fails
     */
    private function readSseEventOptimized(): ?array
    {
        if ($this->stream === null) {
            throw new TransportError('Stream not available');
        }

        // Read data into buffer more efficiently
        $data = fread($this->stream, $this->readBufferSize);
        if ($data === false) {
            throw new TransportError('Failed to read from SSE stream');
        }

        // Add to existing buffer
        $this->eventBuffer .= $data;

        // Check if we have a complete event (ends with double newline)
        $eventEndPos = strpos($this->eventBuffer, "\n\n");
        if ($eventEndPos === false) {
            return null; // No complete event yet
        }

        // Extract the complete event
        $eventText = substr($this->eventBuffer, 0, $eventEndPos);
        $this->eventBuffer = substr($this->eventBuffer, $eventEndPos + 2);

        return $this->parseEventData($eventText);
    }

    /**
     * Parse SSE event data from text format.
     *
     * @param string $eventText Raw event text
     * @return array<string, mixed> Parsed event data
     */
    private function parseEventData(string $eventText): array
    {
        $event = [
            'event' => null,
            'data' => '',
            'id' => null,
            'retry' => null,
        ];

        $lines = explode("\n", $eventText);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || $line[0] === ':') {
                continue; // Skip empty lines and comments
            }

            $colonPos = strpos($line, ':');
            if ($colonPos === false) {
                continue; // Invalid line format
            }

            $field = substr($line, 0, $colonPos);
            $value = ltrim(substr($line, $colonPos + 1));

            switch ($field) {
                case 'event':
                    $event['event'] = $value;
                    break;
                case 'data':
                    $event['data'] .= ($event['data'] ? "\n" : '') . $value;
                    break;
                case 'id':
                    $event['id'] = $value;
                    break;
                case 'retry':
                    $event['retry'] = (int) $value;
                    break;
            }
        }

        return $event;
    }

    /**
     * Track performance metrics for event processing.
     *
     * @param float $duration Event processing duration
     * @param int $dataSize Size of event data
     */
    private function trackEventPerformance(float $duration, int $dataSize): void
    {
        if (! isset($this->performanceMetrics['total_events'])) {
            $this->performanceMetrics['total_events'] = 0;
            $this->performanceMetrics['total_duration'] = 0.0;
            $this->performanceMetrics['total_data_size'] = 0;
            $this->performanceMetrics['avg_duration'] = 0.0;
            $this->performanceMetrics['avg_data_size'] = 0.0;
        }

        ++$this->performanceMetrics['total_events'];
        $this->performanceMetrics['total_duration'] += $duration;
        $this->performanceMetrics['total_data_size'] += $dataSize;

        $totalEvents = $this->performanceMetrics['total_events'];
        $this->performanceMetrics['avg_duration'] = $this->performanceMetrics['total_duration'] / $totalEvents;
        $this->performanceMetrics['avg_data_size'] = $this->performanceMetrics['total_data_size'] / $totalEvents;

        $this->performanceMetrics['last_duration'] = $duration;
        $this->performanceMetrics['last_data_size'] = $dataSize;

        // Track min/max
        if (! isset($this->performanceMetrics['min_duration']) || $duration < $this->performanceMetrics['min_duration']) {
            $this->performanceMetrics['min_duration'] = $duration;
        }
        if (! isset($this->performanceMetrics['max_duration']) || $duration > $this->performanceMetrics['max_duration']) {
            $this->performanceMetrics['max_duration'] = $duration;
        }
    }

    /**
     * Check if the stream is still alive.
     *
     * @return bool True if stream is alive
     */
    private function isStreamAlive(): bool
    {
        if ($this->stream === null) {
            return false;
        }

        $streamMeta = stream_get_meta_data($this->stream);
        return ! $streamMeta['eof'] && ! feof($this->stream);
    }

    /**
     * Handle connection loss.
     */
    private function handleConnectionLoss(): void
    {
        $this->logger->warning('SSE connection lost', [
            'session_id' => $this->sessionId,
        ]);

        $this->connected = false;
        if ($this->stream !== null) {
            fclose($this->stream);
            $this->stream = null;
        }

        $this->updateStats('connection_lost');
    }

    /**
     * Initialize statistics tracking.
     */
    private function initializeStats(): void
    {
        $this->stats = [
            'connections_established' => 0,
            'connection_errors' => 0,
            'connection_lost' => 0,
            'events_received' => 0,
            'read_errors' => 0,
            'last_event_time' => null,
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

        if ($key === 'events_received') {
            $this->stats['last_event_time'] = microtime(true);
        }
    }
}
