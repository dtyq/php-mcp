<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Core;

use Dtyq\PhpMcp\Shared\Exceptions\McpError;
use Dtyq\PhpMcp\Shared\Exceptions\ProtocolError;
use Dtyq\PhpMcp\Shared\Logging\McpLogger;
use Dtyq\PhpMcp\Types\Core\JsonRpcError;
use Dtyq\PhpMcp\Types\Core\JsonRpcRequest;
use Dtyq\PhpMcp\Types\Core\JsonRpcResponse;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Main MCP Server implementation.
 *
 * This class serves as the core MCP server that handles:
 * - Server lifecycle management (start, stop, restart)
 * - Transport layer integration
 * - Request routing and processing
 * - Capability management and negotiation
 * - Error handling and recovery
 */
class McpServer implements McpServerInterface
{
    /**
     * Server states.
     */
    public const STATE_STOPPED = 'stopped';

    public const STATE_STARTING = 'starting';

    public const STATE_RUNNING = 'running';

    public const STATE_STOPPING = 'stopping';

    public const STATE_ERROR = 'error';

    /**
     * Current server state.
     */
    private string $state = self::STATE_STOPPED;

    /**
     * Server configuration.
     */
    private ServerConfig $config;

    /**
     * Logger instance.
     */
    private LoggerInterface $logger;

    /**
     * Transport layer instance.
     */
    private ?TransportInterface $transport = null;

    /**
     * Request router.
     */
    private ?RequestRouterInterface $router = null;

    /**
     * Server capabilities.
     */
    private ?ServerCapabilities $capabilities = null;

    /**
     * Middleware stack.
     *
     * @var MiddlewareInterface[]
     */
    private array $middleware = [];

    /**
     * Event listeners.
     *
     * @var array<string, callable[]>
     */
    private array $eventListeners = [];

    /**
     * Server statistics.
     *
     * @var array<string, mixed>
     */
    private array $stats = [
        'requests_processed' => 0,
        'errors_occurred' => 0,
        'start_time' => null,
        'uptime' => 0,
    ];

    /**
     * Constructor.
     *
     * @param ServerConfig $config Server configuration
     * @param null|LoggerInterface $logger Logger instance
     */
    public function __construct(ServerConfig $config, ?LoggerInterface $logger = null)
    {
        $this->config = $config;
        $this->logger = $logger ?? new McpLogger();

        $this->logger->info('MCP Server initialized', [
            'server_name' => $config->getName(),
            'version' => $config->getVersion(),
        ]);
    }

    /**
     * Start the server.
     *
     * @throws McpError If server cannot be started
     */
    public function start(): void
    {
        if ($this->state !== self::STATE_STOPPED) {
            throw new ProtocolError('Server is already running or starting');
        }

        $this->setState(self::STATE_STARTING);

        try {
            $this->logger->info('Starting MCP Server');

            // Initialize components
            $this->initializeComponents();

            // Start transport layer
            if ($this->transport) {
                $this->transport->start();
            }

            // Set server as running
            $this->setState(self::STATE_RUNNING);
            $this->stats['start_time'] = time();

            $this->logger->info('MCP Server started successfully', [
                'transport' => $this->transport ? get_class($this->transport) : 'none',
                'capabilities' => $this->capabilities ? $this->capabilities->toArray() : [],
            ]);

            // Emit server started event
            $this->emitEvent('server.started', ['server' => $this]);
        } catch (Throwable $e) {
            $this->setState(self::STATE_ERROR);
            $this->logger->error('Failed to start MCP Server', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new ProtocolError('Failed to start server: ' . $e->getMessage());
        }
    }

    /**
     * Stop the server.
     */
    public function stop(): void
    {
        if ($this->state === self::STATE_STOPPED) {
            return;
        }

        $this->setState(self::STATE_STOPPING);

        try {
            $this->logger->info('Stopping MCP Server');

            // Emit server stopping event
            $this->emitEvent('server.stopping', ['server' => $this]);

            // Stop transport layer
            if ($this->transport) {
                $this->transport->stop();
            }

            // Clean up resources
            $this->cleanup();

            $this->setState(self::STATE_STOPPED);
            $this->stats['start_time'] = null;

            $this->logger->info('MCP Server stopped successfully');

            // Emit server stopped event
            $this->emitEvent('server.stopped', ['server' => $this]);
        } catch (Throwable $e) {
            $this->setState(self::STATE_ERROR);
            $this->logger->error('Error stopping MCP Server', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Restart the server.
     */
    public function restart(): void
    {
        $this->logger->info('Restarting MCP Server');
        $this->stop();
        $this->start();
    }

    /**
     * Get current server state.
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * Check if server is running.
     */
    public function isRunning(): bool
    {
        return $this->state === self::STATE_RUNNING;
    }

    /**
     * Get server configuration.
     */
    public function getConfig(): ServerConfig
    {
        return $this->config;
    }

    /**
     * Set transport layer.
     */
    public function setTransport(TransportInterface $transport): void
    {
        if ($this->state === self::STATE_RUNNING) {
            throw new ProtocolError('Cannot change transport while server is running');
        }

        $this->transport = $transport;
        $this->logger->debug('Transport layer set', ['transport' => get_class($transport)]);
    }

    /**
     * Get transport layer.
     */
    public function getTransport(): ?TransportInterface
    {
        return $this->transport;
    }

    /**
     * Set request router.
     */
    public function setRouter(RequestRouterInterface $router): void
    {
        $this->router = $router;
        $this->logger->debug('Request router set', ['router' => get_class($router)]);
    }

    /**
     * Get request router.
     */
    public function getRouter(): ?RequestRouterInterface
    {
        return $this->router;
    }

    /**
     * Set server capabilities.
     */
    public function setCapabilities(ServerCapabilities $capabilities): void
    {
        $this->capabilities = $capabilities;
        $this->logger->debug('Server capabilities set', ['capabilities' => $capabilities->toArray()]);
    }

    /**
     * Get server capabilities.
     */
    public function getCapabilities(): ?ServerCapabilities
    {
        return $this->capabilities;
    }

    /**
     * Add middleware.
     */
    public function addMiddleware(MiddlewareInterface $middleware): void
    {
        $this->middleware[] = $middleware;
        $this->logger->debug('Middleware added', ['middleware' => get_class($middleware)]);
    }

    /**
     * Get middleware stack.
     *
     * @return MiddlewareInterface[]
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Add event listener.
     */
    public function addEventListener(string $event, callable $listener): void
    {
        if (! isset($this->eventListeners[$event])) {
            $this->eventListeners[$event] = [];
        }
        $this->eventListeners[$event][] = $listener;
        $this->logger->debug('Event listener added', ['event' => $event]);
    }

    /**
     * Get server statistics.
     *
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        $stats = $this->stats;
        if ($stats['start_time']) {
            $stats['uptime'] = time() - $stats['start_time'];
        }
        return $stats;
    }

    /**
     * Process incoming request.
     *
     * @param JsonRpcRequest $request The incoming request
     * @return JsonRpcError|JsonRpcResponse The response
     */
    public function processRequest(JsonRpcRequest $request)
    {
        ++$this->stats['requests_processed'];

        try {
            $this->logger->debug('Processing request', [
                'method' => $request->getMethod(),
                'id' => $request->getId(),
            ]);

            // Emit request received event
            $this->emitEvent('request.received', ['request' => $request]);

            // Apply middleware
            $response = $this->applyMiddleware($request);

            if ($response === null && $this->router) {
                // Route request
                $response = $this->router->route($request);
            }

            if ($response === null) {
                // Method not found
                $response = JsonRpcError::fromError(
                    $request->getId(),
                    -32601, // METHOD_NOT_FOUND
                    'Method not found: ' . $request->getMethod()
                );
            }

            // Emit response ready event
            $this->emitEvent('response.ready', ['request' => $request, 'response' => $response]);

            return $response;
        } catch (Throwable $e) {
            ++$this->stats['errors_occurred'];
            $this->logger->error('Error processing request', [
                'method' => $request->getMethod(),
                'id' => $request->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Emit error event
            $this->emitEvent('request.error', ['request' => $request, 'error' => $e]);

            return JsonRpcError::fromError(
                $request->getId(),
                -32603, // INTERNAL_ERROR
                'Internal server error: ' . $e->getMessage()
            );
        }
    }

    /**
     * Set server state.
     */
    private function setState(string $state): void
    {
        $oldState = $this->state;
        $this->state = $state;

        $this->logger->debug('Server state changed', [
            'old_state' => $oldState,
            'new_state' => $state,
        ]);

        // Emit state change event
        $this->emitEvent('state.changed', [
            'old_state' => $oldState,
            'new_state' => $state,
            'server' => $this,
        ]);
    }

    /**
     * Initialize server components.
     */
    private function initializeComponents(): void
    {
        // Initialize default router if none set
        if (! $this->router) {
            $this->router = new DefaultRequestRouter($this->logger);
        }

        // Initialize default capabilities if none set
        if (! $this->capabilities) {
            $this->capabilities = new ServerCapabilities();
        }

        // Configure transport if set
        if ($this->transport) {
            $this->transport->setRequestHandler([$this, 'processRequest']);
        }
    }

    /**
     * Apply middleware to request.
     */
    private function applyMiddleware(JsonRpcRequest $request): ?JsonRpcResponse
    {
        foreach ($this->middleware as $middleware) {
            $response = $middleware->process($request);
            if ($response !== null) {
                return $response;
            }
        }
        return null;
    }

    /**
     * Emit event to listeners.
     *
     * @param array<string, mixed> $data
     */
    private function emitEvent(string $event, array $data = []): void
    {
        if (! isset($this->eventListeners[$event])) {
            return;
        }

        foreach ($this->eventListeners[$event] as $listener) {
            try {
                call_user_func($listener, $data);
            } catch (Throwable $e) {
                $this->logger->warning('Event listener error', [
                    'event' => $event,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Clean up server resources.
     */
    private function cleanup(): void
    {
        // Clear middleware
        $this->middleware = [];

        // Clear event listeners
        $this->eventListeners = [];

        // Reset stats
        $this->stats['requests_processed'] = 0;
        $this->stats['errors_occurred'] = 0;
    }
}
