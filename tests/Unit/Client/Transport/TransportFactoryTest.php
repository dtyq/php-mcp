<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Client\Transport;

use Dtyq\PhpMcp\Client\Configuration\HttpConfig;
use Dtyq\PhpMcp\Client\Configuration\StdioConfig;
use Dtyq\PhpMcp\Client\Configuration\TransportConfigInterface;
use Dtyq\PhpMcp\Client\Transport\Http\HttpTransport;
use Dtyq\PhpMcp\Client\Transport\Stdio\StdioTransport;
use Dtyq\PhpMcp\Client\Transport\TransportFactory;
use Dtyq\PhpMcp\Shared\Exceptions\SystemException;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

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

    public function testCreateInvalidTransport(): void
    {
        // Create a mock config that returns an invalid transport type
        /** @var MockObject|TransportConfigInterface $config */
        $config = $this->getMockBuilder(TransportConfigInterface::class)->getMock();
        $config->expects($this->once())->method('getTransportType')->willReturn('invalid');

        $this->expectException(ValidationError::class);
        TransportFactory::create($config, $this->application);
    }

    public function testCreateStdioTransportWithMissingCommand(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Field \'command\' cannot be empty');

        // This should throw an exception when validating
        $config = new StdioConfig([]);
        $config->validate();
    }

    public function testCreateStdioTransportWithTimeouts(): void
    {
        $config = new StdioConfig(
            ['php', '-v'],
            null,  // args
            10.0,  // read_timeout
            5.0,   // write_timeout
            3.0,   // shutdown_timeout
            8192   // buffer_size
        );

        $transport = TransportFactory::create($config, $this->application);

        $this->assertInstanceOf(StdioTransport::class, $transport);
    }

    public function testCreateHttpTransportWithConfiguration(): void
    {
        $config = new HttpConfig(
            'https://example.com/mcp',
            30.0,  // timeout
            60.0,  // sse_timeout
            5      // max_retries
        );

        $transport = TransportFactory::create($config, $this->application);

        $this->assertInstanceOf(HttpTransport::class, $transport);
    }

    public function testCreateWithStdioConfig(): void
    {
        $config = new StdioConfig(['php', '-v']);
        $transport = TransportFactory::create($config, $this->application);

        $this->assertInstanceOf(StdioTransport::class, $transport);
    }

    public function testCreateWithHttpConfig(): void
    {
        $config = new HttpConfig('https://example.com/mcp');
        $transport = TransportFactory::create($config, $this->application);

        $this->assertInstanceOf(HttpTransport::class, $transport);
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
                        throw new SystemException("Service not found: {$id}");
                }
            }

            public function has($id): bool
            {
                return in_array($id, [LoggerInterface::class, EventDispatcherInterface::class], true);
            }
        };
    }
}
