<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Core;

use Dtyq\PhpMcp\Shared\Exceptions\McpError;

/**
 * Interface for server lifecycle management.
 *
 * This interface defines the contract for managing server lifecycle events,
 * initialization sequences, and graceful shutdown procedures.
 */
interface LifecycleManagerInterface
{
    /**
     * Initialize the server components in the correct order.
     *
     * @throws McpError If initialization fails
     */
    public function initialize(): void;

    /**
     * Start the server and all its components.
     *
     * @throws McpError If startup fails
     */
    public function startup(): void;

    /**
     * Perform graceful shutdown of the server.
     *
     * @param int $timeoutSeconds Maximum time to wait for graceful shutdown
     */
    public function shutdown(int $timeoutSeconds = 30): void;

    /**
     * Restart the server (stop then start).
     *
     * @param int $timeoutSeconds Maximum time to wait for shutdown before restart
     * @throws McpError If restart fails
     */
    public function restart(int $timeoutSeconds = 30): void;

    /**
     * Register a lifecycle hook.
     *
     * @param string $phase The lifecycle phase (initialize, startup, shutdown)
     * @param callable $callback The callback to execute
     * @param int $priority Priority (higher numbers execute first)
     */
    public function registerHook(string $phase, callable $callback, int $priority = 0): void;

    /**
     * Get the current lifecycle phase.
     *
     * @return string Current phase (stopped, initializing, starting, running, stopping)
     */
    public function getCurrentPhase(): string;

    /**
     * Check if the server is in a healthy state.
     *
     * @return bool True if healthy, false otherwise
     */
    public function isHealthy(): bool;

    /**
     * Get lifecycle statistics.
     *
     * @return array<string, mixed> Statistics about lifecycle events
     */
    public function getStats(): array;
}
