<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Constants;

/**
 * Server-Sent Events (SSE) constants for MCP protocol.
 *
 * Contains SSE-specific field names, event types, and
 * other constants used in MCP SSE transport.
 */
final class SseConstants
{
    // SSE event types
    public const SSE_EVENT_TYPE_MESSAGE = 'message';

    public const SSE_EVENT_TYPE_ERROR = 'error';

    public const SSE_EVENT_TYPE_PING = 'ping';

    public const SSE_EVENT_TYPE_CLOSE = 'close';

    public const SSE_EVENT_TYPE_ENDPOINT = 'endpoint';

    // SSE field names
    public const SSE_FIELD_EVENT = 'event';

    public const SSE_FIELD_DATA = 'data';

    public const SSE_FIELD_ID = 'id';

    public const SSE_FIELD_RETRY = 'retry';

    public const SSE_FIELD_COMMENT = '';

    // SSE line endings and separators
    public const SSE_LINE_ENDING = "\n";

    public const SSE_FIELD_SEPARATOR = ': ';

    public const SSE_EVENT_SEPARATOR = "\n\n";

    // Default values
    public const DEFAULT_RETRY_MS = 5000;

    public const DEFAULT_TIMEOUT_MS = 30000;

    /**
     * Get all valid SSE event types.
     *
     * @return array<string>
     */
    public static function getValidEventTypes(): array
    {
        return [
            self::SSE_EVENT_TYPE_MESSAGE,
            self::SSE_EVENT_TYPE_ERROR,
            self::SSE_EVENT_TYPE_PING,
            self::SSE_EVENT_TYPE_CLOSE,
            self::SSE_EVENT_TYPE_ENDPOINT,
        ];
    }

    /**
     * Get all SSE field names.
     *
     * @return array<string>
     */
    public static function getFieldNames(): array
    {
        return [
            self::SSE_FIELD_EVENT,
            self::SSE_FIELD_DATA,
            self::SSE_FIELD_ID,
            self::SSE_FIELD_RETRY,
            self::SSE_FIELD_COMMENT,
        ];
    }

    /**
     * Check if an event type is valid.
     */
    public static function isValidEventType(string $eventType): bool
    {
        return in_array($eventType, self::getValidEventTypes(), true);
    }

    /**
     * Check if a field name is valid.
     */
    public static function isValidField(string $fieldName): bool
    {
        return in_array($fieldName, self::getFieldNames(), true);
    }

    /**
     * Format an SSE field line.
     */
    public static function formatField(string $field, string $value): string
    {
        if ($field === self::SSE_FIELD_COMMENT) {
            return $field . $value . self::SSE_LINE_ENDING;
        }

        return $field . self::SSE_FIELD_SEPARATOR . $value . self::SSE_LINE_ENDING;
    }

    /**
     * Format a complete SSE event.
     *
     * @param array<string, mixed> $fields Event fields
     */
    public static function formatEvent(array $fields): string
    {
        $lines = [];

        foreach ($fields as $field => $value) {
            if (self::isValidField($field) && $value !== null) {
                // Handle multi-line data fields
                if ($field === self::SSE_FIELD_DATA && is_string($value)) {
                    $dataLines = explode("\n", $value);
                    foreach ($dataLines as $dataLine) {
                        $lines[] = self::formatField($field, $dataLine);
                    }
                } else {
                    $lines[] = self::formatField($field, (string) $value);
                }
            }
        }

        return implode('', $lines) . self::SSE_LINE_ENDING;
    }

    /**
     * Parse an SSE field line.
     *
     * @return null|array{field: string, value: string}
     */
    public static function parseFieldLine(string $line): ?array
    {
        $line = rtrim($line, "\r\n");

        // Handle comment lines
        if (str_starts_with($line, ':')) {
            return [
                'field' => self::SSE_FIELD_COMMENT,
                'value' => substr($line, 1),
            ];
        }

        // Handle field lines
        $separatorPos = strpos($line, ':');
        if ($separatorPos === false) {
            return [
                'field' => $line,
                'value' => '',
            ];
        }

        $field = substr($line, 0, $separatorPos);
        $value = substr($line, $separatorPos + 1);

        // Remove leading space from value if present
        if (str_starts_with($value, ' ')) {
            $value = substr($value, 1);
        }

        return [
            'field' => $field,
            'value' => $value,
        ];
    }

    /**
     * Check if an event type requires data.
     */
    public static function requiresData(string $eventType): bool
    {
        return in_array($eventType, [
            self::SSE_EVENT_TYPE_MESSAGE,
            self::SSE_EVENT_TYPE_ERROR,
            self::SSE_EVENT_TYPE_ENDPOINT,
        ], true);
    }
}
