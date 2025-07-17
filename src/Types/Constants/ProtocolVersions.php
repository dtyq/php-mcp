<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Constants;

/**
 * MCP Protocol version constants and utilities.
 *
 * Contains all protocol version definitions and related logic
 * for version detection and compatibility checks.
 */
final class ProtocolVersions
{
    /** Current MCP protocol version for clients */
    public const LATEST_PROTOCOL_VERSION = '2025-06-18';

    /** Default MCP protocol version for servers (stable) */
    public const DEFAULT_SERVER_PROTOCOL_VERSION = '2025-03-26';

    /** MCP Protocol version 2025-06-18 */
    public const PROTOCOL_VERSION_20250618 = '2025-06-18';

    /** MCP Protocol version 2025-03-26 */
    public const PROTOCOL_VERSION_20250326 = '2025-03-26';

    /** MCP Protocol version 2024-11-05 */
    public const PROTOCOL_VERSION_20241105 = '2024-11-05';

    /** JSON-RPC 2.0 version */
    public const JSONRPC_VERSION = '2.0';

    /**
     * Get all supported protocol versions.
     *
     * @return array<string>
     */
    public static function getSupportedProtocolVersions(): array
    {
        return array_unique([
            self::PROTOCOL_VERSION_20250618,
            self::PROTOCOL_VERSION_20250326,
            self::PROTOCOL_VERSION_20241105,
        ]);
    }

    /**
     * Check if a protocol version uses the new streamable HTTP transport.
     *
     * @param string $version Protocol version to check
     * @return bool True if version uses streamable HTTP, false for legacy SSE
     */
    public static function usesStreamableHttp(string $version): bool
    {
        return in_array($version, [
            self::PROTOCOL_VERSION_20250618,
            self::PROTOCOL_VERSION_20250326,
        ], true);
    }

    /**
     * Check if a protocol version is supported.
     */
    public static function isValidProtocolVersion(string $version): bool
    {
        return in_array($version, self::getSupportedProtocolVersions(), true);
    }

    /**
     * Get the latest compatible version for the given version.
     *
     * @param string $requestedVersion The version requested by client
     * @return string The best compatible version to use
     */
    public static function getCompatibleVersion(string $requestedVersion): string
    {
        if (self::isValidProtocolVersion($requestedVersion)) {
            return $requestedVersion;
        }

        // Fallback to latest supported version
        return self::LATEST_PROTOCOL_VERSION;
    }
}
