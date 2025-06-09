<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client\Session;

use Dtyq\PhpMcp\Client\Core\AbstractSession;
use Dtyq\PhpMcp\Client\Core\SessionInterface;
use Dtyq\PhpMcp\Client\Core\TransportInterface;
use Dtyq\PhpMcp\Shared\Exceptions\ProtocolError;
use Dtyq\PhpMcp\Shared\Utilities\JsonUtils;
use Dtyq\PhpMcp\Types\Core\JsonRpcRequest;
use Dtyq\PhpMcp\Types\Core\JsonRpcResponse;
use Dtyq\PhpMcp\Types\Core\NotificationInterface;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;
use Dtyq\PhpMcp\Types\Core\RequestInterface;
use Dtyq\PhpMcp\Types\Notifications\InitializedNotification;
use Dtyq\PhpMcp\Types\Prompts\GetPromptResult;
use Dtyq\PhpMcp\Types\Requests\CallToolRequest;
use Dtyq\PhpMcp\Types\Requests\GetPromptRequest;
use Dtyq\PhpMcp\Types\Requests\InitializeRequest;
use Dtyq\PhpMcp\Types\Requests\ListPromptsRequest;
use Dtyq\PhpMcp\Types\Requests\ListResourcesRequest;
use Dtyq\PhpMcp\Types\Requests\ListToolsRequest;
use Dtyq\PhpMcp\Types\Requests\ReadResourceRequest;
use Dtyq\PhpMcp\Types\Responses\CallToolResult;
use Dtyq\PhpMcp\Types\Responses\InitializeResult;
use Dtyq\PhpMcp\Types\Responses\ListPromptsResult;
use Dtyq\PhpMcp\Types\Responses\ListResourcesResult;
use Dtyq\PhpMcp\Types\Responses\ListToolsResult;
use Dtyq\PhpMcp\Types\Responses\ReadResourceResult;
use Exception;

/**
 * Main client session implementation for MCP communication.
 *
 * This class handles the complete MCP client session lifecycle including:
 * - Session initialization with capability negotiation
 * - Request/response correlation and timeout handling
 * - MCP-specific operations (tools, resources)
 * - Protocol compliance and error handling
 */
class ClientSession extends AbstractSession implements SessionInterface
{
    private SessionMetadata $metadata;

    private SessionState $state;

    /** @var string Unique session identifier */
    private string $sessionId;

    /** @var array<string, mixed> Server capabilities received during initialization */
    private array $serverCapabilities = [];

    /** @var array<string, mixed> Client capabilities to send during initialization */
    private array $clientCapabilities = [];

    /**
     * @param null|array<string, mixed> $clientCapabilities Optional client capabilities
     * @param null|string $sessionId Optional session ID (generated if null)
     */
    public function __construct(
        TransportInterface $transport,
        SessionMetadata $metadata,
        ?array $clientCapabilities = null,
        ?string $sessionId = null
    ) {
        parent::__construct($transport);
        $this->metadata = $metadata;
        $this->state = new SessionState();
        $this->sessionId = $sessionId ?? $this->generateSessionId();

        // Set default timeout from metadata
        $this->setDefaultTimeout($this->metadata->getResponseTimeout());

        // Set default client capabilities
        $this->clientCapabilities = $clientCapabilities ?? $this->getDefaultClientCapabilities();
    }

    public function initialize(): void
    {
        $this->validateSessionState('initialize', false);

        if ($this->initialized) {
            throw new ProtocolError('Session already initialized');
        }

        try {
            $response = $this->sendRequestAndWaitForResponse(
                new InitializeRequest(
                    ProtocolConstants::LATEST_PROTOCOL_VERSION,
                    $this->clientCapabilities,
                    $this->metadata->createClientInfo()
                ),
                $this->metadata->getInitializationTimeout()
            );

            // Parse initialize result
            $initResult = InitializeResult::fromArray($response->getResult());

            // Store server capabilities
            $this->serverCapabilities = $initResult->getCapabilities();
            $this->state->markAsInitialized($initResult);

            // Validate protocol version compatibility
            $this->validateProtocolVersion($initResult->getProtocolVersion());

            // Mark session as initialized
            $this->markAsInitialized();

            // Send initialized notification to complete handshake
            $this->sendNotification(new InitializedNotification());

            $this->state->setState(SessionState::STATE_READY);
        } catch (Exception $e) {
            $this->state->setState(SessionState::STATE_ERROR);
            throw new ProtocolError('Session initialization failed: ' . $e->getMessage());
        }
    }

    public function sendRequest(RequestInterface $request, ?int $timeout = null): array
    {
        $this->validateSessionState('sendRequest');

        $response = $this->sendRequestAndWaitForResponse($request, $timeout ? (float) $timeout : null);

        if (isset($response->getResult()['error'])) {
            throw new ProtocolError('Request failed: ' . JsonUtils::encode($response->getResult()['error']));
        }

        return $response->getResult();
    }

    public function sendNotification(NotificationInterface $notification): void
    {
        $this->validateSessionState('sendNotification');

        $jsonRpcRequest = new JsonRpcRequest($notification->getMethod(), $notification->getParams());
        $message = JsonUtils::encode($jsonRpcRequest->toJsonRpc());
        $this->transport->send($message);
    }

    public function listTools(): ListToolsResult
    {
        $this->validateSessionState('listTools');

        // Check if server supports tools capability
        if (! $this->hasServerCapability('tools')) {
            // Return empty result if server doesn't support tools
            return ListToolsResult::fromArray(['tools' => []]);
        }

        $request = new ListToolsRequest();
        $response = $this->sendRequestAndWaitForResponse($request);

        return ListToolsResult::fromArray($response->getResult());
    }

    public function callTool(string $name, ?array $arguments = null): CallToolResult
    {
        $this->validateSessionState('callTool');

        // Check if server supports tools capability
        if (! $this->hasServerCapability('tools')) {
            throw new ProtocolError('Server does not support tools capability');
        }

        $request = new CallToolRequest($name, $arguments);
        $response = $this->sendRequestAndWaitForResponse($request);

        return CallToolResult::fromArray($response->getResult());
    }

    public function listResources(): ListResourcesResult
    {
        $this->validateSessionState('listResources');

        // Check if server supports resources capability
        if (! $this->hasServerCapability('resources')) {
            // Return empty result if server doesn't support resources
            return ListResourcesResult::fromArray(['resources' => []]);
        }

        $request = new ListResourcesRequest();
        $response = $this->sendRequestAndWaitForResponse($request);

        return ListResourcesResult::fromArray($response->getResult());
    }

    public function listPrompts(): ListPromptsResult
    {
        $this->validateSessionState('listPrompts');

        // Check if server supports prompts capability
        if (! $this->hasServerCapability('prompts')) {
            // Return empty result if server doesn't support prompts
            return ListPromptsResult::fromArray(['prompts' => []]);
        }

        $request = new ListPromptsRequest();
        $response = $this->sendRequestAndWaitForResponse($request);

        return ListPromptsResult::fromArray($response->getResult());
    }

    /**
     * Get a prompt from the server.
     *
     * @param string $name Prompt name
     * @param null|array<string, mixed> $arguments Optional prompt arguments
     * @return GetPromptResult The prompt result
     */
    public function getPrompt(string $name, ?array $arguments = null): GetPromptResult
    {
        $this->validateSessionState('getPrompt');

        // Check if server supports prompts capability
        if (! $this->hasServerCapability('prompts')) {
            throw new ProtocolError('Server does not support prompts capability');
        }

        $request = new GetPromptRequest($name, $arguments);
        $response = $this->sendRequestAndWaitForResponse($request);

        return GetPromptResult::fromArray($response->getResult());
    }

    public function readResource(string $uri): ReadResourceResult
    {
        $this->validateSessionState('readResource');

        // Check if server supports resources capability
        if (! $this->hasServerCapability('resources')) {
            throw new ProtocolError('Server does not support resources capability');
        }

        $request = new ReadResourceRequest($uri);
        $response = $this->sendRequestAndWaitForResponse($request);

        return ReadResourceResult::fromArray($response->getResult());
    }

    public function close(): void
    {
        try {
            // Update state before closing transport
            $this->state->setState(SessionState::STATE_DISCONNECTED);
        } catch (Exception $e) {
            // Continue with cleanup even if state update fails
        }

        // Call parent cleanup
        parent::close();

        // Reset session state
        $this->state->reset();
        $this->serverCapabilities = [];
    }

    /**
     * Get server capabilities received during initialization.
     *
     * @return array<string, mixed> Server capabilities
     * @throws ProtocolError If session not initialized
     */
    public function getServerCapabilities(): array
    {
        if (! $this->initialized) {
            throw new ProtocolError('Session not initialized - no server capabilities available');
        }

        return $this->serverCapabilities;
    }

    /**
     * Get client capabilities that were sent during initialization.
     *
     * @return array<string, mixed> Client capabilities
     */
    public function getClientCapabilities(): array
    {
        return $this->clientCapabilities;
    }

    /**
     * Check if server has a specific capability.
     *
     * @param string $capability Capability path (e.g., 'tools.listChanged')
     * @return bool True if server has the capability
     */
    public function hasServerCapability(string $capability): bool
    {
        if (! $this->initialized) {
            return false;
        }

        return $this->state->hasServerCapability($capability);
    }

    /**
     * Get the current session state.
     *
     * @return string Current state
     */
    public function getSessionState(): string
    {
        return $this->state->getCurrentState();
    }

    /**
     * Get the unique session identifier.
     *
     * @return string Session ID
     */
    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    /**
     * Get session metadata.
     *
     * @return SessionMetadata Session metadata object
     */
    public function getMetadata(): SessionMetadata
    {
        return $this->metadata;
    }

    /**
     * Get session statistics.
     *
     * @return array<string, mixed> Session statistics
     */
    public function getStats(): array
    {
        $clientInfo = $this->metadata->createClientInfo();

        return [
            'session_id' => $this->sessionId,
            'initialized' => $this->initialized,
            'state' => $this->state->getCurrentState(),
            'transport_connected' => $this->transport->isConnected(),
            'server_capabilities' => $this->serverCapabilities,
            'client_capabilities' => $this->clientCapabilities,
            'pending_requests' => count($this->pendingRequests),
            'default_timeout' => $this->defaultTimeout,
            'metadata' => [
                'client_name' => $clientInfo['name'] ?? 'unknown',
                'client_version' => $clientInfo['version'] ?? 'unknown',
                'response_timeout' => $this->metadata->getResponseTimeout(),
                'initialize_timeout' => $this->metadata->getInitializationTimeout(),
            ],
        ];
    }

    /**
     * Handle unexpected messages received while waiting for responses.
     *
     * This method processes server-initiated requests and notifications.
     *
     * @param JsonRpcResponse $message The unexpected message
     */
    protected function handleUnexpectedMessage($message): void
    {
        // For now, we log and ignore unexpected messages
        // Future implementations could:
        // 1. Handle server-initiated requests
        // 2. Process progress notifications
        // 3. Handle capability change notifications
        // 4. Store messages for later processing

        // TODO: Add proper logging when logger becomes available
        // For debugging purposes during development, we could store the message ID:
        // $messageId = $message->getId();
        // Could store for debugging: "Received unexpected message with ID: {$messageId}"
    }

    /**
     * Validate protocol version compatibility.
     *
     * @param string $serverVersion Server's protocol version
     * @throws ProtocolError If versions are incompatible
     */
    private function validateProtocolVersion(string $serverVersion): void
    {
        if (! in_array($serverVersion, ProtocolConstants::getSupportedProtocolVersions())) {
            throw new ProtocolError(
                'Protocol version mismatch. Client: ' . ProtocolConstants::LATEST_PROTOCOL_VERSION
                . ', Server: ' . $serverVersion
            );
        }
    }

    /**
     * Get default client capabilities.
     *
     * @return array<string, mixed> Default capabilities
     */
    private function getDefaultClientCapabilities(): array
    {
        return [
            'tools' => [
                'listChanged' => false,
            ],
            'resources' => [
                'listChanged' => false,
            ],
            'prompts' => [
                'listChanged' => false,
            ],
        ];
    }

    /**
     * Generate a unique session ID.
     *
     * @return string Unique session identifier
     */
    private function generateSessionId(): string
    {
        // Generate a more robust session ID with timestamp and random component
        $timestamp = (int) (microtime(true) * 1000); // milliseconds
        $randomBytes = random_bytes(8);
        $randomHex = bin2hex($randomBytes);

        return "mcp_session_{$timestamp}_{$randomHex}";
    }
}
