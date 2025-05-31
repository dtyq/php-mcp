<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Client\Transport;

use Dtyq\PhpMcp\Client\Configuration\ClientConfig;
use Dtyq\PhpMcp\Client\Core\TransportInterface;
use Dtyq\PhpMcp\Client\Transport\Stdio\StdioTransport;
use Dtyq\PhpMcp\Client\Transport\TransportFactory;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Shared\Kernel\Application;
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
            'stdio',
            [
                'command' => ['php', '-v'], // Simple command that should work
            ]
        );

        $transport = TransportFactory::create('stdio', $config, $this->application);

        $this->assertInstanceOf(StdioTransport::class, $transport);
        $this->assertEquals('stdio', $transport->getType());
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

        $config = new ClientConfig('stdio', []);

        TransportFactory::create('', $config, $this->application);
    }

    public function testCreateStdioWithoutCommand(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Stdio transport requires command array');

        $config = new ClientConfig('stdio', []);

        TransportFactory::create('stdio', $config, $this->application);
    }

    public function testCreateStdioWithInvalidCommand(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Stdio transport requires command array');

        $config = new ClientConfig('stdio', [
            'command' => 'not-an-array', // Should be array
        ]);

        TransportFactory::create('stdio', $config, $this->application);
    }

    public function testGetSupportedTypes(): void
    {
        $types = TransportFactory::getSupportedTypes();

        $this->assertIsArray($types);
        $this->assertContains('stdio', $types);
    }

    public function testIsSupported(): void
    {
        $this->assertTrue(TransportFactory::isSupported('stdio'));
        $this->assertFalse(TransportFactory::isSupported('invalid-type'));
        $this->assertFalse(TransportFactory::isSupported('http')); // Not implemented yet
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

    public function testCreateDefaultConfig(): void
    {
        $config = TransportFactory::createDefaultConfig('stdio');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('read_timeout', $config);
        $this->assertArrayHasKey('write_timeout', $config);
        $this->assertArrayHasKey('shutdown_timeout', $config);
    }

    public function testCreateDefaultConfigWithOverrides(): void
    {
        $overrides = [
            'read_timeout' => 60.0,
            'custom_option' => 'value',
        ];

        $config = TransportFactory::createDefaultConfig('stdio', $overrides);

        $this->assertIsArray($config);
        $this->assertEquals(60.0, $config['read_timeout']);
        $this->assertEquals('value', $config['custom_option']);

        // Should still have other defaults
        $this->assertArrayHasKey('write_timeout', $config);
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
            /** @var array<string, object> */
            private array $services = [];

            public function __construct()
            {
                $this->services[LoggerInterface::class] = new NullLogger();
                $this->services[EventDispatcherInterface::class] = new class implements EventDispatcherInterface {
                    public function dispatch(object $event): object
                    {
                        return $event;
                    }
                };
            }

            public function get($id)
            {
                return $this->services[$id] ?? null;
            }

            public function has($id): bool
            {
                return isset($this->services[$id]);
            }
        };
    }
}
