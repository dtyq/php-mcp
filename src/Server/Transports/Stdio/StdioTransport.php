<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Transports\Stdio;

use Dtyq\PhpMcp\Server\Transports\Core\AbstractTransport;
use Dtyq\PhpMcp\Server\Transports\Core\TransportMetadata;
use Dtyq\PhpMcp\Shared\Exceptions\TransportError;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Exception;
use Throwable;

/**
 * Stdio transport implementation for MCP.
 *
 * This transport implements the stdio mechanism defined in MCP 2025-03-26
 * specification, handling communication through standard input and output
 * streams with proper message validation and error handling.
 */
class StdioTransport extends AbstractTransport
{
    private StreamHandler $streamHandler;

    private bool $shouldStop = false;

    /**
     * @var array<string, mixed>
     */
    private array $config;

    public function __construct(Application $app, TransportMetadata $transportMetadata)
    {
        parent::__construct($app, $transportMetadata);

        $this->streamHandler = new StreamHandler();
        $this->config = $app->getConfig()->get('transports.stdio', [
            'enabled' => true,
            'buffer_size' => 8192,
            'timeout' => 30,
            'validate_messages' => true,
        ]);
    }

    public function start(): void
    {
        if ($this->running) {
            throw TransportError::alreadyStarted('stdio');
        }

        if (! $this->config['enabled']) {
            throw TransportError::stdioError('Stdio transport is disabled in configuration');
        }

        $this->logger->info('Starting stdio transport');

        try {
            $this->running = true;
            $this->shouldStop = false;

            // Start the main processing loop
            $this->processStdinLoop();
        } catch (Exception $e) {
            $this->running = false;
            $this->logger->error('Stdio transport failed to start', [
                'error' => $e->getMessage(),
            ]);
            throw TransportError::stdioError('Failed to start stdio transport: ' . $e->getMessage());
        }
    }

    public function stop(): void
    {
        if (! $this->running) {
            throw TransportError::notStarted('stdio');
        }

        $this->logger->info('Stopping stdio transport');

        $this->shouldStop = true;
        $this->running = false;

        // Cleanup resources
        $this->streamHandler->close();

        $this->logger->info('Stdio transport stopped');
    }

    public function sendMessage(string $message): void
    {
        if (! $this->running) {
            throw TransportError::notStarted('stdio');
        }

        try {
            // Validate message before sending
            if ($this->config['validate_messages'] && ! $this->validateNoEmbeddedNewlines($message)) {
                throw TransportError::stdioError('Message contains embedded newlines');
            }

            // Send through stream handler
            $this->streamHandler->writeLine($message);

            $this->logger->debug('Message sent via stdio', [
                'message_length' => strlen($message),
                'message' => $message,
            ]);
        } catch (Exception $e) {
            $this->logger->error('Failed to send message via stdio', [
                'error' => $e->getMessage(),
                'message' => $message,
            ]);
            throw $e;
        }
    }

    /**
     * Get the stream handler instance.
     */
    public function getStreamHandler(): StreamHandler
    {
        return $this->streamHandler;
    }

    /**
     * Check if the transport should stop.
     */
    public function shouldStop(): bool
    {
        return $this->shouldStop;
    }

    /**
     * Get the transport configuration.
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Handle subprocess lifecycle events.
     *
     * This method can be called to handle events related to the
     * subprocess lifecycle, such as signals or cleanup operations.
     */
    public function handleSubprocessLifecycle(): void
    {
        // Register signal handlers for graceful shutdown
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, [$this, 'handleShutdownSignal']);
            pcntl_signal(SIGINT, [$this, 'handleShutdownSignal']);
            pcntl_signal(SIGHUP, [$this, 'handleShutdownSignal']);

            // Enable signal handling
            pcntl_async_signals(true);

            $this->logger->debug('Signal handlers registered');
        } else {
            $this->logger->warning('PCNTL extension not available, signal handling disabled');
        }

        // Setup error handlers
        set_error_handler([$this, 'handlePhpError']);
        set_exception_handler([$this, 'handleUncaughtException']);

        // Setup shutdown handler
        register_shutdown_function([$this, 'handleShutdown']);
    }

    /**
     * Handle shutdown signals.
     *
     * @param int $signal The signal number
     */
    public function handleShutdownSignal(int $signal): void
    {
        $signalNames = [
            SIGTERM => 'SIGTERM',
            SIGINT => 'SIGINT',
            SIGHUP => 'SIGHUP',
        ];

        $signalName = $signalNames[$signal] ?? "Signal {$signal}";

        $this->logger->info('Received shutdown signal', [
            'signal' => $signal,
            'signal_name' => $signalName,
        ]);

        try {
            $this->stop();
        } catch (Exception $e) {
            $this->logger->error('Error during signal shutdown', [
                'error' => $e->getMessage(),
            ]);
        }

        exit(0);
    }

    /**
     * Handle PHP errors.
     *
     * @param int $severity The error severity
     * @param string $message The error message
     * @param string $file The file where the error occurred
     * @param int $line The line number where the error occurred
     */
    public function handlePhpError(int $severity, string $message, string $file, int $line): bool
    {
        $this->logger->error('PHP error in stdio transport', [
            'severity' => $severity,
            'message' => $message,
            'file' => $file,
            'line' => $line,
        ]);

        // Don't execute PHP's internal error handler
        return true;
    }

    /**
     * Handle uncaught exceptions.
     *
     * @param Throwable $exception The uncaught exception
     */
    public function handleUncaughtException(Throwable $exception): void
    {
        $this->logger->critical('Uncaught exception in stdio transport', [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);

        try {
            $this->stop();
        } catch (Exception $e) {
            $this->logger->error('Error during exception shutdown', [
                'error' => $e->getMessage(),
            ]);
        }

        exit(1);
    }

    /**
     * Handle script shutdown.
     */
    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->logger->critical('Fatal error in stdio transport', [
                'type' => $error['type'],
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
            ]);
        }

        try {
            if ($this->running) {
                $this->stop();
            }
        } catch (Exception $e) {
            $this->logger->error('Error during shutdown cleanup', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function getTransportType(): string
    {
        return 'stdio';
    }

    /**
     * Process the main stdin reading loop.
     *
     * This method implements the core stdio transport logic, reading
     * messages from stdin and processing them according to MCP specification.
     */
    private function processStdinLoop(): void
    {
        $this->logger->debug('Starting stdin processing loop');

        while (! $this->shouldStop) {
            try {
                // Check for EOF before attempting to read
                if ($this->streamHandler->isEof()) {
                    $this->logger->info('EOF reached on stdin, stopping transport');
                    break;
                }

                // Read a line from stdin
                $line = $this->streamHandler->readLine();

                if ($line === null) {
                    // EOF reached during read
                    $this->logger->info('EOF reached on stdin, stopping transport');
                    break;
                }

                if (empty($line)) {
                    // Skip empty lines
                    continue;
                }

                $this->logger->debug('Received message from stdin', [
                    'message_length' => strlen($line),
                    'message' => $line,
                ]);

                // Process the message
                $response = $this->handleMessage($line);

                // Send response if needed
                if ($response !== null) {
                    $this->sendMessage($response);
                }

                // For piped input, we might want to exit after processing one message
                // Check if stdin is closed after processing
                if ($this->streamHandler->isEof()) {
                    $this->logger->info('EOF reached after processing message, stopping transport');
                    break;
                }
            } catch (TransportError $e) {
                // Check if this is an EOF-related error
                if (strpos($e->getMessage(), 'Failed to read from stdin') !== false) {
                    $this->logger->info('EOF reached on stdin (via exception), stopping transport');
                    break;
                }

                // Log transport errors and continue
                $this->logger->error('Transport error in stdin loop', [
                    'error' => $e->getMessage(),
                ]);

                // Write error to stderr for client visibility
                $this->streamHandler->writeError('Transport error: ' . $e->getMessage());
            } catch (Exception $e) {
                // Log unexpected errors and continue
                $this->logger->error('Unexpected error in stdin loop', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Write error to stderr
                $this->streamHandler->writeError('Unexpected error: ' . $e->getMessage());
            }
        }

        $this->logger->debug('Stdin processing loop ended');
    }

    /**
     * Validate that a message doesn't contain embedded newlines.
     *
     * According to MCP specification, messages must not contain
     * embedded newlines.
     *
     * @param string $message The message to validate
     * @return bool True if valid, false otherwise
     */
    private function validateNoEmbeddedNewlines(string $message): bool
    {
        return strpos($message, "\n") === false && strpos($message, "\r") === false;
    }
}
