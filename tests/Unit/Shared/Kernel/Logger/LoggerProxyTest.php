<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Shared\Kernel\Logger;

use Dtyq\PhpMcp\Shared\Kernel\Logger\LoggerProxy;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Test case for LoggerProxy class.
 * @internal
 */
class LoggerProxyTest extends TestCase
{
    /** @var LoggerInterface&MockObject */
    private $mockLogger;

    private string $sdkName = 'test-sdk';

    protected function setUp(): void
    {
        $this->mockLogger = $this->createMock(LoggerInterface::class);
    }

    public function testConstructorWithLogger(): void
    {
        $proxy = new LoggerProxy($this->sdkName, $this->mockLogger);

        $this->assertInstanceOf(LoggerProxy::class, $proxy);
    }

    public function testConstructorWithoutLogger(): void
    {
        $proxy = new LoggerProxy($this->sdkName);

        $this->assertInstanceOf(LoggerProxy::class, $proxy);
    }

    public function testEmergencyCall(): void
    {
        $message = 'Emergency message';
        $context = ['key' => 'value'];
        $expectedMessage = "[{$this->sdkName}] {$message}";

        $this->mockLogger->expects($this->once())
            ->method('emergency')
            ->with($expectedMessage, $context);

        $proxy = new LoggerProxy($this->sdkName, $this->mockLogger);
        $proxy->emergency($message, $context);
    }

    public function testAlertCall(): void
    {
        $message = 'Alert message';
        $context = ['alert' => true];
        $expectedMessage = "[{$this->sdkName}] {$message}";

        $this->mockLogger->expects($this->once())
            ->method('alert')
            ->with($expectedMessage, $context);

        $proxy = new LoggerProxy($this->sdkName, $this->mockLogger);
        $proxy->alert($message, $context);
    }

    public function testCriticalCall(): void
    {
        $message = 'Critical message';
        $expectedMessage = "[{$this->sdkName}] {$message}";

        $this->mockLogger->expects($this->once())
            ->method('critical')
            ->with($expectedMessage, []);

        $proxy = new LoggerProxy($this->sdkName, $this->mockLogger);
        $proxy->critical($message);
    }

    public function testErrorCall(): void
    {
        $message = 'Error message';
        $context = ['error_code' => 500];
        $expectedMessage = "[{$this->sdkName}] {$message}";

        $this->mockLogger->expects($this->once())
            ->method('error')
            ->with($expectedMessage, $context);

        $proxy = new LoggerProxy($this->sdkName, $this->mockLogger);
        $proxy->error($message, $context);
    }

    public function testWarningCall(): void
    {
        $message = 'Warning message';
        $expectedMessage = "[{$this->sdkName}] {$message}";

        $this->mockLogger->expects($this->once())
            ->method('warning')
            ->with($expectedMessage, []);

        $proxy = new LoggerProxy($this->sdkName, $this->mockLogger);
        $proxy->warning($message);
    }

    public function testNoticeCall(): void
    {
        $message = 'Notice message';
        $context = ['notice' => 'info'];
        $expectedMessage = "[{$this->sdkName}] {$message}";

        $this->mockLogger->expects($this->once())
            ->method('notice')
            ->with($expectedMessage, $context);

        $proxy = new LoggerProxy($this->sdkName, $this->mockLogger);
        $proxy->notice($message, $context);
    }

    public function testInfoCall(): void
    {
        $message = 'Info message';
        $expectedMessage = "[{$this->sdkName}] {$message}";

        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with($expectedMessage, []);

        $proxy = new LoggerProxy($this->sdkName, $this->mockLogger);
        $proxy->info($message);
    }

    public function testDebugCall(): void
    {
        $message = 'Debug message';
        $context = ['debug' => true, 'level' => 1];
        $expectedMessage = "[{$this->sdkName}] {$message}";

        $this->mockLogger->expects($this->once())
            ->method('debug')
            ->with($expectedMessage, $context);

        $proxy = new LoggerProxy($this->sdkName, $this->mockLogger);
        $proxy->debug($message, $context);
    }

    public function testLogCallWithoutLogger(): void
    {
        $proxy = new LoggerProxy($this->sdkName, null);

        // Should not throw any exception when logger is null
        $proxy->info('Test message');
        $proxy->error('Error message', ['context' => 'data']);

        $this->assertTrue(true); // Test passes if no exception is thrown
    }

    public function testLogCallWithLoggerThatDoesNotHaveMethod(): void
    {
        /** @var LoggerInterface&MockObject $mockLogger */
        $mockLogger = $this->createMock(LoggerInterface::class);

        // Mock a logger that doesn't have the 'collect' method
        $mockLogger->expects($this->never())
            ->method($this->anything());

        $proxy = new LoggerProxy($this->sdkName, $mockLogger);

        // Should not throw exception for non-existent method
        $proxy->collect('Collection message');

        $this->assertTrue(true); // Test passes if no exception is thrown
    }

    public function testMessagePrefixing(): void
    {
        $sdkName = 'custom-sdk-name';
        $message = 'Test message';
        $expectedMessage = "[{$sdkName}] {$message}";

        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with($expectedMessage, []);

        $proxy = new LoggerProxy($sdkName, $this->mockLogger);
        $proxy->info($message);
    }

    public function testMultipleArgumentsHandling(): void
    {
        $message = 'Message with multiple args';
        $context = ['key1' => 'value1', 'key2' => 'value2'];
        $extraArg = 'extra';
        $expectedMessage = "[{$this->sdkName}] {$message}";

        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with($expectedMessage, $context, $extraArg);

        $proxy = new LoggerProxy($this->sdkName, $this->mockLogger);
        $proxy->info($message, $context, $extraArg);
    }

    public function testEmptyMessageHandling(): void
    {
        $message = '';
        $expectedMessage = "[{$this->sdkName}] ";

        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with($expectedMessage, []);

        $proxy = new LoggerProxy($this->sdkName, $this->mockLogger);
        $proxy->info($message);
    }

    public function testSpecialCharactersInSdkName(): void
    {
        $sdkName = 'sdk-name_with.special@chars';
        $message = 'Test message';
        $expectedMessage = "[{$sdkName}] {$message}";

        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with($expectedMessage, []);

        $proxy = new LoggerProxy($sdkName, $this->mockLogger);
        $proxy->info($message);
    }
}
