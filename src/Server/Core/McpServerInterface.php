<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Core;

use Dtyq\PhpMcp\Shared\Exceptions\McpError;
use Dtyq\PhpMcp\Types\Core\JsonRpcError;
use Dtyq\PhpMcp\Types\Core\JsonRpcRequest;
use Dtyq\PhpMcp\Types\Core\JsonRpcResponse;

/**
 * Interface for MCP Server implementations.
 *
 * Defines the contract that all MCP servers must implement,
 * including lifecycle management, configuration, and request processing.
 */
interface McpServerInterface
{
    /**
     * Start the server.
     *
     * @throws McpError If server cannot be started
     */
    public function start(): void;

    /**
     * Stop the server.
     */
    public function stop(): void;

    /**
     * Restart the server.
     */
    public function restart(): void;

    /**
     * Get current server state.
     */
    public function getState(): string;

    /**
     * Check if server is running.
     */
    public function isRunning(): bool;

    /**
     * Get server configuration.
     */
    public function getConfig(): ServerConfig;

    /**
     * Set transport layer.
     */
    public function setTransport(TransportInterface $transport): void;

    /**
     * Get transport layer.
     */
    public function getTransport(): ?TransportInterface;

    /**
     * Set request router.
     */
    public function setRouter(RequestRouterInterface $router): void;

    /**
     * Get request router.
     */
    public function getRouter(): ?RequestRouterInterface;

    /**
     * Set server capabilities.
     */
    public function setCapabilities(ServerCapabilities $capabilities): void;

    /**
     * Get server capabilities.
     */
    public function getCapabilities(): ?ServerCapabilities;

    /**
     * Add middleware.
     */
    public function addMiddleware(MiddlewareInterface $middleware): void;

    /**
     * Get middleware stack.
     *
     * @return MiddlewareInterface[]
     */
    public function getMiddleware(): array;

    /**
     * Add event listener.
     */
    public function addEventListener(string $event, callable $listener): void;

    /**
     * Get server statistics.
     *
     * @return array<string, mixed>
     */
    public function getStats(): array;

    /**
     * Process incoming request.
     *
     * @param JsonRpcRequest $request The incoming request
     * @return JsonRpcError|JsonRpcResponse The response
     */
    public function processRequest(JsonRpcRequest $request);
}
