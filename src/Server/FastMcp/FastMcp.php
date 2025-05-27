<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\FastMcp;

use Dtyq\PhpMcp\Server\Core\DefaultRequestRouter;
use Dtyq\PhpMcp\Server\Core\McpServer;
use Dtyq\PhpMcp\Server\Core\MiddlewareInterface;
use Dtyq\PhpMcp\Server\Core\TransportInterface;
use Dtyq\PhpMcp\Shared\Exceptions\ProtocolError;
use Dtyq\PhpMcp\Shared\Logging\McpLogger;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * FastMCP - Simplified API for creating MCP servers.
 *
 * This class provides a fluent, easy-to-use API for quickly setting up
 * MCP servers with sensible defaults and minimal configuration.
 *
 * Example usage:
 * ```php
 * $server = FastMcp::create('My Server')
 *     ->withDescription('A simple MCP server')
 *     ->withTools(['calculator', 'weather'])
 *     ->withResources(['files', 'database'])
 *     ->build();
 *
 * $server->start();
 * ```
 */
class FastMcp
{
    /**
     * Server configuration builder.
     */
    private ServerConfigBuilder $configBuilder;

    /**
     * Server capabilities builder.
     */
    private ServerCapabilitiesBuilder $capabilitiesBuilder;

    /**
     * Transport configuration.
     */
    private ?TransportInterface $transport = null;

    /**
     * Custom logger.
     */
    private ?LoggerInterface $logger = null;

    /**
     * Middleware stack.
     *
     * @var MiddlewareInterface[]
     */
    private array $middleware = [];

    /**
     * Route handlers.
     *
     * @var array<string, callable>
     */
    private array $routes = [];

    /**
     * Event listeners.
     *
     * @var array<string, callable[]>
     */
    private array $eventListeners = [];

    /**
     * Private constructor - use create() method.
     */
    private function __construct(string $name)
    {
        $this->configBuilder = new ServerConfigBuilder($name);
        $this->capabilitiesBuilder = new ServerCapabilitiesBuilder();
    }

    /**
     * Create a new FastMCP instance.
     *
     * @param string $name Server name
     */
    public static function create(string $name): self
    {
        return new self($name);
    }

    /**
     * Set server version.
     *
     * @param string $version Server version
     */
    public function withVersion(string $version): self
    {
        $this->configBuilder->setVersion($version);
        return $this;
    }

    /**
     * Set server description.
     *
     * @param string $description Server description
     */
    public function withDescription(string $description): self
    {
        $this->configBuilder->setDescription($description);
        return $this;
    }

    /**
     * Set vendor information.
     *
     * @param string $name Vendor name
     * @param string $url Vendor URL
     */
    public function withVendor(string $name, string $url = ''): self
    {
        $this->configBuilder->setVendor(['name' => $name, 'url' => $url]);
        return $this;
    }

    /**
     * Enable tools capability.
     *
     * @param string[] $toolNames List of tool names to support
     */
    public function withTools(array $toolNames = []): self
    {
        $this->capabilitiesBuilder->enableTools($toolNames);
        return $this;
    }

    /**
     * Enable resources capability.
     *
     * @param string[] $resourceTypes List of resource types to support
     */
    public function withResources(array $resourceTypes = []): self
    {
        $this->capabilitiesBuilder->enableResources($resourceTypes);
        return $this;
    }

    /**
     * Enable prompts capability.
     *
     * @param string[] $promptNames List of prompt names to support
     */
    public function withPrompts(array $promptNames = []): self
    {
        $this->capabilitiesBuilder->enablePrompts($promptNames);
        return $this;
    }

    /**
     * Enable sampling capability.
     */
    public function withSampling(): self
    {
        $this->capabilitiesBuilder->enableSampling();
        return $this;
    }

    /**
     * Enable roots capability.
     *
     * @param string[] $rootPaths List of root paths to support
     */
    public function withRoots(array $rootPaths = []): self
    {
        $this->capabilitiesBuilder->enableRoots($rootPaths);
        return $this;
    }

    /**
     * Set transport layer.
     *
     * @param TransportInterface $transport Transport implementation
     */
    public function withTransport(TransportInterface $transport): self
    {
        $this->transport = $transport;
        return $this;
    }

    /**
     * Use STDIO transport (default).
     *
     * @param array<string, mixed> $options STDIO transport options
     */
    public function withStdio(array $options = []): self
    {
        // TODO: Create STDIO transport when implemented
        $this->configBuilder->setTransportType('stdio', $options);
        return $this;
    }

    /**
     * Use HTTP transport.
     *
     * @param string $host Host to bind to
     * @param int $port Port to bind to
     * @param array<string, mixed> $options HTTP transport options
     */
    public function withHttp(string $host = '127.0.0.1', int $port = 8080, array $options = []): self
    {
        $options = array_merge([
            'host' => $host,
            'port' => $port,
        ], $options);

        // TODO: Create HTTP transport when implemented
        $this->configBuilder->setTransportType('http', $options);
        return $this;
    }

    /**
     * Set custom logger.
     *
     * @param LoggerInterface $logger Logger instance
     */
    public function withLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Set log level.
     *
     * @param string $level Log level (debug, info, warning, error)
     */
    public function withLogLevel(string $level): self
    {
        $this->configBuilder->setLogLevel($level);
        return $this;
    }

    /**
     * Add middleware.
     *
     * @param MiddlewareInterface $middleware Middleware instance
     */
    public function withMiddleware(MiddlewareInterface $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * Add route handler.
     *
     * @param string $method Method name to handle
     * @param callable $handler Handler function
     */
    public function withRoute(string $method, callable $handler): self
    {
        $this->routes[$method] = $handler;
        return $this;
    }

    /**
     * Add multiple route handlers.
     *
     * @param array<string, callable> $routes Array of method => handler pairs
     */
    public function withRoutes(array $routes): self
    {
        $this->routes = array_merge($this->routes, $routes);
        return $this;
    }

    /**
     * Add event listener.
     *
     * @param string $event Event name
     * @param callable $listener Event listener function
     */
    public function withEventListener(string $event, callable $listener): self
    {
        if (! isset($this->eventListeners[$event])) {
            $this->eventListeners[$event] = [];
        }
        $this->eventListeners[$event][] = $listener;
        return $this;
    }

    /**
     * Set performance options.
     *
     * @param array<string, mixed> $options Performance options
     */
    public function withPerformance(array $options): self
    {
        $this->configBuilder->setPerformance($options);
        return $this;
    }

    /**
     * Set security options.
     *
     * @param array<string, mixed> $options Security options
     */
    public function withSecurity(array $options): self
    {
        $this->configBuilder->setSecurity($options);
        return $this;
    }

    /**
     * Enable CORS with default settings.
     *
     * @param string[] $allowedOrigins Allowed origins (default: ['*'])
     */
    public function withCors(array $allowedOrigins = ['*']): self
    {
        $this->configBuilder->setSecurity([
            'enable_cors' => true,
            'allowed_origins' => $allowedOrigins,
        ]);
        return $this;
    }

    /**
     * Build and return the configured MCP server.
     *
     * @return McpServer The configured server instance
     * @throws ProtocolError If server cannot be built
     */
    public function build(): McpServer
    {
        try {
            // Build configuration
            $config = $this->configBuilder->build();

            // Validate configuration
            $errors = $config->validate();
            if (! empty($errors)) {
                throw new ProtocolError('Configuration validation failed: ' . implode(', ', $errors));
            }

            // Create server
            $server = new McpServer($config, $this->logger);

            // Set capabilities
            $capabilities = $this->capabilitiesBuilder->build();
            $server->setCapabilities($capabilities);

            // Set transport if provided
            if ($this->transport) {
                $server->setTransport($this->transport);
            }

            // Add middleware
            foreach ($this->middleware as $middleware) {
                $server->addMiddleware($middleware);
            }

            // Add routes
            if (! empty($this->routes)) {
                $router = $server->getRouter();
                if (! $router) {
                    // Create default router if none exists
                    $router = new DefaultRequestRouter(
                        $this->logger ?? new McpLogger()
                    );
                    $server->setRouter($router);
                }

                foreach ($this->routes as $method => $handler) {
                    $router->addRoute($method, $handler);
                }
            }

            // Add event listeners
            foreach ($this->eventListeners as $event => $listeners) {
                foreach ($listeners as $listener) {
                    $server->addEventListener($event, $listener);
                }
            }

            return $server;
        } catch (Throwable $e) {
            throw new ProtocolError('Failed to build server: ' . $e->getMessage());
        }
    }

    /**
     * Build and start the server in one call.
     *
     * @return McpServer The running server instance
     * @throws ProtocolError If server cannot be built or started
     */
    public function start(): McpServer
    {
        $server = $this->build();
        $server->start();
        return $server;
    }

    /**
     * Create a simple server with minimal configuration.
     *
     * @param string $name Server name
     * @param array<string, callable> $routes Route handlers (method => handler)
     * @return McpServer Ready-to-use server instance
     */
    public static function simple(string $name, array $routes = []): McpServer
    {
        $builder = self::create($name)
            ->withDescription('Simple MCP server created with FastMCP')
            ->withStdio();

        if (! empty($routes)) {
            $builder->withRoutes($routes);
        }

        return $builder->build();
    }

    /**
     * Create a server with all capabilities enabled.
     *
     * @param string $name Server name
     * @return self Builder instance with all capabilities enabled
     */
    public static function full(string $name): self
    {
        return self::create($name)
            ->withDescription('Full-featured MCP server')
            ->withTools()
            ->withResources()
            ->withPrompts()
            ->withSampling()
            ->withRoots()
            ->withCors();
    }
}
