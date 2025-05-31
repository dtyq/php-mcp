<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client\Transport\Stdio;

use Dtyq\PhpMcp\Client\Configuration\StdioConfig;
use Dtyq\PhpMcp\Shared\Exceptions\ProtocolError;
use Dtyq\PhpMcp\Shared\Utilities\JsonUtils;
use Exception;

/**
 * Message parser for stdio transport.
 *
 * This class handles parsing and validation of JSON-RPC messages
 * including format validation, type detection, and error recovery.
 */
class MessageParser
{
    /** @var StdioConfig Stdio configuration */
    private StdioConfig $config;

    /** @var int Number of parsing errors encountered */
    private int $parseErrorCount = 0;

    /** @var array<array<string, string>> Recent parsing errors for debugging */
    private array $recentErrors = [];

    /**
     * @param StdioConfig $config Stdio configuration
     */
    public function __construct(StdioConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Parse a JSON-RPC message from raw string.
     *
     * @param string $message Raw message string
     * @return array<string, mixed> Parsed message data
     * @throws ProtocolError If parsing fails or message is invalid
     */
    public function parseMessage(string $message): array
    {
        $message = trim($message);

        if (empty($message)) {
            throw new ProtocolError('Empty message received');
        }

        try {
            // Parse JSON
            $data = JsonUtils::decode($message);

            if (! is_array($data)) {
                throw new ProtocolError('Message must be a JSON object');
            }

            // Validate if enabled
            if ($this->config->shouldValidateMessages()) {
                $this->validateJsonRpcStructure($data);
            }

            $this->parseErrorCount = 0; // Reset on successful parse
            return $data;
        } catch (Exception $e) {
            $this->recordParseError($e->getMessage(), $message);
            throw new ProtocolError('Failed to parse message: ' . $e->getMessage());
        }
    }

    /**
     * Detect the type of a JSON-RPC message.
     *
     * @param array<string, mixed> $data Parsed message data
     * @return string Message type: 'request', 'response', 'notification', or 'unknown'
     */
    public function detectMessageType(array $data): string
    {
        // Response: has 'id' and either 'result' or 'error'
        if (isset($data['id']) && (isset($data['result']) || isset($data['error']))) {
            return 'response';
        }

        // Request: has 'method' and 'id'
        if (isset($data['method'], $data['id'])) {
            return 'request';
        }

        // Notification: has 'method' but no 'id'
        if (isset($data['method']) && ! isset($data['id'])) {
            return 'notification';
        }

        return 'unknown';
    }

    /**
     * Parse multiple messages from a batch string.
     *
     * This method handles cases where multiple JSON-RPC messages
     * are concatenated or sent as a batch.
     *
     * @param string $input Raw input containing multiple messages
     * @return array<array<string, mixed>> Array of parsed messages
     * @throws ProtocolError If any message fails to parse
     */
    public function parseBatch(string $input): array
    {
        $input = trim($input);
        $messages = [];

        // Try to parse as single JSON first
        try {
            $data = JsonUtils::decode($input);

            // Check if it's an array (batch)
            if (is_array($data) && isset($data[0])) {
                foreach ($data as $messageData) {
                    if (is_array($messageData)) {
                        if ($this->config->shouldValidateMessages()) {
                            $this->validateJsonRpcStructure($messageData);
                        }
                        $messages[] = $messageData;
                    }
                }
                return $messages;
            }
            // Single message
            return [$this->parseMessage($input)];
        } catch (Exception $e) {
            // Fall back to line-by-line parsing
            return $this->parseLineByLine($input);
        }
    }

    /**
     * Validate JSON-RPC message structure.
     *
     * @param array<string, mixed> $data Message data to validate
     * @throws ProtocolError If message structure is invalid
     */
    public function validateJsonRpcStructure(array $data): void
    {
        // Check JSON-RPC version
        if (! isset($data['jsonrpc']) || $data['jsonrpc'] !== '2.0') {
            throw new ProtocolError('Invalid or missing JSON-RPC version');
        }

        $messageType = $this->detectMessageType($data);

        switch ($messageType) {
            case 'request':
                $this->validateRequest($data);
                break;
            case 'response':
                $this->validateResponse($data);
                break;
            case 'notification':
                $this->validateNotification($data);
                break;
            default:
                throw new ProtocolError('Unknown message type');
        }
    }

    /**
     * Check if a message is a valid MCP message.
     *
     * @param array<string, mixed> $data Message data
     * @return bool True if valid MCP message
     */
    public function isValidMcpMessage(array $data): bool
    {
        try {
            $this->validateJsonRpcStructure($data);

            // Additional MCP-specific validation could go here
            // For now, any valid JSON-RPC message is considered valid MCP

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get parsing statistics.
     *
     * @return array<string, mixed> Statistics about parsing operations
     */
    public function getStats(): array
    {
        return [
            'parseErrorCount' => $this->parseErrorCount,
            'recentErrors' => $this->recentErrors,
            'validationEnabled' => $this->config->shouldValidateMessages(),
        ];
    }

    /**
     * Reset parsing statistics.
     */
    public function resetStats(): void
    {
        $this->parseErrorCount = 0;
        $this->recentErrors = [];
    }

    /**
     * Parse input line by line for error recovery.
     *
     * @param string $input Raw input
     * @return array<array<string, mixed>> Array of successfully parsed messages
     */
    private function parseLineByLine(string $input): array
    {
        $lines = explode("\n", $input);
        $messages = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            try {
                $messages[] = $this->parseMessage($line);
            } catch (Exception $e) {
                // Log error but continue parsing other lines
                $this->recordParseError($e->getMessage(), $line);
            }
        }

        return $messages;
    }

    /**
     * Validate a JSON-RPC request.
     *
     * @param array<string, mixed> $data Request data
     * @throws ProtocolError If request is invalid
     */
    private function validateRequest(array $data): void
    {
        if (! isset($data['method']) || ! is_string($data['method'])) {
            throw new ProtocolError('Request missing or invalid method');
        }

        if (! isset($data['id'])) {
            throw new ProtocolError('Request missing id');
        }

        // ID must be string, number, or null
        $id = $data['id'];
        if (! is_string($id) && ! is_numeric($id) && $id !== null) {
            throw new ProtocolError('Request id must be string, number, or null');
        }

        // Params are optional but must be array or object if present
        if (isset($data['params'])) {
            if (! is_array($data['params'])) {
                throw new ProtocolError('Request params must be array or object');
            }
        }
    }

    /**
     * Validate a JSON-RPC response.
     *
     * @param array<string, mixed> $data Response data
     * @throws ProtocolError If response is invalid
     */
    private function validateResponse(array $data): void
    {
        if (! isset($data['id'])) {
            throw new ProtocolError('Response missing id');
        }

        // Must have either result or error, but not both
        $hasResult = isset($data['result']);
        $hasError = isset($data['error']);

        if (! $hasResult && ! $hasError) {
            throw new ProtocolError('Response must have either result or error');
        }

        if ($hasResult && $hasError) {
            throw new ProtocolError('Response cannot have both result and error');
        }

        // Validate error structure if present
        if ($hasError) {
            $error = $data['error'];
            if (! is_array($error)) {
                throw new ProtocolError('Error must be an object');
            }

            if (! isset($error['code']) || ! is_int($error['code'])) {
                throw new ProtocolError('Error must have integer code');
            }

            if (! isset($error['message']) || ! is_string($error['message'])) {
                throw new ProtocolError('Error must have string message');
            }
        }
    }

    /**
     * Validate a JSON-RPC notification.
     *
     * @param array<string, mixed> $data Notification data
     * @throws ProtocolError If notification is invalid
     */
    private function validateNotification(array $data): void
    {
        if (! isset($data['method']) || ! is_string($data['method'])) {
            throw new ProtocolError('Notification missing or invalid method');
        }

        if (isset($data['id'])) {
            throw new ProtocolError('Notification must not have id');
        }

        // Params are optional but must be array or object if present
        if (isset($data['params'])) {
            if (! is_array($data['params'])) {
                throw new ProtocolError('Notification params must be array or object');
            }
        }
    }

    /**
     * Record a parsing error for debugging.
     *
     * @param string $error Error message
     * @param string $input Input that caused the error
     */
    private function recordParseError(string $error, string $input): void
    {
        ++$this->parseErrorCount;

        $errorEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'error' => $error,
            'input' => substr($input, 0, 200), // Truncate long inputs
        ];

        $this->recentErrors[] = $errorEntry;

        // Keep only recent errors
        if (count($this->recentErrors) > 10) {
            $this->recentErrors = array_slice($this->recentErrors, -5);
        }
    }
}
