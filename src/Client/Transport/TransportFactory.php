<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client\Transport;

use Dtyq\PhpMcp\Client\Configuration\HttpConfig;
use Dtyq\PhpMcp\Client\Configuration\StdioConfig;
use Dtyq\PhpMcp\Client\Configuration\TransportConfigInterface;
use Dtyq\PhpMcp\Client\Core\TransportInterface;
use Dtyq\PhpMcp\Client\Transport\Http\HttpTransport;
use Dtyq\PhpMcp\Client\Transport\Stdio\StdioTransport;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Dtyq\PhpMcp\Types\Constants\TransportTypes;

/**
 * Factory for creating transport instances.
 * 创建传输实例的工厂。
 *
 * This factory implements the factory method pattern to create
 * appropriate transport instances based on the requested type,
 * with support for multiple transport protocols including stdio and HTTP.
 * 此工厂实现工厂方法模式，根据请求的类型创建适当的传输实例，
 * 支持包括 stdio 和 HTTP 在内的多种传输协议。
 */
class TransportFactory
{
    /**
     * Create a transport instance from transport configuration.
     * 从传输配置创建传输实例。
     *
     * @param TransportConfigInterface $config Transport configuration / 传输配置
     * @param Application $application Application instance for services / 应用程序实例
     * @return TransportInterface The created transport instance / 创建的传输实例
     * @throws ValidationError If transport type is invalid or configuration is invalid / 如果传输类型无效或配置无效
     */
    public static function create(TransportConfigInterface $config, Application $application): TransportInterface
    {
        // Get transport type from configuration
        $transportType = $config->getTransportType();

        // Create transport based on type
        switch ($transportType) {
            case TransportTypes::TRANSPORT_TYPE_STDIO:
                return self::createStdioTransport($config, $application);
            case TransportTypes::TRANSPORT_TYPE_HTTP:
                return self::createHttpTransport($config, $application);
            default:
                throw ValidationError::invalidFieldValue(
                    'transportType',
                    'Unsupported transport type from configuration',
                    ['type' => $transportType, 'configClass' => get_class($config)]
                );
        }
    }

    /**
     * Create stdio transport instance.
     *
     * @param TransportConfigInterface $config Transport configuration object / 传输配置对象
     * @param Application $application Application instance / 应用程序实例
     * @return StdioTransport Created transport instance / 创建的传输实例
     * @throws ValidationError If configuration is invalid / 如果配置无效
     */
    private static function createStdioTransport(TransportConfigInterface $config, Application $application): StdioTransport
    {
        // Ensure we have a StdioConfig instance
        if (! $config instanceof StdioConfig) {
            throw ValidationError::invalidFieldValue(
                'config',
                'Expected StdioConfig instance for stdio transport',
                ['actualType' => get_class($config)]
            );
        }

        return new StdioTransport($config, $application);
    }

    /**
     * Create HTTP transport instance.
     *
     * @param TransportConfigInterface $config Transport configuration object / 传输配置对象
     * @param Application $application Application instance / 应用程序实例
     * @return HttpTransport Created transport instance / 创建的传输实例
     * @throws ValidationError If configuration is invalid / 如果配置无效
     */
    private static function createHttpTransport(TransportConfigInterface $config, Application $application): HttpTransport
    {
        // Ensure we have an HttpConfig instance
        if (! $config instanceof HttpConfig) {
            throw ValidationError::invalidFieldValue(
                'config',
                'Expected HttpConfig instance for HTTP transport',
                ['actualType' => get_class($config)]
            );
        }

        return new HttpTransport($config, $application);
    }
}
