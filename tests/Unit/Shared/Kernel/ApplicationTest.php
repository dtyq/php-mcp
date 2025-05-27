<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Shared\Kernel;

use Dtyq\PhpMcp\Shared\Exceptions\SystemException;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Dtyq\PhpMcp\Shared\Kernel\Config\Config;
use Dtyq\PhpMcp\Shared\Kernel\Logger\LoggerProxy;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use stdClass;

/**
 * Test case for Application class.
 * @internal
 */
class ApplicationTest extends TestCase
{
    /** @var ContainerInterface&MockObject */
    private $container;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
    }

    public function testConstructorWithEmptyConfig(): void
    {
        $configs = [];
        $application = new Application($this->container, $configs);

        $this->assertInstanceOf(Application::class, $application);
        $this->assertInstanceOf(Config::class, $application->getConfig());
    }

    public function testConstructorWithCustomConfig(): void
    {
        $configs = [
            'app_name' => 'test-app',
            'debug' => true,
            'custom_setting' => 'value',
        ];
        $application = new Application($this->container, $configs);

        $config = $application->getConfig();
        $this->assertSame('test-app', $config->get('app_name'));
        $this->assertTrue($config->get('debug'));
        $this->assertSame('value', $config->get('custom_setting'));
    }

    public function testGetConfig(): void
    {
        $configs = ['test_key' => 'test_value'];
        $application = new Application($this->container, $configs);

        $config = $application->getConfig();
        $this->assertInstanceOf(Config::class, $config);
        $this->assertSame('test_value', $config->get('test_key'));
        $this->assertSame('php-mcp', $config->getSdkName());
    }

    public function testGetLoggerWithValidLogger(): void
    {
        $mockLogger = $this->createMock(LoggerInterface::class);

        $this->container->expects($this->once())
            ->method('get')
            ->with(LoggerInterface::class)
            ->willReturn($mockLogger);

        $application = new Application($this->container, []);
        $logger = $application->getLogger();

        $this->assertInstanceOf(LoggerProxy::class, $logger);
    }

    public function testGetLoggerWithInvalidLogger(): void
    {
        $invalidLogger = new stdClass();

        $this->container->expects($this->once())
            ->method('get')
            ->with(LoggerInterface::class)
            ->willReturn($invalidLogger);

        $application = new Application($this->container, []);

        $this->expectException(SystemException::class);
        $this->expectExceptionMessage('Logger Must Be An Instance Of Psr\Log\LoggerInterface');
        $application->getLogger();
    }

    public function testGetLoggerReturnsSameInstance(): void
    {
        $mockLogger = $this->createMock(LoggerInterface::class);

        $this->container->expects($this->once())
            ->method('get')
            ->with(LoggerInterface::class)
            ->willReturn($mockLogger);

        $application = new Application($this->container, []);
        $logger1 = $application->getLogger();
        $logger2 = $application->getLogger();

        $this->assertSame($logger1, $logger2);
    }

    public function testGetCacheWithValidCache(): void
    {
        $mockCache = $this->createMock(CacheInterface::class);

        $this->container->expects($this->once())
            ->method('get')
            ->with(CacheInterface::class)
            ->willReturn($mockCache);

        $application = new Application($this->container, []);
        $cache = $application->getCache();

        $this->assertInstanceOf(CacheInterface::class, $cache);
        $this->assertSame($mockCache, $cache);
    }

    public function testGetCacheWithInvalidCache(): void
    {
        $invalidCache = new stdClass();

        $this->container->expects($this->once())
            ->method('get')
            ->with(CacheInterface::class)
            ->willReturn($invalidCache);

        $application = new Application($this->container, []);

        $this->expectException(SystemException::class);
        $this->expectExceptionMessage('Cache Must Be An Instance Of Psr\SimpleCache\CacheInterface');
        $application->getCache();
    }

    public function testGetCacheReturnsSameInstance(): void
    {
        $mockCache = $this->createMock(CacheInterface::class);

        $this->container->expects($this->once())
            ->method('get')
            ->with(CacheInterface::class)
            ->willReturn($mockCache);

        $application = new Application($this->container, []);
        $cache1 = $application->getCache();
        $cache2 = $application->getCache();

        $this->assertSame($cache1, $cache2);
    }

    public function testGetEventDispatcherWithValidDispatcher(): void
    {
        $mockDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->container->expects($this->once())
            ->method('get')
            ->with(EventDispatcherInterface::class)
            ->willReturn($mockDispatcher);

        $application = new Application($this->container, []);
        $dispatcher = $application->getEventDispatcher();

        $this->assertInstanceOf(EventDispatcherInterface::class, $dispatcher);
        $this->assertSame($mockDispatcher, $dispatcher);
    }

    public function testGetEventDispatcherWithInvalidDispatcher(): void
    {
        $invalidDispatcher = new stdClass();

        $this->container->expects($this->once())
            ->method('get')
            ->with(EventDispatcherInterface::class)
            ->willReturn($invalidDispatcher);

        $application = new Application($this->container, []);

        $this->expectException(SystemException::class);
        $this->expectExceptionMessage('Event Dispatcher Must Be An Instance Of Psr\EventDispatcher\EventDispatcherInterface');
        $application->getEventDispatcher();
    }

    public function testGetEventDispatcherReturnsSameInstance(): void
    {
        $mockDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->container->expects($this->once())
            ->method('get')
            ->with(EventDispatcherInterface::class)
            ->willReturn($mockDispatcher);

        $application = new Application($this->container, []);
        $dispatcher1 = $application->getEventDispatcher();
        $dispatcher2 = $application->getEventDispatcher();

        $this->assertSame($dispatcher1, $dispatcher2);
    }

    public function testAllServicesWorkTogether(): void
    {
        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockCache = $this->createMock(CacheInterface::class);
        $mockDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->container->expects($this->exactly(3))
            ->method('get')
            ->willReturnMap([
                [LoggerInterface::class, $mockLogger],
                [CacheInterface::class, $mockCache],
                [EventDispatcherInterface::class, $mockDispatcher],
            ]);

        $configs = ['app_name' => 'integration-test'];
        $application = new Application($this->container, $configs);

        // Test all services can be retrieved
        $config = $application->getConfig();
        $logger = $application->getLogger();
        $cache = $application->getCache();
        $dispatcher = $application->getEventDispatcher();

        $this->assertInstanceOf(Config::class, $config);
        $this->assertInstanceOf(LoggerProxy::class, $logger);
        $this->assertInstanceOf(CacheInterface::class, $cache);
        $this->assertInstanceOf(EventDispatcherInterface::class, $dispatcher);

        $this->assertSame('integration-test', $config->get('app_name'));
    }
}
