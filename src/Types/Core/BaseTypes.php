<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Core;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Constants\LogLevels;
use Dtyq\PhpMcp\Types\Constants\MessageConstants;

/**
 * Basic type definitions for MCP protocol.
 *
 * Contains fundamental types used throughout the Model Context Protocol:
 * - ProgressToken: For tracking long-running operations
 * - Cursor: For pagination
 * - Role: For message roles (user/assistant)
 * - RequestId: For request correlation
 */
final class BaseTypes
{
    /**
     * Validate a progress token.
     *
     * @param mixed $token
     * @throws ValidationError
     */
    public static function validateProgressToken($token): void
    {
        if ($token !== null && ! is_string($token) && ! is_int($token)) {
            throw ValidationError::invalidArgumentType('progressToken', 'string, integer, or null', gettype($token));
        }
    }

    /**
     * Validate a cursor.
     *
     * @param mixed $cursor
     * @throws ValidationError
     */
    public static function validateCursor($cursor): void
    {
        if ($cursor !== null && ! is_string($cursor)) {
            throw ValidationError::invalidArgumentType('cursor', 'string or null', gettype($cursor));
        }
    }

    /**
     * Validate a role.
     *
     * @throws ValidationError
     */
    public static function validateRole(string $role): void
    {
        if (! MessageConstants::isValidRole($role)) {
            throw ValidationError::invalidFieldValue(
                'role',
                'must be one of: ' . implode(', ', MessageConstants::getValidRoles())
            );
        }
    }

    /**
     * Validate a request ID.
     *
     * @param mixed $id
     * @throws ValidationError
     */
    public static function validateRequestId($id): void
    {
        if (! is_string($id) && ! is_int($id)) {
            throw ValidationError::invalidArgumentType('id', 'string or integer', gettype($id));
        }
    }

    /**
     * Validate a URI.
     *
     * @throws ValidationError
     */
    public static function validateUri(string $uri): void
    {
        if (empty($uri)) {
            throw ValidationError::emptyField('uri');
        }

        // Basic URI validation - more specific validation can be done in context
        if (! filter_var($uri, FILTER_VALIDATE_URL) && ! self::isRelativeUri($uri)) {
            throw ValidationError::invalidFieldValue('uri', 'invalid URI format');
        }
    }

    /**
     * Validate MIME type.
     *
     * @throws ValidationError
     */
    public static function validateMimeType(?string $mimeType): void
    {
        if ($mimeType === null) {
            return;
        }

        if (! preg_match('/^[a-zA-Z0-9][a-zA-Z0-9!#$&\-\^_]*\/[a-zA-Z0-9][a-zA-Z0-9!#$&\-\^_.]*$/', $mimeType)) {
            throw ValidationError::invalidFieldValue('mimeType', 'invalid MIME type format');
        }
    }

    /**
     * Validate logging level.
     *
     * @throws ValidationError
     */
    public static function validateLogLevel(string $level): void
    {
        if (! LogLevels::isValidLogLevel($level)) {
            throw ValidationError::invalidFieldValue(
                'logLevel',
                'must be one of: ' . implode(', ', LogLevels::getValidLogLevels())
            );
        }
    }

    /**
     * Validate content type.
     *
     * @throws ValidationError
     */
    public static function validateContentType(string $type): void
    {
        $validTypes = [
            MessageConstants::CONTENT_TYPE_TEXT,
            MessageConstants::CONTENT_TYPE_IMAGE,
            MessageConstants::CONTENT_TYPE_RESOURCE,
            MessageConstants::CONTENT_TYPE_AUDIO,
        ];

        if (! in_array($type, $validTypes, true)) {
            throw ValidationError::invalidFieldValue(
                'contentType',
                'must be one of: ' . implode(', ', $validTypes)
            );
        }
    }

    /**
     * Validate reference type.
     *
     * @throws ValidationError
     */
    public static function validateReferenceType(string $type): void
    {
        $validTypes = [
            MessageConstants::REF_TYPE_RESOURCE,
            MessageConstants::REF_TYPE_PROMPT,
        ];

        if (! in_array($type, $validTypes, true)) {
            throw ValidationError::invalidFieldValue(
                'referenceType',
                'must be one of: ' . implode(', ', $validTypes)
            );
        }
    }

    /**
     * Validate stop reason.
     *
     * @throws ValidationError
     */
    public static function validateStopReason(?string $reason): void
    {
        if ($reason === null) {
            return;
        }

        $validReasons = [
            MessageConstants::STOP_REASON_END_TURN,
            MessageConstants::STOP_REASON_MAX_TOKENS,
            MessageConstants::STOP_REASON_STOP_SEQUENCE,
            MessageConstants::STOP_REASON_TOOL_USE,
        ];

        if (! in_array($reason, $validReasons, true)) {
            throw ValidationError::invalidFieldValue(
                'stopReason',
                'must be one of: ' . implode(', ', $validReasons)
            );
        }
    }

    /**
     * Validate priority value (0.0 to 1.0).
     *
     * @throws ValidationError
     */
    public static function validatePriority(?float $priority): void
    {
        if ($priority === null) {
            return;
        }

        if ($priority < 0.0 || $priority > 1.0) {
            throw ValidationError::invalidFieldValue('priority', 'must be between 0.0 and 1.0');
        }
    }

    /**
     * Sanitize text content for safe output.
     */
    public static function sanitizeText(string $text): string
    {
        // Remove null bytes and control characters except newlines and tabs
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
    }

    /**
     * Generate a unique identifier.
     */
    public static function generateId(): string
    {
        return uniqid('mcp_', true);
    }

    /**
     * Generate a progress token.
     */
    public static function generateProgressToken(): string
    {
        return uniqid('progress_', true);
    }

    /**
     * Generate a cursor for pagination.
     */
    public static function generateCursor(): string
    {
        return base64_encode(uniqid('cursor_', true));
    }

    /**
     * Validate base64 encoded data.
     */
    public static function isValidBase64(string $data): bool
    {
        // Check if the string is valid base64
        $decoded = base64_decode($data, true);
        if ($decoded === false) {
            return false;
        }
        return base64_encode($decoded) === $data;
    }

    /**
     * Format bytes into human-readable format.
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; ++$i) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Polyfill for array_is_list() function (PHP 8.1+).
     * Checks if an array is a list (sequential integer keys starting from 0).
     *
     * @param array<mixed> $array
     */
    public static function arrayIsList(array $array): bool
    {
        if (function_exists('array_is_list')) {
            return array_is_list($array);
        }

        // Polyfill implementation for PHP 7.4
        if (empty($array)) {
            return true;
        }

        $keys = array_keys($array);
        $expectedKeys = range(0, count($array) - 1);

        return $keys === $expectedKeys;
    }

    /**
     * Check if URI is relative.
     */
    private static function isRelativeUri(string $uri): bool
    {
        // Simple check for relative URIs (not starting with scheme)
        return ! preg_match('/^[a-zA-Z][a-zA-Z0-9+.-]*:/', $uri);
    }
}
