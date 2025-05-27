<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Core;

use Dtyq\PhpMcp\Types\Core\JsonRpcError;
use Dtyq\PhpMcp\Types\Core\JsonRpcRequest;
use Dtyq\PhpMcp\Types\Core\JsonRpcResponse;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Default request router implementation.
 */
class DefaultRequestRouter implements RequestRouterInterface
{
    /**
     * @var array<string, callable>
     */
    private array $routes = [];

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Route request to appropriate handler.
     *
     * @param JsonRpcRequest $request The request to route
     * @return null|JsonRpcError|JsonRpcResponse The response or null if not handled
     */
    public function route(JsonRpcRequest $request)
    {
        $method = $request->getMethod();

        if (! isset($this->routes[$method])) {
            return null;
        }

        try {
            $handler = $this->routes[$method];
            return call_user_func($handler, $request);
        } catch (Throwable $e) {
            $this->logger->error('Route handler error', [
                'method' => $method,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function addRoute(string $method, callable $handler): void
    {
        $this->routes[$method] = $handler;
        $this->logger->debug('Route added', ['method' => $method]);
    }

    public function removeRoute(string $method): void
    {
        unset($this->routes[$method]);
        $this->logger->debug('Route removed', ['method' => $method]);
    }

    /**
     * Get all registered routes.
     *
     * @return string[]
     */
    public function getRoutes(): array
    {
        return array_keys($this->routes);
    }
}
