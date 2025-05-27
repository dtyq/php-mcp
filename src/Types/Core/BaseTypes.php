<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Core;

use InvalidArgumentException;

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
     * @throws InvalidArgumentException
     */
    public static function validateProgressToken($token): void
    {
        if ($token !== null && ! is_string($token) && ! is_int($token)) {
            throw new InvalidArgumentException('Progress token must be string, integer, or null');
        }
    }

    /**
     * Validate a cursor.
     *
     * @param mixed $cursor
     * @throws InvalidArgumentException
     */
    public static function validateCursor($cursor): void
    {
        if ($cursor !== null && ! is_string($cursor)) {
            throw new InvalidArgumentException('Cursor must be string or null');
        }
    }

    /**
     * Validate a role.
     *
     * @throws InvalidArgumentException
     */
    public static function validateRole(string $role): void
    {
        if (! ProtocolConstants::isValidRole($role)) {
            throw new InvalidArgumentException(
                'Role must be one of: ' . implode(', ', ProtocolConstants::getValidRoles())
            );
        }
    }

    /**
     * Validate a request ID.
     *
     * @param mixed $id
     * @throws InvalidArgumentException
     */
    public static function validateRequestId($id): void
    {
        if (! is_string($id) && ! is_int($id)) {
            throw new InvalidArgumentException('Request ID must be string or integer');
        }
    }

    /**
     * Validate a URI.
     *
     * @throws InvalidArgumentException
     */
    public static function validateUri(string $uri): void
    {
        if (empty($uri)) {
            throw new InvalidArgumentException('URI cannot be empty');
        }

        // Basic URI validation - more specific validation can be done in context
        if (! filter_var($uri, FILTER_VALIDATE_URL) && ! self::isRelativeUri($uri)) {
            throw new InvalidArgumentException('Invalid URI format');
        }
    }

    /**
     * Validate MIME type.
     *
     * @throws InvalidArgumentException
     */
    public static function validateMimeType(?string $mimeType): void
    {
        if ($mimeType === null) {
            return;
        }

        if (! preg_match('/^[a-zA-Z0-9][a-zA-Z0-9!#$&\-\^_]*\/[a-zA-Z0-9][a-zA-Z0-9!#$&\-\^_.]*$/', $mimeType)) {
            throw new InvalidArgumentException('Invalid MIME type format');
        }
    }

    /**
     * Validate logging level.
     *
     * @throws InvalidArgumentException
     */
    public static function validateLogLevel(string $level): void
    {
        if (! ProtocolConstants::isValidLogLevel($level)) {
            throw new InvalidArgumentException(
                'Logging level must be one of: ' . implode(', ', ProtocolConstants::getValidLogLevels())
            );
        }
    }

    /**
     * Validate content type.
     *
     * @throws InvalidArgumentException
     */
    public static function validateContentType(string $type): void
    {
        $validTypes = [
            ProtocolConstants::CONTENT_TYPE_TEXT,
            ProtocolConstants::CONTENT_TYPE_IMAGE,
            ProtocolConstants::CONTENT_TYPE_RESOURCE,
        ];

        if (! in_array($type, $validTypes, true)) {
            throw new InvalidArgumentException(
                'Content type must be one of: ' . implode(', ', $validTypes)
            );
        }
    }

    /**
     * Validate reference type.
     *
     * @throws InvalidArgumentException
     */
    public static function validateReferenceType(string $type): void
    {
        $validTypes = [
            ProtocolConstants::REF_TYPE_RESOURCE,
            ProtocolConstants::REF_TYPE_PROMPT,
        ];

        if (! in_array($type, $validTypes, true)) {
            throw new InvalidArgumentException(
                'Reference type must be one of: ' . implode(', ', $validTypes)
            );
        }
    }

    /**
     * Validate stop reason.
     *
     * @throws InvalidArgumentException
     */
    public static function validateStopReason(?string $reason): void
    {
        if ($reason === null) {
            return;
        }

        $validReasons = [
            ProtocolConstants::STOP_REASON_END_TURN,
            ProtocolConstants::STOP_REASON_MAX_TOKENS,
            ProtocolConstants::STOP_REASON_STOP_SEQUENCE,
            ProtocolConstants::STOP_REASON_TOOL_USE,
        ];

        if (! in_array($reason, $validReasons, true)) {
            throw new InvalidArgumentException(
                'Stop reason must be one of: ' . implode(', ', $validReasons)
            );
        }
    }

    /**
     * Validate priority value (0.0 to 1.0).
     *
     * @throws InvalidArgumentException
     */
    public static function validatePriority(?float $priority): void
    {
        if ($priority === null) {
            return;
        }

        if ($priority < 0.0 || $priority > 1.0) {
            throw new InvalidArgumentException('Priority must be between 0.0 and 1.0');
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
     * Check if URI is relative.
     */
    private static function isRelativeUri(string $uri): bool
    {
        // Simple check for relative URIs (not starting with scheme)
        return ! preg_match('/^[a-zA-Z][a-zA-Z0-9+.-]*:/', $uri);
    }
}
