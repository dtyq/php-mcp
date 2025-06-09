<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Core;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Shared\Utilities\JsonUtils;
use Exception;

/**
 * Universal message validator for MCP JSON-RPC messages.
 *
 * This class provides static validation methods for both client and server-side
 * message processing, ensuring compliance with MCP specification
 * and JSON-RPC 2.0 standard.
 *
 * All validation methods throw ValidationError exceptions on failure.
 */
final class MessageValidator
{
    /**
     * Validate a complete JSON-RPC message string.
     *
     * @param string $message Raw message string
     * @param bool $strictMode Enable strict MCP stdio validation
     * @throws ValidationError If validation fails
     */
    public static function validateMessage(string $message, bool $strictMode = false): void
    {
        // 1. UTF-8 encoding validation (MCP requirement)
        self::validateUtf8($message);

        // 2. MCP stdio-specific validation (if strict mode)
        if ($strictMode) {
            self::validateStdioFormat($message);
        }

        // 3. JSON parsing validation
        try {
            $decoded = JsonUtils::decode($message, true);
        } catch (Exception $e) {
            throw ValidationError::invalidJsonFormat($e->getMessage(), [
                'message' => $message,
                'json_error' => $e->getMessage(),
            ]);
        }

        // 4. Structure validation
        self::validateStructure($decoded);
    }

    /**
     * Validate UTF-8 encoding (reused from MessageProcessor).
     *
     * @param string $message The message to validate
     * @throws ValidationError If encoding is invalid
     */
    public static function validateUtf8(string $message): void
    {
        if (! mb_check_encoding($message, 'UTF-8')) {
            throw new ValidationError('Message contains invalid UTF-8 encoding', [
                'message_length' => strlen($message),
                'first_bytes' => substr($message, 0, 100),
            ]);
        }
    }

    /**
     * Validate MCP stdio format requirements (NEW - spec requirement).
     *
     * According to MCP specification, stdio transport messages
     * must not contain embedded newlines.
     *
     * @param string $message The message to validate
     * @throws ValidationError If format is invalid
     */
    public static function validateStdioFormat(string $message): void
    {
        if (str_contains($message, "\n") || str_contains($message, "\r")) {
            throw new ValidationError(
                'Message contains embedded newlines, which violates MCP stdio transport specification',
                [
                    'message' => $message,
                    'contains_lf' => str_contains($message, "\n"),
                    'contains_cr' => str_contains($message, "\r"),
                ]
            );
        }
    }

    /**
     * Validate JSON-RPC structure (reused and enhanced from MessageProcessor).
     *
     * @param mixed $decoded The decoded JSON data
     * @throws ValidationError If structure is invalid
     */
    public static function validateStructure($decoded): void
    {
        if (! is_array($decoded)) {
            throw ValidationError::invalidFieldType('message', 'array', gettype($decoded), $decoded);
        }

        // Check if it's a batch message (array of messages)
        if (BaseTypes::arrayIsList($decoded)) {
            self::validateBatch($decoded);
            return;
        }

        // Single message
        self::validateSingleMessage($decoded);
    }

    /**
     * Validate a single JSON-RPC message (reused from MessageProcessor).
     *
     * @param array<string, mixed> $message The message to validate
     * @throws ValidationError If message is invalid
     */
    public static function validateSingleMessage(array $message): void
    {
        // Must have jsonrpc version
        if (! isset($message['jsonrpc'])) {
            throw ValidationError::requiredFieldMissing('jsonrpc', 'JSON-RPC message', $message);
        }

        if ($message['jsonrpc'] !== ProtocolConstants::JSONRPC_VERSION) {
            throw ValidationError::invalidFieldValue(
                'jsonrpc',
                'must be "' . ProtocolConstants::JSONRPC_VERSION . '"',
                [
                    'expected' => ProtocolConstants::JSONRPC_VERSION,
                    'actual' => $message['jsonrpc'],
                    'message' => $message,
                ]
            );
        }

        // Request or notification must have method
        if (isset($message['method'])) {
            if (! is_string($message['method'])) {
                throw ValidationError::invalidFieldType('method', 'string', gettype($message['method']), $message);
            }
            return;
        }

        // Response must have result or error, and id
        if (isset($message['result']) || isset($message['error'])) {
            if (! isset($message['id'])) {
                throw ValidationError::requiredFieldMissing('id', 'JSON-RPC response', $message);
            }
            return;
        }

        // If we get here, the message is neither a valid request/notification nor a valid response
        throw new ValidationError(
            'Invalid JSON-RPC message: must have either "method" (for request/notification) or "result"/"error" with "id" (for response)',
            $message
        );
    }

    /**
     * Validate batch messages (NEW - enhanced batch support).
     *
     * @param array<int, mixed> $batch Array of messages to validate
     * @throws ValidationError If batch is invalid
     */
    public static function validateBatch(array $batch): void
    {
        if (empty($batch)) {
            throw new ValidationError('Batch message cannot be empty', $batch);
        }

        foreach ($batch as $index => $item) {
            if (! is_array($item)) {
                throw ValidationError::invalidFieldType(
                    "batch[{$index}]",
                    'array',
                    gettype($item),
                    ['batch' => $batch, 'invalid_item' => $item, 'index' => $index]
                );
            }

            try {
                self::validateSingleMessage($item);
            } catch (ValidationError $e) {
                throw new ValidationError(
                    "Invalid message at batch index {$index}: " . $e->getMessage(),
                    ['batch' => $batch, 'invalid_item' => $item, 'index' => $index, 'original_error' => $e->getMessage()]
                );
            }
        }
    }

    /**
     * Safe validation that returns boolean instead of throwing (convenience method).
     *
     * @param string $message Raw message string
     * @param bool $strictMode Enable strict MCP stdio validation
     * @return bool True if valid, false otherwise
     */
    public static function isValidMessage(string $message, bool $strictMode = false): bool
    {
        try {
            self::validateMessage($message, $strictMode);
            return true;
        } catch (ValidationError $ex) {
            return false;
        }
    }

    /**
     * Get message type information (NEW - utility method).
     *
     * @param string $message Raw message string
     * @return array{type: string, isBatch: bool, method?: string, id?: mixed, count?: int} Message info
     * @throws ValidationError If message is not valid JSON
     */
    public static function getMessageInfo(string $message): array
    {
        $info = ['type' => 'unknown', 'isBatch' => false];

        try {
            $decoded = JsonUtils::decode($message, true);
        } catch (Exception $e) {
            throw ValidationError::invalidJsonFormat($e->getMessage(), [
                'message' => $message,
                'json_error' => $e->getMessage(),
            ]);
        }

        if (! is_array($decoded)) {
            return $info;
        }

        if (BaseTypes::arrayIsList($decoded)) {
            $info['type'] = 'batch';
            $info['isBatch'] = true;
            $info['count'] = count($decoded);
        } else {
            if (isset($decoded['method'])) {
                $info['type'] = isset($decoded['id']) ? 'request' : 'notification';
                $info['method'] = $decoded['method'];
                if (isset($decoded['id'])) {
                    $info['id'] = $decoded['id'];
                }
            } elseif (isset($decoded['result']) || isset($decoded['error'])) {
                $info['type'] = 'response';
                if (isset($decoded['id'])) {
                    $info['id'] = $decoded['id'];
                }
            }
        }

        return $info;
    }
}
