<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client\Transport\Stdio;

use Dtyq\PhpMcp\Client\Configuration\StdioConfig;
use Dtyq\PhpMcp\Shared\Exceptions\TransportError;
use Exception;

/**
 * Process manager for stdio transport.
 *
 * This class handles the lifecycle of the child process including
 * creation, monitoring, and termination with proper resource cleanup.
 */
class ProcessManager
{
    /** @var array<string> Command to execute */
    private array $command;

    /** @var StdioConfig Stdio configuration */
    private StdioConfig $config;

    /** @var null|resource Process resource */
    private $process;

    /** @var null|array<null|resource> Pipe resources [stdin, stdout, stderr] */
    private ?array $pipes = null;

    /** @var bool Whether the process is running */
    private bool $running = false;

    /** @var null|int Process ID */
    private ?int $processId = null;

    /**
     * @param array<string> $command Command and arguments to execute
     * @param StdioConfig $config Stdio configuration
     */
    public function __construct(array $command, StdioConfig $config)
    {
        $this->command = $command;
        $this->config = $config;
    }

    /**
     * Destructor to ensure cleanup.
     */
    public function __destruct()
    {
        if ($this->isRunning()) {
            try {
                $this->stop();
            } catch (Exception $e) {
                // Ignore errors during cleanup in destructor
            }
        }
    }

    /**
     * Start the process.
     *
     * @throws TransportError If process cannot be started
     */
    public function start(): void
    {
        if ($this->isRunning()) {
            throw new TransportError('Process is already running');
        }

        try {
            // Build command string
            $commandString = $this->buildCommand();

            // Configure pipe descriptors
            $descriptors = [
                0 => ['pipe', 'r'], // stdin
                1 => ['pipe', 'w'], // stdout
                2 => ['pipe', 'w'], // stderr
            ];

            // Build environment
            $environment = $this->buildEnvironment();

            // Start process
            $process = proc_open(
                $commandString,
                $descriptors,
                $this->pipes,
                null, // cwd
                $environment
            );

            if (! is_resource($process)) {
                throw new TransportError('Failed to start process: proc_open returned false');
            }

            $this->process = $process;

            // Get process status
            $status = proc_get_status($this->process);
            if (! $status['running']) {
                $this->cleanup();
                throw new TransportError('Process failed to start');
            }

            $this->processId = $status['pid'];
            $this->running = true;

            // Wait a moment and check again to catch immediate failures
            usleep(50000); // 50ms
            $status = proc_get_status($this->process);
            if (! $status['running']) {
                $exitCode = $status['exitcode'];
                $this->cleanup();

                if ($exitCode === 127) {
                    throw new TransportError('Command not found: ' . $this->command[0]);
                }

                throw new TransportError('Process failed to start (exit code: ' . $exitCode . ')');
            }

            // Configure streams as non-blocking
            $this->configureStreams();
        } catch (Exception $e) {
            $this->cleanup();
            throw new TransportError('Failed to start process: ' . $e->getMessage());
        }
    }

    /**
     * Stop the process gracefully.
     *
     * @throws TransportError If process cannot be stopped
     */
    public function stop(): void
    {
        if (! $this->isRunning()) {
            return; // Already stopped
        }

        try {
            // Try graceful termination first
            $this->terminateGracefully();

            // Wait for process to exit
            $timeout = microtime(true) + $this->config->getShutdownTimeout();
            while ($this->isRunning() && microtime(true) < $timeout) {
                usleep(100000); // 100ms
            }

            // Force kill if still running
            if ($this->isRunning()) {
                $this->forceKill();
            }
        } finally {
            $this->cleanup();
        }
    }

    /**
     * Check if the process is running.
     *
     * @return bool True if process is running
     */
    public function isRunning(): bool
    {
        if (! $this->running || ! is_resource($this->process)) {
            return false;
        }

        $status = proc_get_status($this->process);
        $this->running = $status['running'];

        return $this->running;
    }

    /**
     * Get the stdin pipe.
     *
     * @return resource The stdin pipe
     * @throws TransportError If process not running or pipe unavailable
     */
    public function getStdin()
    {
        $this->ensureRunning();

        if ($this->pipes === null || ! isset($this->pipes[0]) || ! is_resource($this->pipes[0])) {
            throw new TransportError('Stdin pipe is not available');
        }

        return $this->pipes[0];
    }

    /**
     * Get the stdout pipe.
     *
     * @return resource The stdout pipe
     * @throws TransportError If process not running or pipe unavailable
     */
    public function getStdout()
    {
        $this->ensureRunning();

        if ($this->pipes === null || ! isset($this->pipes[1]) || ! is_resource($this->pipes[1])) {
            throw new TransportError('Stdout pipe is not available');
        }

        return $this->pipes[1];
    }

    /**
     * Get the stderr pipe.
     *
     * @return resource The stderr pipe
     * @throws TransportError If process not running or pipe unavailable
     */
    public function getStderr()
    {
        $this->ensureRunning();

        if ($this->pipes === null || ! isset($this->pipes[2]) || ! is_resource($this->pipes[2])) {
            throw new TransportError('Stderr pipe is not available');
        }

        return $this->pipes[2];
    }

    /**
     * Get the process ID.
     *
     * @return null|int The process ID or null if not running
     */
    public function getProcessId(): ?int
    {
        return $this->processId;
    }

    /**
     * Build the command string for execution.
     *
     * @return string The command string
     */
    private function buildCommand(): string
    {
        if (empty($this->command)) {
            throw new TransportError('Command cannot be empty');
        }

        // Escape command parts for shell execution
        $escapedParts = array_map('escapeshellarg', $this->command);
        return implode(' ', $escapedParts);
    }

    /**
     * Build the environment array.
     *
     * @return null|array<string, string> Environment variables
     */
    private function buildEnvironment(): ?array
    {
        if (! $this->config->shouldInheritEnvironment()) {
            return []; // Empty environment
        }

        return null; // Inherit from parent process
    }

    /**
     * Configure streams as non-blocking.
     */
    private function configureStreams(): void
    {
        if ($this->pipes === null) {
            return;
        }

        // Set stdout and stderr as non-blocking
        if (isset($this->pipes[1])) {
            stream_set_blocking($this->pipes[1], false);
        }

        if (isset($this->pipes[2])) {
            stream_set_blocking($this->pipes[2], false);
        }
    }

    /**
     * Terminate the process gracefully.
     */
    private function terminateGracefully(): void
    {
        if (! is_resource($this->process)) {
            return;
        }

        // Close stdin to signal termination
        if (isset($this->pipes[0]) && is_resource($this->pipes[0])) {
            fclose($this->pipes[0]);
            $this->pipes[0] = null;
        }

        // Send SIGTERM on Unix systems
        if (function_exists('proc_terminate')) {
            proc_terminate($this->process, SIGTERM);
        }
    }

    /**
     * Force kill the process.
     */
    private function forceKill(): void
    {
        if (! is_resource($this->process)) {
            return;
        }

        // Send SIGKILL on Unix systems
        if (function_exists('proc_terminate')) {
            proc_terminate($this->process, SIGKILL);
        }
    }

    /**
     * Cleanup all resources.
     */
    private function cleanup(): void
    {
        // Close pipes
        if ($this->pipes !== null) {
            foreach ($this->pipes as $index => $pipe) {
                if (is_resource($pipe)) {
                    fclose($pipe);
                }
                $this->pipes[$index] = null;
            }
            $this->pipes = null;
        }

        // Close process
        if (is_resource($this->process)) {
            proc_close($this->process);
            $this->process = null;
        }

        $this->running = false;
        $this->processId = null;
    }

    /**
     * Ensure the process is running.
     *
     * @throws TransportError If process is not running
     */
    private function ensureRunning(): void
    {
        if (! $this->isRunning()) {
            throw new TransportError('Process is not running');
        }
    }
}
