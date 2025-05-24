<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Shared\Logging;

use DateTime;
use Dtyq\PhpMcp\Shared\Logging\LogRecord;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

/**
 * Tests for LogRecord class.
 * @internal
 */
class LogRecordTest extends TestCase
{
    public function testBasicLogRecordCreation(): void
    {
        $record = new LogRecord(LogLevel::INFO, 'Test message');

        $this->assertEquals(LogLevel::INFO, $record->getLevel());
        $this->assertEquals('Test message', $record->getMessage());
        $this->assertEmpty($record->getContext());
        $this->assertInstanceOf(DateTime::class, $record->getDatetime());
        $this->assertEmpty($record->getExtra());
        $this->assertNull($record->getChannel());
        $this->assertNull($record->getRequestId());
        $this->assertNull($record->getSessionId());
    }

    public function testLogRecordWithAllParameters(): void
    {
        $datetime = new DateTime('2024-01-01 12:00:00');
        $context = ['user' => 'test'];
        $extra = ['trace' => 'stack'];

        $record = new LogRecord(
            LogLevel::ERROR,
            'Error occurred',
            $context,
            $datetime,
            $extra,
            'app',
            'req-123',
            'session-456'
        );

        $this->assertEquals(LogLevel::ERROR, $record->getLevel());
        $this->assertEquals('Error occurred', $record->getMessage());
        $this->assertEquals($context, $record->getContext());
        $this->assertEquals($datetime, $record->getDatetime());
        $this->assertEquals($extra, $record->getExtra());
        $this->assertEquals('app', $record->getChannel());
        $this->assertEquals('req-123', $record->getRequestId());
        $this->assertEquals('session-456', $record->getSessionId());
    }

    public function testSettersAndGetters(): void
    {
        $record = new LogRecord(LogLevel::DEBUG, 'Test');

        $record->setLevel(LogLevel::WARNING);
        $this->assertEquals(LogLevel::WARNING, $record->getLevel());

        $record->setMessage('Updated message');
        $this->assertEquals('Updated message', $record->getMessage());

        $record->setContext(['key' => 'value']);
        $this->assertEquals(['key' => 'value'], $record->getContext());

        $record->setExtra(['meta' => 'data']);
        $this->assertEquals(['meta' => 'data'], $record->getExtra());

        $record->setChannel('test');
        $this->assertEquals('test', $record->getChannel());

        $record->setRequestId('req-999');
        $this->assertEquals('req-999', $record->getRequestId());

        $record->setSessionId('sess-888');
        $this->assertEquals('sess-888', $record->getSessionId());
    }

    public function testAddContext(): void
    {
        $record = new LogRecord(LogLevel::INFO, 'Test', ['original' => 'value']);

        $record->addContext(['new' => 'data', 'another' => 'item']);

        $expected = ['original' => 'value', 'new' => 'data', 'another' => 'item'];
        $this->assertEquals($expected, $record->getContext());
    }

    public function testAddExtra(): void
    {
        $record = new LogRecord(LogLevel::INFO, 'Test', [], null, ['original' => 'meta']);

        $record->addExtra(['new' => 'meta', 'another' => 'meta']);

        $expected = ['original' => 'meta', 'new' => 'meta', 'another' => 'meta'];
        $this->assertEquals($expected, $record->getExtra());
    }

    public function testToArray(): void
    {
        $datetime = new DateTime('2024-01-01 12:00:00');
        $record = new LogRecord(
            LogLevel::INFO,
            'Test message',
            ['user' => 'test'],
            $datetime,
            ['trace' => 'stack'],
            'app',
            'req-123',
            'session-456'
        );

        $array = $record->toArray();

        $this->assertEquals(LogLevel::INFO, $array['level']);
        $this->assertEquals('Test message', $array['message']);
        $this->assertEquals(['user' => 'test'], $array['context']);
        $this->assertEquals($datetime->format('Y-m-d\TH:i:s.vP'), $array['datetime']);
        $this->assertEquals(['trace' => 'stack'], $array['extra']);
        $this->assertEquals('app', $array['channel']);
        $this->assertEquals('req-123', $array['request_id']);
        $this->assertEquals('session-456', $array['session_id']);
    }

    public function testGetFormattedMessage(): void
    {
        $record = new LogRecord(
            LogLevel::INFO,
            'User {user} performed {action}',
            ['user' => 'john', 'action' => 'login', 'ip' => '127.0.0.1']
        );

        $formatted = $record->getFormattedMessage();
        $this->assertEquals('User john performed login', $formatted);
    }

    public function testGetFormattedMessageWithComplexValues(): void
    {
        $record = new LogRecord(
            LogLevel::INFO,
            'Data: {data}, Status: {status}, Null: {null}',
            [
                'data' => ['key' => 'value'],
                'status' => true,
                'null' => null,
            ]
        );

        $formatted = $record->getFormattedMessage();
        $this->assertNotFalse(strpos($formatted, '{"key":"value"}'));
        $this->assertNotFalse(strpos($formatted, 'true'));
        $this->assertNotFalse(strpos($formatted, 'null'));
    }

    public function testIsMcpMessage(): void
    {
        // Regular log record
        $record1 = new LogRecord(LogLevel::INFO, 'Regular message');
        $this->assertFalse($record1->isMcpMessage());

        // With request ID
        $record2 = new LogRecord(LogLevel::INFO, 'Test', [], null, [], null, 'req-123');
        $this->assertTrue($record2->isMcpMessage());

        // With session ID
        $record3 = new LogRecord(LogLevel::INFO, 'Test', [], null, [], null, null, 'sess-456');
        $this->assertTrue($record3->isMcpMessage());

        // With MCP method in context
        $record4 = new LogRecord(LogLevel::INFO, 'Test', ['mcp_method' => 'initialize']);
        $this->assertTrue($record4->isMcpMessage());

        // With MCP direction in extra
        $record5 = new LogRecord(LogLevel::INFO, 'Test', [], null, ['mcp_direction' => 'inbound']);
        $this->assertTrue($record5->isMcpMessage());
    }

    public function testGetLevelPriority(): void
    {
        $debugRecord = new LogRecord(LogLevel::DEBUG, 'Debug');
        $infoRecord = new LogRecord(LogLevel::INFO, 'Info');
        $errorRecord = new LogRecord(LogLevel::ERROR, 'Error');
        $emergencyRecord = new LogRecord(LogLevel::EMERGENCY, 'Emergency');

        $this->assertEquals(100, $debugRecord->getLevelPriority());
        $this->assertEquals(200, $infoRecord->getLevelPriority());
        $this->assertEquals(400, $errorRecord->getLevelPriority());
        $this->assertEquals(600, $emergencyRecord->getLevelPriority());

        // Verify ordering
        $this->assertTrue($debugRecord->getLevelPriority() < $infoRecord->getLevelPriority());
        $this->assertTrue($infoRecord->getLevelPriority() < $errorRecord->getLevelPriority());
        $this->assertTrue($errorRecord->getLevelPriority() < $emergencyRecord->getLevelPriority());
    }

    public function testFluentInterface(): void
    {
        $record = new LogRecord(LogLevel::INFO, 'Test');

        $result = $record
            ->setLevel(LogLevel::ERROR)
            ->setMessage('Updated')
            ->setChannel('test')
            ->setRequestId('req-1')
            ->setSessionId('sess-1');

        $this->assertSame($record, $result);
        $this->assertEquals(LogLevel::ERROR, $record->getLevel());
        $this->assertEquals('Updated', $record->getMessage());
        $this->assertEquals('test', $record->getChannel());
        $this->assertEquals('req-1', $record->getRequestId());
        $this->assertEquals('sess-1', $record->getSessionId());
    }
}
