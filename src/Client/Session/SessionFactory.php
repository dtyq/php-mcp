<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client\Session;

use Dtyq\PhpMcp\Client\Core\TransportInterface;
use Dtyq\PhpMcp\Shared\Exceptions\ProtocolError;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;

/**
 * Factory for creating client sessions with proper configuration.
 *
 * This class provides convenient methods to create ClientSession instances
 * with various configurations and transport types.
 */
class SessionFactory
{
    /**
     * Create a client session with default configuration.
     *
     * @param TransportInterface $transport The transport to use
     * @param null|array<string, mixed> $config Optional configuration overrides
     * @param null|array<string, mixed> $clientCapabilities Optional client capabilities
     * @return ClientSession The configured session
     * @throws ValidationError If configuration is invalid
     */
    public static function create(
        TransportInterface $transport,
        ?array $config = null,
        ?array $clientCapabilities = null
    ): ClientSession {
        $metadata = self::createMetadata($config ?? []);

        return new ClientSession($transport, $metadata, $clientCapabilities);
    }

    /**
     * Create a session with stdio transport configuration.
     *
     * @param TransportInterface $transport The stdio transport
     * @param null|array<string, mixed> $config Optional configuration overrides
     * @return ClientSession The configured session
     * @throws ValidationError If configuration is invalid
     */
    public static function createForStdio(
        TransportInterface $transport,
        ?array $config = null
    ): ClientSession {
        $defaultConfig = [
            'response_timeout' => 30.0,
            'initialization_timeout' => 60.0,
            'client_name' => 'php-mcp-stdio-client',
            'client_version' => '1.0.0',
        ];

        $mergedConfig = array_merge($defaultConfig, $config ?? []);
        $metadata = self::createMetadata($mergedConfig);

        $capabilities = self::getStdioCapabilities();

        return new ClientSession($transport, $metadata, $capabilities);
    }

    /**
     * Create a session for development/testing with relaxed timeouts.
     *
     * @param TransportInterface $transport The transport to use
     * @param null|array<string, mixed> $config Optional configuration overrides
     * @return ClientSession The configured session
     * @throws ValidationError If configuration is invalid
     */
    public static function createForDevelopment(
        TransportInterface $transport,
        ?array $config = null
    ): ClientSession {
        $defaultConfig = [
            'response_timeout' => 120.0, // Longer timeout for development
            'initialization_timeout' => 180.0,
            'client_name' => 'php-mcp-dev-client',
            'client_version' => '1.0.0-dev',
        ];

        $mergedConfig = array_merge($defaultConfig, $config ?? []);
        $metadata = self::createMetadata($mergedConfig);

        $capabilities = self::getDevelopmentCapabilities();

        return new ClientSession($transport, $metadata, $capabilities);
    }

    /**
     * Create session metadata from configuration array.
     *
     * @param array<string, mixed> $config Configuration array
     * @return SessionMetadata The session metadata
     * @throws ValidationError If configuration is invalid
     */
    public static function createMetadata(array $config): SessionMetadata
    {
        // Validate required fields
        if (isset($config['response_timeout']) && $config['response_timeout'] <= 0) {
            throw ValidationError::invalidFieldValue(
                'response_timeout',
                'must be greater than 0',
                ['value' => $config['response_timeout']]
            );
        }

        if (isset($config['initialization_timeout']) && $config['initialization_timeout'] <= 0) {
            throw ValidationError::invalidFieldValue(
                'initialization_timeout',
                'must be greater than 0',
                ['value' => $config['initialization_timeout']]
            );
        }

        return SessionMetadata::fromArray($config);
    }

    /**
     * Validate transport compatibility with session requirements.
     *
     * @param TransportInterface $transport The transport to validate
     * @throws ValidationError If transport is incompatible
     */
    public static function validateTransport(TransportInterface $transport): void
    {
        if (! $transport->isConnected()) {
            throw ValidationError::invalidFieldValue(
                'transport',
                'must be connected before creating session',
                ['transport_type' => $transport->getType()]
            );
        }
    }

    /**
     * Create a session and automatically initialize it.
     *
     * @param TransportInterface $transport The transport to use
     * @param null|array<string, mixed> $config Optional configuration overrides
     * @param null|array<string, mixed> $clientCapabilities Optional client capabilities
     * @return ClientSession The initialized session
     * @throws ValidationError If configuration is invalid
     * @throws ProtocolError If initialization fails
     */
    public static function createAndInitialize(
        TransportInterface $transport,
        ?array $config = null,
        ?array $clientCapabilities = null
    ): ClientSession {
        self::validateTransport($transport);

        $session = self::create($transport, $config, $clientCapabilities);
        $session->initialize();

        return $session;
    }

    /**
     * Create multiple sessions for load balancing or redundancy.
     *
     * @param array<TransportInterface> $transports Array of transports
     * @param null|array<string, mixed> $config Optional configuration overrides
     * @param null|array<string, mixed> $clientCapabilities Optional client capabilities
     * @return array<ClientSession> Array of configured sessions
     * @throws ValidationError If configuration is invalid
     */
    public static function createMultiple(
        array $transports,
        ?array $config = null,
        ?array $clientCapabilities = null
    ): array {
        if (empty($transports)) {
            throw ValidationError::emptyField('transports');
        }

        $sessions = [];
        foreach ($transports as $index => $transport) {
            if (! $transport instanceof TransportInterface) {
                throw ValidationError::invalidFieldType(
                    "transports[{$index}]",
                    'TransportInterface',
                    get_debug_type($transport)
                );
            }

            $sessions[] = self::create($transport, $config, $clientCapabilities);
        }

        return $sessions;
    }

    /**
     * Get default capabilities for stdio transport.
     *
     * @return array<string, mixed> Stdio-specific capabilities
     */
    private static function getStdioCapabilities(): array
    {
        return [
            'tools' => [
                'listChanged' => true,
            ],
            'resources' => [
                'subscribe' => true,
                'listChanged' => true,
            ],
            'prompts' => [
                'listChanged' => true,
            ],
            'sampling' => [],
            'logging' => [],
        ];
    }

    /**
     * Get capabilities for development/testing.
     *
     * @return array<string, mixed> Development-specific capabilities
     */
    private static function getDevelopmentCapabilities(): array
    {
        return [
            'tools' => [
                'listChanged' => true,
            ],
            'resources' => [
                'subscribe' => true,
                'listChanged' => true,
            ],
            'prompts' => [
                'listChanged' => true,
            ],
            'sampling' => [
                'createMessage' => true,
            ],
            'logging' => [
                'setLevel' => true,
            ],
            'roots' => [
                'listChanged' => true,
            ],
        ];
    }
}
