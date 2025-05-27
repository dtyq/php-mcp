<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Core;

use Dtyq\PhpMcp\Types\Core\JsonRpcError;
use Dtyq\PhpMcp\Types\Core\JsonRpcRequest;
use Dtyq\PhpMcp\Types\Core\JsonRpcResponse;

/**
 * Interface for request routing implementations.
 */
interface RequestRouterInterface
{
    /**
     * Route request to appropriate handler.
     *
     * @param JsonRpcRequest $request The request to route
     * @return null|JsonRpcError|JsonRpcResponse The response or null if not handled
     */
    public function route(JsonRpcRequest $request);

    /**
     * Register a route handler.
     *
     * @param string $method The method name to handle
     * @param callable $handler The handler function
     */
    public function addRoute(string $method, callable $handler): void;

    /**
     * Remove a route handler.
     *
     * @param string $method The method name to remove
     */
    public function removeRoute(string $method): void;

    /**
     * Get all registered routes.
     *
     * @return string[]
     */
    public function getRoutes(): array;
}
