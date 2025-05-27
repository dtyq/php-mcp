<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Core;

use Dtyq\PhpMcp\Shared\Exceptions\ErrorCodes;
use Dtyq\PhpMcp\Shared\Exceptions\ErrorData;
use Dtyq\PhpMcp\Shared\Exceptions\McpError;
use Dtyq\PhpMcp\Shared\Exceptions\ProtocolError;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Default implementation of lifecycle management.
 *
 * This class manages the server lifecycle including initialization,
 * startup, shutdown, and restart procedures with proper error handling
 * and graceful degradation.
 */
class LifecycleManager implements LifecycleManagerInterface
{
    /**
     * Lifecycle phases.
     */
    public const PHASE_STOPPED = 'stopped';

    public const PHASE_INITIALIZING = 'initializing';

    public const PHASE_STARTING = 'starting';

    public const PHASE_RUNNING = 'running';

    public const PHASE_STOPPING = 'stopping';

    public const PHASE_ERROR = 'error';

    /**
     * Current lifecycle phase.
     */
    private string $currentPhase = self::PHASE_STOPPED;

    /**
     * Logger instance.
     */
    private LoggerInterface $logger;

    /**
     * MCP Server instance.
     */
    private McpServerInterface $server;

    /**
     * Lifecycle hooks.
     *
     * @var array<string, array<int, callable[]>>
     */
    private array $hooks = [
        'initialize' => [],
        'startup' => [],
        'shutdown' => [],
    ];

    /**
     * Lifecycle statistics.
     *
     * @var array<string, mixed>
     */
    private array $stats = [
        'initialization_count' => 0,
        'startup_count' => 0,
        'shutdown_count' => 0,
        'restart_count' => 0,
        'error_count' => 0,
        'last_startup_time' => null,
        'last_shutdown_time' => null,
        'total_uptime' => 0,
    ];

    /**
     * Health check status.
     */
    private bool $healthy = true;

    /**
     * Last error information.
     *
     * @var null|array{phase: string, error: string, time: int}
     */
    private ?array $lastError = null;

    /**
     * Constructor.
     *
     * @param McpServerInterface $server The MCP server instance
     * @param LoggerInterface $logger Logger instance
     */
    public function __construct(McpServerInterface $server, LoggerInterface $logger)
    {
        $this->server = $server;
        $this->logger = $logger;

        // Register default hooks
        $this->registerDefaultHooks();
    }

    /**
     * Initialize the server components in the correct order.
     *
     * @throws McpError If initialization fails
     */
    public function initialize(): void
    {
        if ($this->currentPhase !== self::PHASE_STOPPED) {
            throw ProtocolError::invalidState(
                'initialize',
                $this->currentPhase,
                self::PHASE_STOPPED
            );
        }

        $this->setPhase(self::PHASE_INITIALIZING);

        try {
            $this->logger->info('Starting server initialization');

            // Execute initialization hooks
            $this->executeHooks('initialize');

            // Validate server configuration
            $this->validateConfiguration();

            // Initialize transport layer
            $this->initializeTransport();

            // Initialize router
            $this->initializeRouter();

            // Initialize capabilities
            $this->initializeCapabilities();

            ++$this->stats['initialization_count'];
            $this->healthy = true;
            $this->lastError = null;

            $this->logger->info('Server initialization completed successfully');
        } catch (Throwable $e) {
            $this->setPhase(self::PHASE_ERROR);
            $this->healthy = false;
            $this->lastError = [
                'phase' => 'initialize',
                'error' => $e->getMessage(),
                'time' => time(),
            ];
            ++$this->stats['error_count'];

            $this->logger->error('Server initialization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $errorData = new ErrorData(
                ErrorCodes::INTERNAL_ERROR,
                'Initialization failed: ' . $e->getMessage(),
                ['phase' => 'initialize', 'original_error' => $e->getMessage()]
            );
            throw new McpError($errorData);
        }
    }

    /**
     * Start the server and all its components.
     *
     * @throws McpError If startup fails
     */
    public function startup(): void
    {
        if ($this->currentPhase !== self::PHASE_INITIALIZING && $this->currentPhase !== self::PHASE_STOPPED) {
            throw ProtocolError::invalidState(
                'startup',
                $this->currentPhase,
                self::PHASE_INITIALIZING . ' or ' . self::PHASE_STOPPED
            );
        }

        // Initialize if not already done
        if ($this->currentPhase === self::PHASE_STOPPED) {
            $this->initialize();
        }

        $this->setPhase(self::PHASE_STARTING);

        try {
            $this->logger->info('Starting server');

            // Execute startup hooks
            $this->executeHooks('startup');

            // Start transport layer
            $transport = $this->server->getTransport();
            if ($transport) {
                $transport->start();
            }

            // Mark as running
            $this->setPhase(self::PHASE_RUNNING);
            ++$this->stats['startup_count'];
            $this->stats['last_startup_time'] = time();
            $this->healthy = true;

            $this->logger->info('Server started successfully', [
                'transport' => $transport ? get_class($transport) : 'none',
                'capabilities' => $this->server->getCapabilities()?->toArray() ?? [],
            ]);
        } catch (Throwable $e) {
            $this->setPhase(self::PHASE_ERROR);
            $this->healthy = false;
            $this->lastError = [
                'phase' => 'startup',
                'error' => $e->getMessage(),
                'time' => time(),
            ];
            ++$this->stats['error_count'];

            $this->logger->error('Server startup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $errorData = new ErrorData(
                ErrorCodes::INTERNAL_ERROR,
                'Startup failed: ' . $e->getMessage(),
                ['phase' => 'startup', 'original_error' => $e->getMessage()]
            );
            throw new McpError($errorData);
        }
    }

    /**
     * Perform graceful shutdown of the server.
     *
     * @param int $timeoutSeconds Maximum time to wait for graceful shutdown
     */
    public function shutdown(int $timeoutSeconds = 30): void
    {
        if ($this->currentPhase === self::PHASE_STOPPED) {
            return;
        }

        $this->setPhase(self::PHASE_STOPPING);
        $shutdownStart = time();

        try {
            $this->logger->info('Starting graceful shutdown', ['timeout' => $timeoutSeconds]);

            // Execute shutdown hooks
            $this->executeHooks('shutdown');

            // Stop transport layer with timeout
            $transport = $this->server->getTransport();
            if ($transport) {
                $this->stopTransportWithTimeout($transport, $timeoutSeconds);
            }

            // Clean up resources
            $this->cleanup();

            // Calculate uptime
            if ($this->stats['last_startup_time']) {
                $uptime = time() - $this->stats['last_startup_time'];
                $this->stats['total_uptime'] += $uptime;
            }

            $this->setPhase(self::PHASE_STOPPED);
            ++$this->stats['shutdown_count'];
            $this->stats['last_shutdown_time'] = time();

            $shutdownDuration = time() - $shutdownStart;
            $this->logger->info('Server shutdown completed', [
                'duration' => $shutdownDuration,
                'graceful' => $shutdownDuration <= $timeoutSeconds,
            ]);
        } catch (Throwable $e) {
            $this->setPhase(self::PHASE_ERROR);
            $this->healthy = false;
            $this->lastError = [
                'phase' => 'shutdown',
                'error' => $e->getMessage(),
                'time' => time(),
            ];
            ++$this->stats['error_count'];

            $this->logger->error('Server shutdown failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Restart the server (stop then start).
     *
     * @param int $timeoutSeconds Maximum time to wait for shutdown before restart
     * @throws McpError If restart fails
     */
    public function restart(int $timeoutSeconds = 30): void
    {
        $this->logger->info('Restarting server', ['timeout' => $timeoutSeconds]);

        try {
            // Shutdown first
            $this->shutdown($timeoutSeconds);

            // Wait a moment for cleanup
            usleep(100000); // 100ms

            // Start again
            $this->startup();

            ++$this->stats['restart_count'];
            $this->logger->info('Server restart completed successfully');
        } catch (Throwable $e) {
            $this->logger->error('Server restart failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $errorData = new ErrorData(
                ErrorCodes::INTERNAL_ERROR,
                'Restart failed: ' . $e->getMessage(),
                ['phase' => 'restart', 'original_error' => $e->getMessage()]
            );
            throw new McpError($errorData);
        }
    }

    /**
     * Register a lifecycle hook.
     *
     * @param string $phase The lifecycle phase (initialize, startup, shutdown)
     * @param callable $callback The callback to execute
     * @param int $priority Priority (higher numbers execute first)
     */
    public function registerHook(string $phase, callable $callback, int $priority = 0): void
    {
        if (! isset($this->hooks[$phase])) {
            throw ValidationError::invalidFieldValue(
                'phase',
                "Invalid lifecycle phase: {$phase}. Valid phases are: " . implode(', ', array_keys($this->hooks))
            );
        }

        if (! isset($this->hooks[$phase][$priority])) {
            $this->hooks[$phase][$priority] = [];
        }

        $this->hooks[$phase][$priority][] = $callback;

        // Sort by priority (highest first)
        krsort($this->hooks[$phase]);

        $this->logger->debug('Lifecycle hook registered', [
            'phase' => $phase,
            'priority' => $priority,
        ]);
    }

    /**
     * Get the current lifecycle phase.
     *
     * @return string Current phase
     */
    public function getCurrentPhase(): string
    {
        return $this->currentPhase;
    }

    /**
     * Check if the server is in a healthy state.
     *
     * @return bool True if healthy, false otherwise
     */
    public function isHealthy(): bool
    {
        return $this->healthy && $this->currentPhase !== self::PHASE_ERROR;
    }

    /**
     * Get lifecycle statistics.
     *
     * @return array<string, mixed> Statistics about lifecycle events
     */
    public function getStats(): array
    {
        $stats = $this->stats;
        $stats['current_phase'] = $this->currentPhase;
        $stats['healthy'] = $this->isHealthy();
        $stats['last_error'] = $this->lastError;

        if ($this->currentPhase === self::PHASE_RUNNING && $this->stats['last_startup_time']) {
            $stats['current_uptime'] = time() - $this->stats['last_startup_time'];
        }

        return $stats;
    }

    /**
     * Set the current lifecycle phase.
     *
     * @param string $phase The new phase
     */
    private function setPhase(string $phase): void
    {
        $oldPhase = $this->currentPhase;
        $this->currentPhase = $phase;

        $this->logger->debug('Lifecycle phase changed', [
            'from' => $oldPhase,
            'to' => $phase,
        ]);
    }

    /**
     * Execute hooks for a specific phase.
     *
     * @param string $phase The lifecycle phase
     */
    private function executeHooks(string $phase): void
    {
        if (! isset($this->hooks[$phase])) {
            return;
        }

        foreach ($this->hooks[$phase] as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                try {
                    $callback($this->server, $this);
                } catch (Throwable $e) {
                    $this->logger->warning('Lifecycle hook failed', [
                        'phase' => $phase,
                        'priority' => $priority,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Register default lifecycle hooks.
     */
    private function registerDefaultHooks(): void
    {
        // Default initialization hook
        $this->registerHook('initialize', function (McpServerInterface $server, LifecycleManagerInterface $lifecycle) {
            // Validate basic server state - getConfig() always returns a valid config
            $config = $server->getConfig();
            if (empty($config->getName())) {
                $errorData = new ErrorData(
                    ErrorCodes::VALIDATION_ERROR,
                    'Server configuration name is missing',
                    ['component' => 'LifecycleManager', 'phase' => 'initialize']
                );
                throw new McpError($errorData);
            }
        }, 1000);

        // Default startup hook
        $this->registerHook('startup', function (McpServerInterface $server, LifecycleManagerInterface $lifecycle) {
            // Log startup information
            $config = $server->getConfig();
            $this->logger->info('Server starting with configuration', [
                'name' => $config->getName(),
                'version' => $config->getVersion(),
            ]);
        }, 1000);

        // Default shutdown hook
        $this->registerHook('shutdown', function (McpServerInterface $server, LifecycleManagerInterface $lifecycle) {
            // Log shutdown information
            $this->logger->info('Server shutting down gracefully');
        }, 1000);
    }

    /**
     * Validate server configuration.
     *
     * @throws ValidationError If configuration is invalid
     */
    private function validateConfiguration(): void
    {
        $config = $this->server->getConfig();

        if (empty($config->getName())) {
            throw ValidationError::emptyField('name');
        }

        if (empty($config->getVersion())) {
            throw ValidationError::emptyField('version');
        }
    }

    /**
     * Initialize transport layer.
     *
     * @throws McpError If transport initialization fails
     */
    private function initializeTransport(): void
    {
        $transport = $this->server->getTransport();
        if (! $transport) {
            $this->logger->warning('No transport layer configured');
            return;
        }

        // Transport-specific initialization can be added here
        $this->logger->debug('Transport layer initialized', [
            'type' => get_class($transport),
        ]);
    }

    /**
     * Initialize request router.
     *
     * @throws McpError If router initialization fails
     */
    private function initializeRouter(): void
    {
        $router = $this->server->getRouter();
        if (! $router) {
            // Use default router
            $router = new DefaultRequestRouter($this->logger);
            $this->server->setRouter($router);
        }

        $this->logger->debug('Request router initialized', [
            'type' => get_class($router),
        ]);
    }

    /**
     * Initialize server capabilities.
     *
     * @throws McpError If capabilities initialization fails
     */
    private function initializeCapabilities(): void
    {
        $capabilities = $this->server->getCapabilities();
        if (! $capabilities) {
            // Use default capabilities
            $capabilities = new ServerCapabilities();
            $this->server->setCapabilities($capabilities);
        }

        $this->logger->debug('Server capabilities initialized', [
            'capabilities' => $capabilities->toArray(),
        ]);
    }

    /**
     * Stop transport with timeout.
     *
     * @param TransportInterface $transport The transport to stop
     * @param int $timeoutSeconds Maximum time to wait
     */
    private function stopTransportWithTimeout(TransportInterface $transport, int $timeoutSeconds): void
    {
        $startTime = time();

        try {
            $transport->stop();

            // Wait for transport to stop gracefully
            while ($transport->isRunning() && (time() - $startTime) < $timeoutSeconds) {
                usleep(100000); // 100ms
            }

            if ($transport->isRunning()) {
                $this->logger->warning('Transport did not stop gracefully within timeout', [
                    'timeout' => $timeoutSeconds,
                    'type' => get_class($transport),
                ]);
            }
        } catch (Throwable $e) {
            $this->logger->error('Error stopping transport', [
                'error' => $e->getMessage(),
                'type' => get_class($transport),
            ]);
        }
    }

    /**
     * Clean up resources.
     */
    private function cleanup(): void
    {
        // Clear any cached data
        // Close file handles
        // Release memory
        $this->logger->debug('Cleanup completed');
    }
}
