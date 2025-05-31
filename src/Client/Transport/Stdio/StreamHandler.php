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
 * Stream handler for stdio transport.
 *
 * This class manages stream I/O operations including reading and writing
 * messages with proper buffering, timeout handling, and error management.
 */
class StreamHandler
{
    /** @var ProcessManager Process manager instance */
    private ProcessManager $processManager;

    /** @var StdioConfig Stdio configuration */
    private StdioConfig $config;

    /** @var bool Whether the streams are initialized */
    private bool $initialized = false;

    /** @var string Buffer for incomplete messages */
    private string $readBuffer = '';

    /** @var array<string> Error log from stderr */
    private array $errorLog = [];

    /**
     * @param ProcessManager $processManager Process manager instance
     * @param StdioConfig $config Stdio configuration
     */
    public function __construct(ProcessManager $processManager, StdioConfig $config)
    {
        $this->processManager = $processManager;
        $this->config = $config;
    }

    /**
     * Initialize the stream handler.
     *
     * @throws TransportError If initialization fails
     */
    public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        if (! $this->processManager->isRunning()) {
            throw new TransportError('Cannot initialize streams: process not running');
        }

        // Clear buffers
        $this->readBuffer = '';
        $this->errorLog = [];

        $this->initialized = true;
    }

    /**
     * Read a complete line from stdout.
     *
     * This method handles buffering and ensures complete messages are returned.
     *
     * @param null|float $timeout Timeout in seconds (null for default)
     * @return null|string The complete line or null on timeout/EOF
     * @throws TransportError If read operation fails
     */
    public function readLine(?float $timeout = null): ?string
    {
        $this->ensureInitialized();

        $timeout = $timeout ?? $this->config->getReadTimeout();
        $endTime = microtime(true) + $timeout;

        while (microtime(true) < $endTime) {
            // Check for complete line in buffer
            $lineEnd = strpos($this->readBuffer, "\n");
            if ($lineEnd !== false) {
                $line = substr($this->readBuffer, 0, $lineEnd);
                $this->readBuffer = substr($this->readBuffer, $lineEnd + 1);
                return rtrim($line, "\r\n");
            }

            // Read more data from stdout
            $remainingTimeout = $endTime - microtime(true);
            if ($remainingTimeout <= 0) {
                break;
            }

            $data = $this->readFromStream($remainingTimeout);
            if ($data === null) {
                continue; // Timeout on this read, try again
            }

            if ($data === '') {
                // EOF reached
                if (! empty($this->readBuffer)) {
                    // Return remaining buffer content
                    $line = $this->readBuffer;
                    $this->readBuffer = '';
                    return rtrim($line, "\r\n");
                }
                return null;
            }

            $this->readBuffer .= $data;
        }

        // Timeout exceeded
        return null;
    }

    /**
     * Write a line to stdin.
     *
     * @param string $message The message to write
     * @throws TransportError If write operation fails
     */
    public function writeLine(string $message): void
    {
        $this->ensureInitialized();

        try {
            $stdin = $this->processManager->getStdin();

            // Add newline if not present
            if (! str_ends_with($message, "\n")) {
                $message .= "\n";
            }

            $timeout = microtime(true) + $this->config->getWriteTimeout();
            $bytesWritten = 0;
            $totalBytes = strlen($message);

            while ($bytesWritten < $totalBytes && microtime(true) < $timeout) {
                $result = fwrite($stdin, substr($message, $bytesWritten));

                if ($result === false) {
                    throw new TransportError('Failed to write to stdin');
                }

                $bytesWritten += $result;

                if ($bytesWritten < $totalBytes) {
                    // Brief pause before retry
                    usleep(1000); // 1ms
                }
            }

            if ($bytesWritten < $totalBytes) {
                throw new TransportError('Write timeout: only wrote ' . $bytesWritten . ' of ' . $totalBytes . ' bytes');
            }

            // Flush the stream
            if (! fflush($stdin)) {
                throw new TransportError('Failed to flush stdin');
            }
        } catch (Exception $e) {
            throw new TransportError('Write operation failed: ' . $e->getMessage());
        }
    }

    /**
     * Write an error message to stderr (if capturing is enabled).
     *
     * @param string $message The error message
     */
    public function writeError(string $message): void
    {
        if (! $this->config->shouldCaptureStderr()) {
            return;
        }

        $this->errorLog[] = '[' . date('Y-m-d H:i:s') . '] ' . $message;

        // Keep error log size manageable
        if (count($this->errorLog) > 1000) {
            $this->errorLog = array_slice($this->errorLog, -500);
        }
    }

    /**
     * Get error log entries.
     *
     * @return array<string> Array of error log entries
     */
    public function getErrorLog(): array
    {
        return $this->errorLog;
    }

    /**
     * Read any available stderr output.
     *
     * @return string Available stderr content
     */
    public function readStderr(): string
    {
        if (! $this->config->shouldCaptureStderr() || ! $this->initialized) {
            return '';
        }

        try {
            $stderr = $this->processManager->getStderr();
            $content = '';

            while (($data = fread($stderr, max(1, $this->config->getBufferSize()))) !== false && $data !== '') {
                $content .= $data;
            }

            if (! empty($content)) {
                $this->writeError('STDERR: ' . trim($content));
            }

            return $content;
        } catch (Exception $e) {
            $this->writeError('Failed to read stderr: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Close all streams and cleanup.
     */
    public function close(): void
    {
        if (! $this->initialized) {
            return;
        }

        // Read any remaining stderr
        $this->readStderr();

        $this->initialized = false;
        $this->readBuffer = '';
    }

    /**
     * Check if the handler is initialized.
     *
     * @return bool True if initialized
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * Get the current read buffer content.
     *
     * @return string Current buffer content
     */
    public function getBufferContent(): string
    {
        return $this->readBuffer;
    }

    /**
     * Clear the read buffer.
     */
    public function clearBuffer(): void
    {
        $this->readBuffer = '';
    }

    /**
     * Read data from stdout stream with timeout.
     *
     * @param float $timeout Timeout in seconds
     * @return null|string Data read or null on timeout/error
     */
    private function readFromStream(float $timeout): ?string
    {
        try {
            $stdout = $this->processManager->getStdout();

            // Use stream_select for timeout handling
            $read = [$stdout];
            $write = null;
            $except = null;
            $timeoutSec = (int) floor($timeout);
            $timeoutMicro = (int) (($timeout - $timeoutSec) * 1000000);

            $result = stream_select($read, $write, $except, $timeoutSec, $timeoutMicro);

            if ($result === false) {
                throw new TransportError('stream_select failed');
            }

            if ($result === 0) {
                return null; // Timeout
            }

            // Read available data
            $data = fread($stdout, max(1, $this->config->getBufferSize()));

            if ($data === false) {
                throw new TransportError('Failed to read from stdout');
            }

            return $data;
        } catch (Exception $e) {
            throw new TransportError('Stream read failed: ' . $e->getMessage());
        }
    }

    /**
     * Ensure the handler is initialized.
     *
     * @throws TransportError If not initialized
     */
    private function ensureInitialized(): void
    {
        if (! $this->initialized) {
            throw new TransportError('Stream handler not initialized');
        }

        if (! $this->processManager->isRunning()) {
            throw new TransportError('Process is not running');
        }
    }
}
