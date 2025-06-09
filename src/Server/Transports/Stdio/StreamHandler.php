<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Transports\Stdio;

use Dtyq\PhpMcp\Shared\Exceptions\TransportError;
use Dtyq\PhpMcp\Shared\Utilities\JsonUtils;
use Exception;

/**
 * Handles stdin/stdout stream operations for stdio transport.
 *
 * This class manages the low-level stream operations required for
 * MCP stdio transport, ensuring compliance with the specification
 * requirements for message formatting and stream handling.
 */
class StreamHandler
{
    /** @var resource */
    private $stdin;

    /** @var resource */
    private $stdout;

    /** @var resource */
    private $stderr;

    private bool $initialized = false;

    public function __construct()
    {
        $this->initializeStreams();
    }

    /**
     * Read a line from stdin.
     *
     * According to MCP specification, messages are delimited by newlines
     * and must not contain embedded newlines.
     *
     * @return null|string The line read, or null if EOF reached
     * @throws TransportError If read operation fails
     */
    public function readLine(): ?string
    {
        if (! $this->initialized) {
            throw TransportError::stdioError('Stream handler not initialized');
        }

        if ($this->isEof()) {
            return null;
        }

        $line = fgets($this->stdin);

        if ($line === false) {
            throw TransportError::stdioError('Failed to read from stdin');
        }

        // Remove trailing newline as per MCP specification
        $line = rtrim($line, "\r\n");

        // Validate that the message doesn't contain embedded newlines
        if (! $this->validateMcpMessage($line)) {
            throw TransportError::stdioError('Message contains embedded newlines');
        }

        return $line;
    }

    /**
     * Write a line to stdout.
     *
     * According to MCP specification, only valid MCP messages should be
     * written to stdout, and they must be terminated with a newline.
     *
     * @param string $data The data to write
     * @throws TransportError If write operation fails
     */
    public function writeLine(string $data): void
    {
        if (! $this->initialized) {
            throw TransportError::stdioError('Stream handler not initialized');
        }

        // Validate that this is a valid MCP message
        if (! $this->validateMcpMessage($data)) {
            throw TransportError::stdioError('Attempted to write invalid MCP message to stdout');
        }

        // Write with newline terminator
        $written = fwrite($this->stdout, $data . "\n");

        if ($written === false) {
            throw TransportError::stdioError('Failed to write to stdout');
        }

        // Ensure data is flushed immediately
        if (! fflush($this->stdout)) {
            throw TransportError::stdioError('Failed to flush stdout');
        }
    }

    /**
     * Write an error message to stderr.
     *
     * According to MCP specification, servers may write UTF-8 strings
     * to stderr for logging purposes.
     *
     * @param string $error The error message to write
     */
    public function writeError(string $error): void
    {
        if (! $this->initialized) {
            return; // Fail silently for error logging
        }

        // Ensure UTF-8 encoding
        if (! mb_check_encoding($error, 'UTF-8')) {
            $error = mb_convert_encoding($error, 'UTF-8', 'auto');
        }

        // Write to stderr with timestamp
        $timestamp = date('Y-m-d H:i:s');
        $message = "[{$timestamp}] {$error}\n";

        fwrite($this->stderr, $message);
        fflush($this->stderr);
    }

    /**
     * Check if stdin has reached EOF.
     *
     * @return bool True if EOF reached, false otherwise
     */
    public function isEof(): bool
    {
        if (! $this->initialized) {
            return true;
        }

        return feof($this->stdin);
    }

    /**
     * Validate that a message is a valid MCP message.
     *
     * According to MCP specification, messages must not contain
     * embedded newlines and should be valid UTF-8.
     *
     * @param string $message The message to validate
     * @return bool True if valid, false otherwise
     */
    public function validateMcpMessage(string $message): bool
    {
        // Check for embedded newlines (MCP requirement)
        if (strpos($message, "\n") !== false || strpos($message, "\r") !== false) {
            return false;
        }

        // Check UTF-8 encoding (MCP requirement)
        if (! mb_check_encoding($message, 'UTF-8')) {
            return false;
        }

        // Basic JSON validation (should be JSON-RPC)
        if (empty($message)) {
            return false;
        }

        try {
            JsonUtils::decode($message, true);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Close all streams and cleanup resources.
     */
    public function close(): void
    {
        // Note: We don't actually close STDIN/STDOUT/STDERR as they
        // are managed by the system, but we mark as uninitialized
        $this->initialized = false;
    }

    /**
     * Check if the stream handler is initialized.
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * Get the stdin resource.
     *
     * @return resource
     */
    public function getStdin()
    {
        return $this->stdin;
    }

    /**
     * Get the stdout resource.
     *
     * @return resource
     */
    public function getStdout()
    {
        return $this->stdout;
    }

    /**
     * Get the stderr resource.
     *
     * @return resource
     */
    public function getStderr()
    {
        return $this->stderr;
    }

    /**
     * Initialize the standard streams.
     *
     * @throws TransportError If streams cannot be initialized
     */
    private function initializeStreams(): void
    {
        $this->stdin = STDIN;
        $this->stdout = STDOUT;
        $this->stderr = STDERR;

        // Verify streams are available
        if (! is_resource($this->stdin)) {
            throw TransportError::stdioError('STDIN is not available');
        }

        if (! is_resource($this->stdout)) {
            throw TransportError::stdioError('STDOUT is not available');
        }

        if (! is_resource($this->stderr)) {
            throw TransportError::stdioError('STDERR is not available');
        }

        $this->initialized = true;
    }
}
