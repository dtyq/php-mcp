<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client\Core;

use Dtyq\PhpMcp\Shared\Exceptions\ProtocolError;
use Dtyq\PhpMcp\Shared\Exceptions\TransportError;
use Dtyq\PhpMcp\Types\Core\NotificationInterface;
use Dtyq\PhpMcp\Types\Core\RequestInterface;
use Dtyq\PhpMcp\Types\Responses\CallToolResult;
use Dtyq\PhpMcp\Types\Responses\ListResourcesResult;
use Dtyq\PhpMcp\Types\Responses\ListToolsResult;

/**
 * Interface for MCP client session management.
 *
 * This interface defines the contract for managing an active session
 * with an MCP server, including initialization, request handling,
 * and MCP-specific operations.
 */
interface SessionInterface
{
    /**
     * Initialize the session with the MCP server.
     *
     * This method performs the MCP initialization handshake:
     * 1. Sends initialize request with client capabilities
     * 2. Receives server capabilities and protocol version
     * 3. Sends initialized notification to complete handshake
     *
     * @throws ProtocolError If initialization fails or protocol mismatch
     * @throws TransportError If communication fails
     */
    public function initialize(): void;

    /**
     * Send a request to the server and wait for response.
     *
     * This method handles the complete request-response cycle including
     * request ID generation, message serialization, response waiting,
     * and timeout handling.
     *
     * @param null|int $timeout Timeout in seconds (null for default)
     * @return array<string, mixed> The response result
     * @throws ProtocolError If response contains an error
     * @throws TransportError If communication fails or times out
     */
    public function sendRequest(RequestInterface $request, ?int $timeout = null): array;

    /**
     * Send a notification to the server (no response expected).
     *
     * Notifications are fire-and-forget messages that do not expect
     * a response from the server.
     *
     * @param NotificationInterface $notification The notification to send
     * @throws TransportError If communication fails
     */
    public function sendNotification(NotificationInterface $notification): void;

    /**
     * List available tools on the server.
     *
     * @return ListToolsResult The available tools
     * @throws ProtocolError If request fails
     * @throws TransportError If communication fails
     */
    public function listTools(): ListToolsResult;

    /**
     * Call a tool on the server.
     *
     * @param string $name The tool name
     * @param null|array<string, mixed> $arguments Tool arguments
     * @return CallToolResult The tool execution result
     * @throws ProtocolError If tool call fails
     * @throws TransportError If communication fails
     */
    public function callTool(string $name, ?array $arguments = null): CallToolResult;

    /**
     * List available resources on the server.
     *
     * @return ListResourcesResult The available resources
     * @throws ProtocolError If request fails
     * @throws TransportError If communication fails
     */
    public function listResources(): ListResourcesResult;

    /**
     * Check if the session has been initialized.
     *
     * @return bool True if initialization completed successfully
     */
    public function isInitialized(): bool;

    /**
     * Close the session.
     *
     * This method performs cleanup and notifies the server that
     * the session is ending.
     */
    public function close(): void;
}
