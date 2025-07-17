<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client\Transport;

use Dtyq\PhpMcp\Client\Configuration\ClientConfig;
use Dtyq\PhpMcp\Client\Configuration\HttpConfig;
use Dtyq\PhpMcp\Client\Configuration\StdioConfig;
use Dtyq\PhpMcp\Client\Core\TransportInterface;
use Dtyq\PhpMcp\Client\Transport\Http\HttpTransport;
use Dtyq\PhpMcp\Client\Transport\Stdio\StdioTransport;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Dtyq\PhpMcp\Types\Constants\TransportTypes;

/**
 * Factory for creating transport instances.
 *
 * This factory implements the factory method pattern to create
 * appropriate transport instances based on the requested type,
 * with support for multiple transport protocols including stdio and HTTP.
 */
class TransportFactory
{
    /** @var array<string, class-string<TransportInterface>> */
    private static array $transportTypes = [
        TransportTypes::TRANSPORT_TYPE_STDIO => StdioTransport::class,
        TransportTypes::TRANSPORT_TYPE_HTTP => HttpTransport::class,
    ];

    /**
     * Create a transport instance.
     *
     * @param string $type The transport type identifier
     * @param ClientConfig $config Client configuration containing transport settings
     * @param Application $application Application instance for services
     * @return TransportInterface The created transport instance
     * @throws ValidationError If transport type is invalid or configuration is invalid
     */
    public static function create(string $type, ClientConfig $config, Application $application): TransportInterface
    {
        // Validate transport type
        self::validateTransportType($type);

        // Get transport-specific configuration
        $transportConfig = $config->getTransportConfig();

        // Create transport based on type
        switch ($type) {
            case TransportTypes::TRANSPORT_TYPE_STDIO:
                return self::createStdioTransport($transportConfig, $application);
            case TransportTypes::TRANSPORT_TYPE_HTTP:
                return self::createHttpTransport($transportConfig, $application);
            default:
                throw ValidationError::invalidFieldValue(
                    'transportType',
                    'Unsupported transport type',
                    ['type' => $type, 'supported' => array_keys(self::$transportTypes)]
                );
        }
    }

    /**
     * Get a list of supported transport types.
     *
     * @return array<string> Array of supported transport type identifiers
     */
    public static function getSupportedTypes(): array
    {
        // Combine built-in types with dynamically registered ones
        $builtInTypes = [
            TransportTypes::TRANSPORT_TYPE_STDIO,
            TransportTypes::TRANSPORT_TYPE_HTTP,
        ];

        $registeredTypes = array_keys(self::$transportTypes);

        return array_unique(array_merge($builtInTypes, $registeredTypes));
    }

    /**
     * Check if a transport type is supported.
     *
     * @param string $type The transport type to check
     * @return bool True if supported, false otherwise
     */
    public static function isSupported(string $type): bool
    {
        return isset(self::$transportTypes[$type]) || in_array($type, self::getSupportedTypes(), true);
    }

    /**
     * Register a new transport type.
     *
     * This method allows for runtime registration of custom transport types.
     *
     * @param string $type The transport type identifier
     * @param class-string<TransportInterface> $className The transport class name
     * @throws ValidationError If the class doesn't implement TransportInterface
     */
    public static function registerTransport(string $type, string $className): void
    {
        if (! class_exists($className)) {
            throw ValidationError::invalidFieldValue(
                'className',
                'Class does not exist',
                ['className' => $className]
            );
        }

        if (! is_subclass_of($className, TransportInterface::class)) {
            throw ValidationError::invalidFieldValue(
                'className',
                'Class must implement TransportInterface',
                ['className' => $className, 'interface' => TransportInterface::class]
            );
        }

        self::$transportTypes[$type] = $className;
    }

    /**
     * Create configuration for a specific transport type with defaults.
     *
     * @param string $type The transport type
     * @param array<string, mixed> $overrides Configuration overrides
     * @return array<string, mixed> Transport configuration
     * @throws ValidationError If transport type is unsupported
     */
    public static function createDefaultConfig(string $type, array $overrides = []): array
    {
        self::validateTransportType($type);

        switch ($type) {
            case TransportTypes::TRANSPORT_TYPE_STDIO:
                $defaults = StdioConfig::getDefaults();
                break;
            case TransportTypes::TRANSPORT_TYPE_HTTP:
                $defaults = HttpConfig::DEFAULTS;
                break;
            default:
                return [];
        }

        return array_merge($defaults, $overrides);
    }

    /**
     * Create stdio transport instance.
     *
     * @param array<string, mixed> $transportConfig Transport configuration
     * @param Application $application Application instance
     * @return StdioTransport Created transport instance
     * @throws ValidationError If configuration is invalid
     */
    private static function createStdioTransport(array $transportConfig, Application $application): StdioTransport
    {
        // Extract command from transport config
        if (! isset($transportConfig['command']) || ! is_array($transportConfig['command'])) {
            throw ValidationError::invalidFieldValue(
                'command',
                'Stdio transport requires command array',
                ['transportConfig' => $transportConfig]
            );
        }

        // Create stdio config
        $stdioConfig = StdioConfig::fromArray($transportConfig);

        return new StdioTransport($transportConfig['command'], $stdioConfig, $application);
    }

    /**
     * Create HTTP transport instance.
     *
     * @param array<string, mixed> $transportConfig Transport configuration
     * @param Application $application Application instance
     * @return HttpTransport Created transport instance
     * @throws ValidationError If configuration is invalid
     */
    private static function createHttpTransport(array $transportConfig, Application $application): HttpTransport
    {
        // Extract base URL from transport config
        if (! isset($transportConfig['base_url']) || ! is_string($transportConfig['base_url'])) {
            throw ValidationError::invalidFieldValue(
                'base_url',
                'HTTP transport requires base_url',
                ['transportConfig' => $transportConfig]
            );
        }

        // Create HTTP config with all parameters
        $httpConfig = new HttpConfig(
            $transportConfig['base_url'],
            $transportConfig['timeout'] ?? HttpConfig::DEFAULTS['timeout'],
            $transportConfig['sse_timeout'] ?? HttpConfig::DEFAULTS['sse_timeout'],
            $transportConfig['max_retries'] ?? HttpConfig::DEFAULTS['max_retries'],
            $transportConfig['retry_delay'] ?? HttpConfig::DEFAULTS['retry_delay'],
            $transportConfig['validate_ssl'] ?? HttpConfig::DEFAULTS['validate_ssl'],
            $transportConfig['user_agent'] ?? HttpConfig::DEFAULTS['user_agent'],
            $transportConfig['headers'] ?? HttpConfig::DEFAULTS['headers'],
            $transportConfig['auth'] ?? HttpConfig::DEFAULTS['auth'],
            $transportConfig['protocol_version'] ?? HttpConfig::DEFAULTS['protocol_version'],
            $transportConfig['enable_resumption'] ?? HttpConfig::DEFAULTS['enable_resumption'],
            $transportConfig['event_store_type'] ?? HttpConfig::DEFAULTS['event_store_type'],
            $transportConfig['event_store_config'] ?? HttpConfig::DEFAULTS['event_store_config'],
            $transportConfig['json_response_mode'] ?? HttpConfig::DEFAULTS['json_response_mode'],
            $transportConfig['terminate_on_close'] ?? HttpConfig::DEFAULTS['terminate_on_close']
        );

        return new HttpTransport($httpConfig, $application);
    }

    /**
     * Validate transport type.
     *
     * @param string $type The transport type to validate
     * @throws ValidationError If type is invalid
     */
    private static function validateTransportType(string $type): void
    {
        if (empty($type)) {
            throw ValidationError::emptyField('transportType');
        }

        // Use protocol constants for validation
        $knownTypes = [
            TransportTypes::TRANSPORT_TYPE_STDIO,
            TransportTypes::TRANSPORT_TYPE_HTTP,
        ];

        if (! in_array($type, $knownTypes, true)) {
            throw ValidationError::invalidFieldValue(
                'transportType',
                'Unknown transport type',
                [
                    'type' => $type,
                    'known' => $knownTypes,
                ]
            );
        }
    }
}
