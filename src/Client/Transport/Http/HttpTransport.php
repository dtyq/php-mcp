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
use Dtyq\PhpMcp\Shared\Message\JsonRpcMessage;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;
use Exception;

/**
 * HTTP transport implementation for MCP client.
 *
 * This transport communicates with MCP servers through HTTP/SSE,
 * supporting both new protocol (2025-03-26) and legacy protocol (2024-11-05).
 * Features include event replay, authentication, connection recovery, and
 * automatic protocol detection.
 */
class HttpTransport implements TransportInterface
{
    /** @var HttpConfig Transport configuration */
    private HttpConfig $config;

    /** @var Application Application instance for services */
    private Application $application;

    /** @var LoggerProxy Logger instance */
    private LoggerProxy $logger;

    /** @var null|HttpConnectionManager HTTP connection manager */
    private ?HttpConnectionManager $connectionManager = null;

    /** @var null|SseStreamHandler SSE stream handler */
    private ?SseStreamHandler $sseHandler = null;

    /** @var null|HttpAuthenticator Authentication handler */
    private ?HttpAuthenticator $authenticator = null;

    /** @var null|EventStore Event storage for replay functionality */
    private ?EventStore $eventStore = null;

    /** @var bool Whether the transport is connected */
    private bool $connected = false;

    /** @var null|float Timestamp when connection was established */
    private ?float $connectedAt = null;

    /** @var string Detected or configured protocol version */
    private string $protocolVersion = '';

    /** @var null|string Session ID for new protocol */
    private ?string $sessionId = null;

    /** @var null|string POST endpoint for legacy protocol */
    private ?string $legacyPostEndpoint = null;

    /** @var null|string Last received event ID for resumption */
    private ?string $lastEventId = null;

    /** @var array<string, array<string, mixed>> Stored synchronous responses for new protocol */
    private array $syncResponses = [];

    /** @var array<string, mixed> Connection statistics */
    private array $stats = [
        'protocol_version' => '',
        'session_id' => null,
        'last_event_id' => null,
        'messages_sent' => 0,
        'messages_received' => 0,
        'events_stored' => 0,
        'connection_attempts' => 0,
        'resumption_attempts' => 0,
    ];

    /**
     * @param HttpConfig $config Transport configuration
     * @param Application $application Application instance for services
     */
    public function __construct(HttpConfig $config, Application $application)
    {
        $this->config = $config;
        $this->application = $application;
        $this->logger = $application->getLogger();
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
                'protocol_version' => $this->config->getProtocolVersion(),
                'enable_resumption' => $this->config->isResumptionEnabled(),
            ]);

            ++$this->stats['connection_attempts'];

            // Initialize components
            $this->initializeComponents();

            // Detect or use configured protocol version
            $this->protocolVersion = $this->detectProtocolVersion();
            $this->stats['protocol_version'] = $this->protocolVersion;

            // Connect based on protocol version
            if ($this->protocolVersion === '2025-03-26') {
                $this->connectStreamableHttp();
            } else {
                $this->connectLegacyHttpSse();
            }

            $this->connected = true;
            $this->connectedAt = microtime(true);

            $this->logger->info('HTTP transport connected successfully', [
                'protocol_version' => $this->protocolVersion,
                'session_id' => $this->sessionId,
                'connected_at' => $this->connectedAt,
            ]);
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
            // Parse message for handling
            $jsonRpcMessage = JsonRpcMessage::fromJson($message);

            // Send message based on protocol
            if ($this->protocolVersion === '2025-03-26') {
                $this->sendNewProtocol($jsonRpcMessage);
            } else {
                $this->sendLegacyProtocol($jsonRpcMessage);
            }

            ++$this->stats['messages_sent'];
        } catch (Exception $e) {
            $this->logger->error('Failed to send message', [
                'direction' => 'outgoing',
                'error' => $e->getMessage(),
                'message_preview' => substr($message, 0, 100),
                'protocol_version' => $this->protocolVersion,
            ]);
            throw new TransportError('Failed to send message: ' . $e->getMessage());
        }
    }

    public function receive(?int $timeout = null): ?string
    {
        $this->ensureConnected();

        try {
            // For new protocol, check stored synchronous responses first
            if ($this->protocolVersion === '2025-03-26') {
                foreach ($this->syncResponses as $messageId => $responseData) {
                    unset($this->syncResponses[$messageId]);
                    $message = json_encode($responseData);
                    ++$this->stats['messages_received'];
                    return $message;
                }

                // If no stored responses and SSE is not connected, try to establish it
                if ($this->sseHandler === null || ! $this->sseHandler->isConnected()) {
                    if ($this->sseHandler === null) {
                        throw new TransportError('SSE handler not available');
                    }
                    $this->sseHandler->connectNew($this->config->getBaseUrl(), $this->sessionId);
                }
            }

            // Receive message from SSE stream
            if ($this->sseHandler === null) {
                throw new TransportError('SSE handler not available');
            }

            $jsonRpcMessage = $this->sseHandler->receiveMessage();
            if ($jsonRpcMessage === null) {
                return null;
            }

            $message = $jsonRpcMessage->toJson();
            ++$this->stats['messages_received'];
            return $message;
        } catch (Exception $e) {
            $this->logger->error('Failed to receive message', [
                'direction' => 'incoming',
                'error' => $e->getMessage(),
                'protocol_version' => $this->protocolVersion,
            ]);
            throw new TransportError('Failed to receive message: ' . $e->getMessage());
        }
    }

    public function isConnected(): bool
    {
        // For new protocol, we don't require SSE to be always connected
        if ($this->protocolVersion === '2025-03-26') {
            return $this->connected && $this->sessionId !== null;
        }

        // For legacy protocol, SSE must be connected
        return $this->connected
               && $this->sseHandler !== null
               && $this->sseHandler->isConnected();
    }

    public function disconnect(): void
    {
        if (! $this->connected) {
            return;
        }

        try {
            $this->logger->info('Disconnecting HTTP transport', [
                'protocol_version' => $this->protocolVersion,
                'session_id' => $this->sessionId,
            ]);

            // Send termination request if configured
            if ($this->config->shouldTerminateOnClose()) {
                $this->sendTerminationRequest();
            }

            $this->cleanup();

            $this->logger->info('HTTP transport disconnected successfully');
        } catch (Exception $e) {
            $this->logger->error('Error during disconnect', [
                'error' => $e->getMessage(),
            ]);
            throw new TransportError('Failed to disconnect: ' . $e->getMessage());
        }
    }

    public function getType(): string
    {
        return 'http';
    }

    /**
     * Get transport configuration.
     *
     * @return HttpConfig Transport configuration
     */
    public function getConfig(): HttpConfig
    {
        return $this->config;
    }

    /**
     * Get application instance.
     *
     * @return Application Application instance
     */
    public function getApplication(): Application
    {
        return $this->application;
    }

    /**
     * Get connection and transport statistics.
     *
     * @return array<string, mixed> Statistics
     */
    public function getStats(): array
    {
        $baseStats = $this->stats;
        $baseStats['connected'] = $this->connected;
        $baseStats['connected_at'] = $this->connectedAt;
        $baseStats['uptime'] = $this->connectedAt ? microtime(true) - $this->connectedAt : 0;

        if ($this->connectionManager) {
            $baseStats['http_stats'] = $this->connectionManager->getStats();
        }

        if ($this->sseHandler) {
            $baseStats['sse_stats'] = $this->sseHandler->getStats();
        }

        if ($this->authenticator) {
            $baseStats['auth_stats'] = $this->authenticator->getAuthStatus();
        }

        return $baseStats;
    }

    /**
     * Get current session ID.
     *
     * @return null|string Session ID or null if not available
     */
    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    /**
     * Get last event ID for resumption.
     *
     * @return null|string Last event ID or null if not available
     */
    public function getLastEventId(): ?string
    {
        return $this->lastEventId;
    }

    /**
     * Get detected protocol version.
     *
     * @return string Protocol version
     */
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    /**
     * Handle SSE event for event storage.
     *
     * @param JsonRpcMessage $message Received message
     * @param null|string $eventId Event ID
     */
    public function handleSseEvent(JsonRpcMessage $message, ?string $eventId = null): void
    {
        if ($this->eventStore && $eventId) {
            $streamId = $this->sessionId ?? 'default';
            $this->eventStore->storeEvent($streamId, $message);
            $this->lastEventId = $eventId;
            ++$this->stats['events_stored'];
            $this->stats['last_event_id'] = $eventId;
        }
    }

    /**
     * Initialize transport components.
     */
    protected function initializeComponents(): void
    {
        $this->connectionManager = new HttpConnectionManager($this->config, $this->logger);
        $this->sseHandler = new SseStreamHandler($this->config, $this->logger);
        $this->authenticator = new HttpAuthenticator($this->config, $this->logger);

        // Initialize event store if resumption is enabled
        if ($this->config->isResumptionEnabled()) {
            $this->eventStore = $this->createEventStore();

            // Set up event callback for SSE handler
            $this->sseHandler->setEventCallback([$this, 'handleSseEvent']);
        }
    }

    /**
     * Create event store based on configuration.
     *
     * @return EventStore Event store instance
     * @throws TransportError If event store type is not supported
     */
    protected function createEventStore(): EventStore
    {
        $storeType = $this->config->getEventStoreType();
        $storeConfig = $this->config->getEventStoreConfig();

        switch ($storeType) {
            case 'memory':
                // Use default values or extract from config
                $maxEvents = $storeConfig['max_events'] ?? 1000;
                $expiration = $storeConfig['expiration'] ?? 3600;
                return new InMemoryEventStore($maxEvents, $expiration);
            case 'file':
                // Future extension point
                throw new TransportError('File event store not yet implemented');
            case 'redis':
                // Future extension point
                throw new TransportError('Redis event store not yet implemented');
            default:
                throw new TransportError('Unsupported event store type: ' . $storeType);
        }
    }

    /**
     * Detect protocol version or use configured version.
     *
     * @return string Detected protocol version
     */
    protected function detectProtocolVersion(): string
    {
        $configuredVersion = $this->config->getProtocolVersion();
        if ($configuredVersion !== 'auto') {
            return $configuredVersion;
        }

        // Use a single HTTP request without retries for protocol detection
        try {
            $response = $this->performProtocolDetectionRequest();

            if ($response['status_code'] === 200) {
                $this->logger->info('Detected new protocol (2025-03-26)');
                return '2025-03-26';
            }

            // Check for 405 Method Not Allowed - indicates legacy protocol
            if ($response['status_code'] === 405) {
                $this->logger->info('Server returned 405 for POST, detected legacy protocol (2024-11-05)');
                return '2024-11-05';
            }

            // For other error codes, try legacy protocol
            $this->logger->info('Server returned status ' . $response['status_code'] . ', falling back to legacy protocol');
        } catch (Exception $e) {
            // Protocol detection failed, continue to fallback
        }

        // Fallback to legacy protocol
        $this->logger->info('Using legacy protocol (2024-11-05)');
        return '2024-11-05';
    }

    /**
     * Perform a single HTTP request for protocol detection without retries.
     *
     * @return array<string, mixed> Response data
     * @throws TransportError If request execution fails
     */
    protected function performProtocolDetectionRequest(): array
    {
        if ($this->connectionManager === null) {
            throw new TransportError('Connection manager not initialized');
        }

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'text/event-stream, application/json',
            'User-Agent' => $this->config->getUserAgent(),
        ];

        // Add authentication headers
        if ($this->authenticator) {
            $headers = $this->authenticator->addAuthHeaders($headers);
        }

        $initMessage = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2025-03-26',
                'capabilities' => [],
                'clientInfo' => [
                    'name' => 'php-mcp-client',
                    'version' => '1.0.0',
                ],
            ],
        ];

        // Use the connection manager's executeRequest method directly to bypass retry logic
        return $this->connectionManager->executeRequest(
            'POST',
            $this->config->getBaseUrl(),
            $headers,
            $initMessage
        );
    }

    /**
     * Try initialize request to detect protocol support.
     *
     * @param string $protocolVersion Protocol version to test
     * @return array<string, mixed> Response data
     * @throws TransportError If request fails
     */
    protected function tryInitializeRequest(string $protocolVersion): array
    {
        if ($this->connectionManager === null) {
            throw new TransportError('Connection manager not initialized');
        }

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => $this->config->getUserAgent(),
        ];

        // Add authentication headers
        if ($this->authenticator) {
            $headers = $this->authenticator->addAuthHeaders($headers);
        }

        $initMessage = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => $protocolVersion,
                'capabilities' => [],
                'clientInfo' => [
                    'name' => 'php-mcp-client',
                    'version' => '1.0.0',
                ],
            ],
        ];

        return $this->connectionManager->sendPostRequest(
            $this->config->getBaseUrl(),
            $headers,
            $initMessage
        );
    }

    /**
     * Connect using new protocol (Streamable HTTP).
     */
    protected function connectStreamableHttp(): void
    {
        // Try resumption if enabled and we have a last event ID
        if ($this->lastEventId && $this->config->isResumptionEnabled()) {
            $this->attemptResumption();
            return;
        }

        // Send initialize request
        $initResponse = $this->sendInitializeRequest('2025-03-26');

        // Extract session ID from response headers or MCP-Session-Id header
        $this->sessionId = $this->extractSessionId($initResponse);
        $this->stats['session_id'] = $this->sessionId;

        // Validate that we got a session ID
        if (! $this->sessionId) {
            throw new TransportError('Server did not return a session ID in initialize response');
        }

        // Send initialized notification
        $this->sendInitializedNotification();

        // Mark as connected - SSE will be established on demand when needed
        $this->connected = true;
        $this->connectedAt = microtime(true);
        $this->protocolVersion = ProtocolConstants::PROTOCOL_VERSION_20250326;
    }

    /**
     * Attempt connection resumption.
     */
    protected function attemptResumption(): void
    {
        $this->logger->info('Attempting connection resumption', [
            'last_event_id' => $this->lastEventId,
        ]);

        ++$this->stats['resumption_attempts'];

        try {
            $headers = [
                'Accept' => 'text/event-stream',
                'Last-Event-ID' => $this->lastEventId,
                'Mcp-Session-Id' => $this->sessionId ?? '',
            ];

            // Add authentication headers
            if ($this->authenticator) {
                $headers = $this->authenticator->addAuthHeaders($headers);
            }

            if ($this->sseHandler === null) {
                throw new TransportError('SSE handler not initialized');
            }
            $this->sseHandler->connectWithResumption($this->config->getBaseUrl(), $headers);

            $this->logger->info('Connection resumption successful');
        } catch (Exception $e) {
            $this->logger->warning('Connection resumption failed, using regular connection', [
                'error' => $e->getMessage(),
            ]);

            // Clear invalid event ID and try regular connection
            $this->lastEventId = null;
            $this->connectStreamableHttp();
        }
    }

    /**
     * Connect using legacy protocol (HTTP+SSE).
     */
    protected function connectLegacyHttpSse(): void
    {
        // Establish SSE connection and get endpoint information
        if ($this->sseHandler === null) {
            throw new TransportError('SSE handler not initialized');
        }
        $endpointInfo = $this->sseHandler->connectLegacy($this->config->getBaseUrl());

        // Convert relative URL to absolute URL if needed
        $this->legacyPostEndpoint = $this->resolveUrl($endpointInfo['post_endpoint']);
        $this->protocolVersion = ProtocolConstants::PROTOCOL_VERSION_20241105;
    }

    /**
     * Resolve a potentially relative URL against the base URL.
     *
     * @param string $url URL that might be relative
     * @return string Absolute URL
     */
    protected function resolveUrl(string $url): string
    {
        // If URL is already absolute, return as-is
        if (filter_var($url, FILTER_VALIDATE_URL) !== false) {
            return $url;
        }

        // If URL starts with '//', it's protocol-relative
        if (str_starts_with($url, '//')) {
            $parsedBase = parse_url($this->config->getBaseUrl());
            return ($parsedBase['scheme'] ?? 'https') . ':' . $url;
        }

        // If URL starts with '/', it's host-relative
        if (str_starts_with($url, '/')) {
            $parsedBase = parse_url($this->config->getBaseUrl());
            if (! $parsedBase) {
                throw new TransportError('Invalid base URL: ' . $this->config->getBaseUrl());
            }

            $scheme = $parsedBase['scheme'] ?? 'https';
            $host = $parsedBase['host'] ?? '';
            $port = isset($parsedBase['port']) ? ':' . $parsedBase['port'] : '';

            return $scheme . '://' . $host . $port . $url;
        }

        // For path-relative URLs, resolve against base URL directory
        $baseUrl = rtrim($this->config->getBaseUrl(), '/');
        return $baseUrl . '/' . ltrim($url, '/');
    }

    /**
     * Send initialize request.
     *
     * @param string $protocolVersion Protocol version
     * @return array<string, mixed> Response data
     */
    protected function sendInitializeRequest(string $protocolVersion): array
    {
        if ($this->connectionManager === null || $this->authenticator === null) {
            throw new TransportError('Components not initialized');
        }

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'text/event-stream, application/json',
        ];

        $headers = $this->authenticator->addAuthHeaders($headers);

        $initMessage = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => $protocolVersion,
                'capabilities' => $this->getClientCapabilities(),
                'clientInfo' => [
                    'name' => 'php-mcp-client',
                    'version' => '1.0.0',
                ],
            ],
        ];

        $response = $this->connectionManager->sendPostRequest(
            $this->config->getBaseUrl(),
            $headers,
            $initMessage
        );

        if (! $response['success'] || ! isset($response['data'])) {
            throw new TransportError('Initialize request failed');
        }

        // Return the complete response so headers can be accessed
        return $response;
    }

    /**
     * Send initialized notification.
     */
    protected function sendInitializedNotification(): void
    {
        if ($this->connectionManager === null || $this->authenticator === null) {
            throw new TransportError('Components not initialized');
        }

        $headers = [
            'Content-Type' => 'application/json',
            'Mcp-Session-Id' => $this->sessionId ?? '',
        ];

        $headers = $this->authenticator->addAuthHeaders($headers);

        $notification = [
            'jsonrpc' => '2.0',
            'method' => 'notifications/initialized',
        ];

        $this->connectionManager->sendPostRequest(
            $this->config->getBaseUrl(),
            $headers,
            $notification
        );
    }

    /**
     * Get client capabilities.
     *
     * @return array<string, mixed> Client capabilities
     */
    protected function getClientCapabilities(): array
    {
        return [
            'tools' => (object) [
                'listChanged' => false,
            ],
            'resources' => (object) [
                'listChanged' => false,
            ],
            'prompts' => (object) [
                'listChanged' => false,
            ],
            'sampling' => (object) [],
            'experimental' => (object) [],
        ];
    }

    /**
     * Send message using new protocol.
     *
     * @param JsonRpcMessage $message Message to send
     */
    protected function sendNewProtocol(JsonRpcMessage $message): void
    {
        if ($this->connectionManager === null || $this->authenticator === null) {
            throw new TransportError('Components not initialized');
        }

        $headers = [
            'Content-Type' => 'application/json',
            'Mcp-Session-Id' => $this->sessionId ?? '',
            'Accept' => 'application/json', // For new protocol, we expect JSON responses for sync messages
        ];

        $headers = $this->authenticator->addAuthHeaders($headers);

        // For requests with ID (synchronous), store the response for retrieval
        $messageArray = $message->toArray();
        $hasId = isset($messageArray['id']);

        $response = $this->connectionManager->sendPostRequest(
            $this->config->getBaseUrl(),
            $headers,
            $messageArray
        );

        // For synchronous requests, store the response
        if ($hasId && isset($response['data'])) {
            $this->storeSyncResponse($messageArray['id'], $response['data']);
        }
    }

    /**
     * Store synchronous response for later retrieval.
     *
     * @param mixed $messageId Message ID
     * @param array<string, mixed> $responseData Response data
     */
    protected function storeSyncResponse($messageId, array $responseData): void
    {
        if (! isset($this->syncResponses)) {
            $this->syncResponses = [];
        }
        $this->syncResponses[(string) $messageId] = $responseData;
    }

    /**
     * Retrieve stored synchronous response.
     *
     * @param string $messageId Message ID
     * @return null|array<string, mixed> Response data or null if not found
     */
    protected function getSyncResponse(string $messageId): ?array
    {
        return $this->syncResponses[$messageId] ?? null;
    }

    /**
     * Send message using legacy protocol.
     *
     * @param JsonRpcMessage $message Message to send
     */
    protected function sendLegacyProtocol(JsonRpcMessage $message): void
    {
        if ($this->connectionManager === null || $this->authenticator === null) {
            throw new TransportError('Components not initialized');
        }

        if ($this->legacyPostEndpoint === null) {
            throw new TransportError('Legacy POST endpoint not available');
        }

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $headers = $this->authenticator->addAuthHeaders($headers);

        $this->connectionManager->sendPostRequest(
            $this->legacyPostEndpoint,
            $headers,
            $message->toArray()
        );
    }

    /**
     * Send session termination request.
     */
    protected function sendTerminationRequest(): void
    {
        if (! $this->sessionId || $this->connectionManager === null || $this->authenticator === null) {
            return;
        }

        try {
            $headers = [
                'Mcp-Session-Id' => $this->sessionId,
            ];

            $headers = $this->authenticator->addAuthHeaders($headers);

            $this->connectionManager->sendDeleteRequest(
                $this->config->getBaseUrl(),
                $headers
            );
        } catch (Exception $e) {
            $this->logger->warning('Failed to send session termination request', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Ensure transport is connected.
     *
     * @throws TransportError If not connected
     */
    protected function ensureConnected(): void
    {
        if (! $this->connected) {
            throw new TransportError('Transport is not connected');
        }

        // For new protocol, we don't require SSE to be always connected
        if ($this->protocolVersion === '2025-03-26') {
            if ($this->sessionId === null) {
                throw new TransportError('Session ID is not available');
            }
            return;
        }

        // For legacy protocol, SSE must be connected
        if ($this->sseHandler === null || ! $this->sseHandler->isConnected()) {
            throw new TransportError('SSE connection is not available');
        }
    }

    /**
     * Extract message ID from JSON-RPC message for logging.
     *
     * @param string $message JSON-RPC message
     * @return null|string Message ID or null if not found
     */
    protected function extractMessageId(string $message): ?string
    {
        $decoded = json_decode($message, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($decoded['id'])) {
            return (string) $decoded['id'];
        }
        return null;
    }

    /**
     * Extract session ID from HTTP response.
     *
     * @param array<string, mixed> $response HTTP response data
     * @return null|string Session ID or null if not found
     */
    protected function extractSessionId(array $response): ?string
    {
        // Check if session ID is in response headers
        if (isset($response['headers']['mcp-session-id'])) {
            return $response['headers']['mcp-session-id'];
        }

        // Check response data for session ID
        if (isset($response['sessionId'])) {
            return (string) $response['sessionId'];
        }

        // Check JSON-RPC result for session ID
        if (isset($response['data']['result']['sessionId'])) {
            return (string) $response['data']['result']['sessionId'];
        }

        // Some servers might return it in the root of the JSON response
        if (isset($response['data']['sessionId'])) {
            return (string) $response['data']['sessionId'];
        }

        return null;
    }

    /**
     * Clean up resources and reset state.
     */
    protected function cleanup(): void
    {
        if ($this->sseHandler) {
            $this->sseHandler->disconnect();
            $this->sseHandler = null;
        }

        if ($this->eventStore) {
            $this->eventStore->cleanup();
            $this->eventStore = null;
        }

        $this->connectionManager = null;
        $this->authenticator = null;
        $this->connected = false;
        $this->connectedAt = null;
        $this->sessionId = null;
        $this->legacyPostEndpoint = null;
        $this->protocolVersion = '';
    }
}
