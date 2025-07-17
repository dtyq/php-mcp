<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Client\Transport;

use Dtyq\PhpMcp\Client\Configuration\ClientConfig;
use Dtyq\PhpMcp\Client\Core\TransportInterface;
use Dtyq\PhpMcp\Client\Transport\Http\HttpTransport;
use Dtyq\PhpMcp\Client\Transport\Stdio\StdioTransport;
use Dtyq\PhpMcp\Client\Transport\TransportFactory;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Dtyq\PhpMcp\Types\Constants\TransportTypes;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use stdClass;

/**
 * Test case for TransportFactory.
 * @internal
 */
class TransportFactoryTest extends TestCase
{
    private Application $application;

    protected function setUp(): void
    {
        $container = $this->createMockContainer();
        $this->application = new Application($container, [
            'sdk_name' => 'test-client',
        ]);
    }

    public function testCreateStdioTransport(): void
    {
        $config = new ClientConfig(
            TransportTypes::TRANSPORT_TYPE_STDIO,
            [
                'command' => ['php', '-v'], // Simple command that should work
            ]
        );

        $transport = TransportFactory::create(TransportTypes::TRANSPORT_TYPE_STDIO, $config, $this->application);

        $this->assertInstanceOf(StdioTransport::class, $transport);
        $this->assertEquals(TransportTypes::TRANSPORT_TYPE_STDIO, $transport->getType());
    }

    public function testCreateWithInvalidTransportType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Unknown transport type');

        $config = new ClientConfig('invalid', []);

        TransportFactory::create('invalid', $config, $this->application);
    }

    public function testCreateWithEmptyTransportType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('transportType');

        $config = new ClientConfig(TransportTypes::TRANSPORT_TYPE_STDIO, []);

        TransportFactory::create('', $config, $this->application);
    }

    public function testCreateStdioWithoutCommand(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Stdio transport requires command array');

        $config = new ClientConfig(TransportTypes::TRANSPORT_TYPE_STDIO, []);

        TransportFactory::create(TransportTypes::TRANSPORT_TYPE_STDIO, $config, $this->application);
    }

    public function testCreateStdioWithInvalidCommand(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Stdio transport requires command array');

        $config = new ClientConfig(TransportTypes::TRANSPORT_TYPE_STDIO, [
            'command' => 'not-an-array', // Should be arrayed
        ]);

        TransportFactory::create(TransportTypes::TRANSPORT_TYPE_STDIO, $config, $this->application);
    }

    public function testCreateHttpWithoutBaseUrl(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('HTTP transport requires base_url');

        $config = new ClientConfig(TransportTypes::TRANSPORT_TYPE_HTTP, []);

        TransportFactory::create(TransportTypes::TRANSPORT_TYPE_HTTP, $config, $this->application);
    }

    public function testCreateHttpWithInvalidBaseUrl(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('HTTP transport requires base_url');

        $config = new ClientConfig(TransportTypes::TRANSPORT_TYPE_HTTP, [
            'base_url' => 123, // Should be string
        ]);

        TransportFactory::create(TransportTypes::TRANSPORT_TYPE_HTTP, $config, $this->application);
    }

    public function testCreateHttpTransport(): void
    {
        $config = new ClientConfig(
            TransportTypes::TRANSPORT_TYPE_HTTP,
            [
                'base_url' => 'https://example.com/mcp',
                'timeout' => 60.0,
                'max_retries' => 5,
                'validate_ssl' => false,
            ]
        );

        $transport = TransportFactory::create(TransportTypes::TRANSPORT_TYPE_HTTP, $config, $this->application);

        $this->assertInstanceOf(HttpTransport::class, $transport);
        $this->assertEquals(TransportTypes::TRANSPORT_TYPE_HTTP, $transport->getType());
    }

    public function testCreateHttpTransportWithMinimalConfig(): void
    {
        $config = new ClientConfig(
            TransportTypes::TRANSPORT_TYPE_HTTP,
            [
                'base_url' => 'https://example.com/mcp',
            ]
        );

        $transport = TransportFactory::create(TransportTypes::TRANSPORT_TYPE_HTTP, $config, $this->application);

        $this->assertInstanceOf(HttpTransport::class, $transport);
        $this->assertEquals(TransportTypes::TRANSPORT_TYPE_HTTP, $transport->getType());
    }

    public function testGetSupportedTypes(): void
    {
        $types = TransportFactory::getSupportedTypes();

        $this->assertIsArray($types);
        $this->assertContains(TransportTypes::TRANSPORT_TYPE_STDIO, $types);
        $this->assertContains(TransportTypes::TRANSPORT_TYPE_HTTP, $types);
    }

    public function testIsSupported(): void
    {
        $this->assertTrue(TransportFactory::isSupported(TransportTypes::TRANSPORT_TYPE_STDIO));
        $this->assertTrue(TransportFactory::isSupported(TransportTypes::TRANSPORT_TYPE_HTTP));
        $this->assertFalse(TransportFactory::isSupported('invalid-type'));
        $this->assertFalse(TransportFactory::isSupported('websocket')); // Not implemented yet
    }

    public function testRegisterTransport(): void
    {
        // Create a mock transport class
        $mockTransportClass = new class implements TransportInterface {
            public function connect(): void
            {
            }

            public function send(string $message): void
            {
            }

            public function receive(?int $timeout = null): ?string
            {
                return null;
            }

            public function isConnected(): bool
            {
                return false;
            }

            public function disconnect(): void
            {
            }

            public function getType(): string
            {
                return 'mock';
            }
        };

        $className = get_class($mockTransportClass);

        TransportFactory::registerTransport('mock', $className);

        $this->assertTrue(TransportFactory::isSupported('mock'));
        $this->assertContains('mock', TransportFactory::getSupportedTypes());
    }

    public function testRegisterTransportWithNonExistentClass(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Class does not exist');

        TransportFactory::registerTransport('test', 'NonExistentClass');
    }

    public function testRegisterTransportWithInvalidInterface(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Class must implement TransportInterface');

        TransportFactory::registerTransport('test', stdClass::class);
    }

    public function testCreateDefaultConfigStdio(): void
    {
        $config = TransportFactory::createDefaultConfig(TransportTypes::TRANSPORT_TYPE_STDIO);

        $this->assertIsArray($config);
        $this->assertArrayHasKey('read_timeout', $config);
        $this->assertArrayHasKey('write_timeout', $config);
        $this->assertArrayHasKey('shutdown_timeout', $config);
    }

    public function testCreateDefaultConfigHttp(): void
    {
        $config = TransportFactory::createDefaultConfig(TransportTypes::TRANSPORT_TYPE_HTTP);

        $this->assertIsArray($config);
        $this->assertArrayHasKey('timeout', $config);
        $this->assertArrayHasKey('sse_timeout', $config);
        $this->assertArrayHasKey('max_retries', $config);
        $this->assertArrayHasKey('retry_delay', $config);
        $this->assertArrayHasKey('validate_ssl', $config);
        $this->assertArrayHasKey('user_agent', $config);
        $this->assertArrayHasKey('headers', $config);
        $this->assertArrayHasKey('auth', $config);
        $this->assertArrayHasKey('protocol_version', $config);
        $this->assertArrayHasKey('enable_resumption', $config);
        $this->assertArrayHasKey('event_store_type', $config);
    }

    public function testCreateDefaultConfigWithOverrides(): void
    {
        $overrides = [
            'read_timeout' => 60.0,
            'custom_option' => 'value',
        ];

        $config = TransportFactory::createDefaultConfig(TransportTypes::TRANSPORT_TYPE_STDIO, $overrides);

        $this->assertIsArray($config);
        $this->assertEquals(60.0, $config['read_timeout']);
        $this->assertEquals('value', $config['custom_option']);

        // Should still have other defaults
        $this->assertArrayHasKey('write_timeout', $config);
    }

    public function testCreateDefaultConfigHttpWithOverrides(): void
    {
        $overrides = [
            'timeout' => 60.0,
            'max_retries' => 5,
            'custom_option' => 'value',
        ];

        $config = TransportFactory::createDefaultConfig(TransportTypes::TRANSPORT_TYPE_HTTP, $overrides);

        $this->assertIsArray($config);
        $this->assertEquals(60.0, $config['timeout']);
        $this->assertEquals(5, $config['max_retries']);
        $this->assertEquals('value', $config['custom_option']);

        // Should still have other defaults
        $this->assertArrayHasKey('sse_timeout', $config);
        $this->assertArrayHasKey('validate_ssl', $config);
    }

    public function testCreateDefaultConfigWithUnsupportedType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Unknown transport type');

        TransportFactory::createDefaultConfig('unsupported');
    }

    private function createMockContainer(): ContainerInterface
    {
        return new class implements ContainerInterface {
            private EventDispatcherInterface $eventDispatcher;

            public function __construct()
            {
                $this->eventDispatcher = new class implements EventDispatcherInterface {
                    public function dispatch(object $event): object
                    {
                        return $event;
                    }
                };
            }

            public function get($id)
            {
                switch ($id) {
                    case LoggerInterface::class:
                        return new NullLogger();
                    case EventDispatcherInterface::class:
                        return $this->eventDispatcher;
                    default:
                        throw new Exception("Service not found: {$id}");
                }
            }

            public function has($id): bool
            {
                return in_array($id, [LoggerInterface::class, EventDispatcherInterface::class], true);
            }
        };
    }
}
