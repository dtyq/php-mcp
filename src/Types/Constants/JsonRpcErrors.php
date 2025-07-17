<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Constants;

/**
 * JSON-RPC 2.0 error code constants.
 *
 * Contains standard JSON-RPC error codes as defined in the specification.
 */
final class JsonRpcErrors
{
    // Standard JSON-RPC error codes
    public const PARSE_ERROR = -32700;

    public const INVALID_REQUEST = -32600;

    public const METHOD_NOT_FOUND = -32601;

    public const INVALID_PARAMS = -32602;

    public const INTERNAL_ERROR = -32603;

    /**
     * Get all standard JSON-RPC error codes.
     *
     * @return array<int>
     */
    public static function getStandardErrorCodes(): array
    {
        return array_keys(self::getErrorMessageMap());
    }

    /**
     * Check if an error code is a standard JSON-RPC error.
     */
    public static function isStandardJsonRpcError(int $code): bool
    {
        $messageMap = self::getErrorMessageMap();
        return isset($messageMap[$code]);
    }

    /**
     * Get error message for a standard JSON-RPC error code.
     */
    public static function getErrorMessage(int $code): ?string
    {
        $messageMap = self::getErrorMessageMap();
        return $messageMap[$code] ?? null;
    }

    /**
     * Get error code to message mapping.
     *
     * @return array<int, string>
     */
    private static function getErrorMessageMap(): array
    {
        return [
            self::PARSE_ERROR => 'Parse error',
            self::INVALID_REQUEST => 'Invalid Request',
            self::METHOD_NOT_FOUND => 'Method not found',
            self::INVALID_PARAMS => 'Invalid params',
            self::INTERNAL_ERROR => 'Internal error',
        ];
    }
}
