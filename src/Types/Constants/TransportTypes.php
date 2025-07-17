<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Constants;

/**
 * Transport type constants for MCP protocol.
 *
 * Contains definitions for transport mechanisms officially
 * supported by the Model Context Protocol specification (2025-03-26).
 *
 * According to MCP spec, there are two standard transport mechanisms:
 * 1. stdio - communication over standard input/output
 * 2. Streamable HTTP - HTTP POST + Server-Sent Events
 */
final class TransportTypes
{
    // Standard transport types defined in MCP 2025-03-26 specification
    public const TRANSPORT_TYPE_STDIO = 'stdio';

    public const TRANSPORT_TYPE_HTTP = 'http';

    public const TRANSPORT_TYPE_SSE = 'sse';

    /**
     * Get all supported transport types.
     *
     * @return array<string>
     */
    public static function getSupportedTransportTypes(): array
    {
        return [
            self::TRANSPORT_TYPE_STDIO,
            self::TRANSPORT_TYPE_HTTP,
            self::TRANSPORT_TYPE_SSE,
        ];
    }

    /**
     * Check if a transport type is supported.
     */
    public static function isValidTransportType(string $transportType): bool
    {
        return in_array($transportType, self::getSupportedTransportTypes(), true);
    }

    /**
     * Check if a transport type supports bidirectional communication.
     */
    public static function supportsBidirectional(string $transportType): bool
    {
        return $transportType === self::TRANSPORT_TYPE_STDIO;
    }

    /**
     * Check if a transport type requires network connectivity.
     */
    public static function requiresNetwork(string $transportType): bool
    {
        return in_array($transportType, [
            self::TRANSPORT_TYPE_HTTP,
            self::TRANSPORT_TYPE_SSE,
        ], true);
    }

    /**
     * Check if a transport type supports streaming.
     */
    public static function supportsStreaming(string $transportType): bool
    {
        return in_array($transportType, [
            self::TRANSPORT_TYPE_STDIO,
            self::TRANSPORT_TYPE_SSE,
        ], true);
    }

    /**
     * Get the default port for a transport type.
     */
    public static function getDefaultPort(string $transportType): ?int
    {
        $portMap = self::getDefaultPortMap();
        return $portMap[$transportType] ?? null; // stdio and sse don't have default ports
    }

    /**
     * Get the default secure port for a transport type.
     */
    public static function getDefaultSecurePort(string $transportType): ?int
    {
        $securePortMap = self::getDefaultSecurePortMap();
        return $securePortMap[$transportType] ?? null;
    }

    /**
     * Check if a transport type supports SSL/TLS.
     */
    public static function supportsSecure(string $transportType): bool
    {
        return in_array($transportType, [
            self::TRANSPORT_TYPE_HTTP,
            self::TRANSPORT_TYPE_SSE,
        ], true);
    }

    /**
     * Get the protocol scheme for a transport type.
     */
    public static function getProtocolScheme(string $transportType, bool $secure = false): ?string
    {
        if ($transportType === self::TRANSPORT_TYPE_HTTP || $transportType === self::TRANSPORT_TYPE_SSE) {
            return $secure ? 'https' : 'http';
        }

        return null;
    }

    /**
     * Get transport type to default port mapping.
     *
     * @return array<string, int>
     */
    private static function getDefaultPortMap(): array
    {
        return [
            self::TRANSPORT_TYPE_HTTP => 80,
        ];
    }

    /**
     * Get transport type to default secure port mapping.
     *
     * @return array<string, int>
     */
    private static function getDefaultSecurePortMap(): array
    {
        return [
            self::TRANSPORT_TYPE_HTTP => 443,
        ];
    }
}
