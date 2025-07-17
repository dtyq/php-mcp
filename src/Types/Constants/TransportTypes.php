<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Constants;

/**
 * Transport type constants for MCP protocol.
 *
 * Contains definitions for various transport mechanisms
 * supported by the Model Context Protocol.
 */
final class TransportTypes
{
    // Transport types
    public const TRANSPORT_TYPE_STDIO = 'stdio';

    public const TRANSPORT_TYPE_HTTP = 'http';

    public const TRANSPORT_TYPE_SSE = 'sse';

    public const TRANSPORT_TYPE_WEBSOCKET = 'websocket';

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
            self::TRANSPORT_TYPE_WEBSOCKET,
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
        return in_array($transportType, [
            self::TRANSPORT_TYPE_STDIO,
            self::TRANSPORT_TYPE_WEBSOCKET,
        ], true);
    }

    /**
     * Check if a transport type requires network connectivity.
     */
    public static function requiresNetwork(string $transportType): bool
    {
        return in_array($transportType, [
            self::TRANSPORT_TYPE_HTTP,
            self::TRANSPORT_TYPE_SSE,
            self::TRANSPORT_TYPE_WEBSOCKET,
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
            self::TRANSPORT_TYPE_WEBSOCKET,
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
            self::TRANSPORT_TYPE_WEBSOCKET,
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

        if ($transportType === self::TRANSPORT_TYPE_WEBSOCKET) {
            return $secure ? 'wss' : 'ws';
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
            self::TRANSPORT_TYPE_WEBSOCKET => 80,
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
            self::TRANSPORT_TYPE_WEBSOCKET => 443,
        ];
    }
}
