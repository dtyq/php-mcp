<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Shared\Logging;

use Dtyq\PhpMcp\Shared\Logging\HandlerInterface;
use Dtyq\PhpMcp\Shared\Logging\LogRecord;
use Dtyq\PhpMcp\Shared\Logging\McpLogger;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use ReflectionClass;

/**
 * Tests for McpLogger class.
 * @internal
 */
class McpLoggerTest extends TestCase
{
    private McpLogger $logger;

    /** @var HandlerInterface&MockInterface */
    private $mockHandler;

    protected function setUp(): void
    {
        $this->mockHandler = Mockery::mock(HandlerInterface::class);
        $this->logger = new McpLogger('test', LogLevel::DEBUG, [$this->mockHandler]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testBasicLogging(): void
    {
        $this->mockHandler
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::type(LogRecord::class))
            ->andReturn(true);

        $this->logger->info('Test message');

        // Add assertion to avoid risky test
        $this->assertTrue(true);
    }

    public function testLogLevelFiltering(): void
    {
        $logger = new McpLogger('test', LogLevel::ERROR, [$this->mockHandler]);

        // Should not call handler for debug/info messages
        $this->mockHandler
            ->shouldReceive('handle')
            ->never();

        $logger->debug('Debug message');
        $logger->info('Info message');

        // Add assertion to avoid risky test
        $this->assertTrue(true);
    }

    public function testLogLevelPassing(): void
    {
        $this->mockHandler
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::type(LogRecord::class))
            ->andReturn(true);

        $this->logger->error('Error message');

        // Add assertion to avoid risky test
        $this->assertTrue(true);
    }

    public function testLogWithContext(): void
    {
        $this->mockHandler
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(function (LogRecord $record) {
                return $record->getContext() === ['user' => 'john', 'action' => 'login'];
            }))
            ->andReturn(true);

        $this->logger->info('User logged in', ['user' => 'john', 'action' => 'login']);

        // Add assertion to avoid risky test
        $this->assertTrue(true);
    }

    public function testGlobalContext(): void
    {
        $this->logger->setGlobalContext(['app' => 'test', 'version' => '1.0']);

        $this->mockHandler
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(function (LogRecord $record) {
                $context = $record->getContext();
                return $context['app'] === 'test'
                    && $context['version'] === '1.0'
                    && $context['user'] === 'john';
            }))
            ->andReturn(true);

        $this->logger->info('Test', ['user' => 'john']);

        // Add assertion to avoid risky test
        $this->assertTrue(true);
    }

    public function testAddGlobalContext(): void
    {
        $this->logger->setGlobalContext(['app' => 'test']);
        $this->logger->addGlobalContext(['version' => '1.0']);

        $this->mockHandler
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(function (LogRecord $record) {
                $context = $record->getContext();
                return $context['app'] === 'test' && $context['version'] === '1.0';
            }))
            ->andReturn(true);

        $this->logger->info('Test');

        // Add assertion to avoid risky test
        $this->assertTrue(true);
    }

    public function testWithContext(): void
    {
        $childLogger = $this->logger->withContext(['session' => 'abc123']);

        $this->mockHandler
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(function (LogRecord $record) {
                return $record->getContext()['session'] === 'abc123';
            }))
            ->andReturn(true);

        $childLogger->info('Test');

        // Add assertion to avoid risky test
        $this->assertTrue(true);
    }

    public function testForSession(): void
    {
        $sessionLogger = $this->logger->forSession('session-123');

        // Verify that the session logger is a different instance
        $this->assertNotSame($this->logger, $sessionLogger);

        // Verify that the session logger has the correct session ID in global context
        $reflection = new ReflectionClass($sessionLogger);
        $property = $reflection->getProperty('globalContext');
        $property->setAccessible(true);
        $globalContext = $property->getValue($sessionLogger);

        $this->assertEquals('session-123', $globalContext['session_id']);
    }

    public function testForRequest(): void
    {
        $requestLogger = $this->logger->forRequest('req-456');

        // Verify that the request logger is a different instance
        $this->assertNotSame($this->logger, $requestLogger);

        // Verify that the request logger has the correct request ID in global context
        $reflection = new ReflectionClass($requestLogger);
        $property = $reflection->getProperty('globalContext');
        $property->setAccessible(true);
        $globalContext = $property->getValue($requestLogger);

        $this->assertEquals('req-456', $globalContext['request_id']);
    }

    public function testLogMcpMessage(): void
    {
        $message = ['method' => 'initialize', 'id' => '123'];

        $this->mockHandler
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(function (LogRecord $record) {
                $context = $record->getContext();
                return $context['mcp_direction'] === 'inbound'
                    && $context['mcp_method'] === 'initialize'
                    && $context['mcp_id'] === '123';
            }))
            ->andReturn(true);

        $this->logger->logMcpMessage(LogLevel::INFO, 'inbound', $message);

        // Add assertion to avoid risky test
        $this->assertTrue(true);
    }

    public function testLogRequest(): void
    {
        $request = ['method' => 'tools/list', 'id' => '1'];

        $this->mockHandler
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(function (LogRecord $record) {
                $context = $record->getContext();
                return $context['mcp_direction'] === 'inbound'
                    && $context['mcp_method'] === 'tools/list';
            }))
            ->andReturn(true);

        $this->logger->logRequest($request);

        // Add assertion to avoid risky test
        $this->assertTrue(true);
    }

    public function testLogResponse(): void
    {
        $response = ['id' => '1', 'result' => []];

        $this->mockHandler
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(function (LogRecord $record) {
                $context = $record->getContext();
                return $context['mcp_direction'] === 'outbound';
            }))
            ->andReturn(true);

        $this->logger->logResponse($response);

        // Add assertion to avoid risky test
        $this->assertTrue(true);
    }

    public function testLogNotification(): void
    {
        $notification = ['method' => 'ping'];

        $this->mockHandler
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(function (LogRecord $record) {
                $context = $record->getContext();
                return $context['mcp_direction'] === 'notification'
                    && $context['mcp_method'] === 'ping';
            }))
            ->andReturn(true);

        $this->logger->logNotification($notification);

        // Add assertion to avoid risky test
        $this->assertTrue(true);
    }

    public function testLogError(): void
    {
        $error = ['code' => -32601, 'message' => 'Method not found'];

        $this->mockHandler
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(function (LogRecord $record) {
                return $record->getLevel() === LogLevel::ERROR
                    && $record->getContext()['mcp_direction'] === 'error';
            }))
            ->andReturn(true);

        $this->logger->logError($error);

        // Add assertion to avoid risky test
        $this->assertTrue(true);
    }

    public function testIsHandling(): void
    {
        $logger = new McpLogger('test', LogLevel::WARNING);

        $this->assertTrue($logger->isHandling(LogLevel::WARNING));
        $this->assertTrue($logger->isHandling(LogLevel::ERROR));
        $this->assertTrue($logger->isHandling(LogLevel::CRITICAL));
        $this->assertFalse($logger->isHandling(LogLevel::INFO));
        $this->assertFalse($logger->isHandling(LogLevel::DEBUG));
    }

    public function testSetAndGetLevel(): void
    {
        $this->logger->setLevel(LogLevel::ERROR);
        $this->assertEquals(LogLevel::ERROR, $this->logger->getLevel());
    }

    public function testPushAndPopHandler(): void
    {
        $handler2 = Mockery::mock(HandlerInterface::class);

        $this->logger->pushHandler($handler2);

        $popped = $this->logger->popHandler();
        $this->assertSame($handler2, $popped);
    }

    public function testPushAndPopProcessor(): void
    {
        $processor = function (LogRecord $record): LogRecord {
            $record->addExtra(['processed' => true]);
            return $record;
        };

        $this->logger->pushProcessor($processor);

        $this->mockHandler
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(function (LogRecord $record) {
                return $record->getExtra()['processed'] === true;
            }))
            ->andReturn(true);

        $this->logger->info('Test');

        $popped = $this->logger->popProcessor();
        $this->assertSame($processor, $popped);
    }

    public function testAllPsr3LogMethods(): void
    {
        $levels = [
            'emergency' => LogLevel::EMERGENCY,
            'alert' => LogLevel::ALERT,
            'critical' => LogLevel::CRITICAL,
            'error' => LogLevel::ERROR,
            'warning' => LogLevel::WARNING,
            'notice' => LogLevel::NOTICE,
            'info' => LogLevel::INFO,
            'debug' => LogLevel::DEBUG,
        ];

        foreach ($levels as $method => $level) {
            $this->mockHandler
                ->shouldReceive('handle')
                ->once()
                ->with(Mockery::on(function (LogRecord $record) use ($level) {
                    return $record->getLevel() === $level;
                }))
                ->andReturn(true);

            $this->logger->{$method}('Test message');
        }

        // Add assertion to verify all methods were called
        $this->assertCount(8, $levels);
    }

    public function testLogRecordHasCorrectChannel(): void
    {
        $logger = new McpLogger('custom-channel');

        $this->mockHandler
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(function (LogRecord $record) {
                return $record->getChannel() === 'custom-channel';
            }))
            ->andReturn(true);

        $logger->pushHandler($this->mockHandler);
        $logger->info('Test');

        // Add assertion to avoid risky test
        $this->assertTrue(true);
    }
}
