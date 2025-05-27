<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Core;

use Dtyq\PhpMcp\Shared\Exceptions\TransportError;
use Dtyq\PhpMcp\Types\Core\JsonRpcError;
use Dtyq\PhpMcp\Types\Core\JsonRpcRequest;
use Dtyq\PhpMcp\Types\Core\JsonRpcResponse;

/**
 * Interface for transport layer implementations.
 *
 * Defines the contract for all transport mechanisms (STDIO, HTTP, WebSocket, etc.)
 * that can be used to communicate with MCP clients.
 */
interface TransportInterface
{
    /**
     * Start the transport layer.
     *
     * @throws TransportError If transport cannot be started
     */
    public function start(): void;

    /**
     * Stop the transport layer.
     */
    public function stop(): void;

    /**
     * Check if transport is running.
     */
    public function isRunning(): bool;

    /**
     * Set request handler.
     *
     * @param callable $handler Function that processes JsonRpcRequest and returns JsonRpcResponse|JsonRpcError
     */
    public function setRequestHandler(callable $handler): void;

    /**
     * Send response to client.
     *
     * @param JsonRpcError|JsonRpcResponse $response The response to send
     * @param mixed $clientContext Client context (connection, session, etc.)
     * @throws TransportError If response cannot be sent
     */
    public function sendResponse(JsonRpcError|JsonRpcResponse $response, mixed $clientContext = null): void;

    /**
     * Send notification to client.
     *
     * @param string $method Notification method
     * @param array<string, mixed> $params Notification parameters
     * @param mixed $clientContext Client context (connection, session, etc.)
     * @throws TransportError If notification cannot be sent
     */
    public function sendNotification(string $method, array $params = [], mixed $clientContext = null): void;

    /**
     * Get transport type identifier.
     */
    public function getType(): string;

    /**
     * Get transport configuration.
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array;

    /**
     * Get transport statistics.
     *
     * @return array<string, mixed>
     */
    public function getStats(): array;
}
