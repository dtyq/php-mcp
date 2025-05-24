<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Shared\Logging;

use DateTime;
use Dtyq\PhpMcp\Shared\Logging\JsonFormatter;
use Dtyq\PhpMcp\Shared\Logging\LogRecord;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

/**
 * Tests for JsonFormatter class.
 * @internal
 */
class JsonFormatterTest extends TestCase
{
    public function testBasicJsonFormatting(): void
    {
        $formatter = new JsonFormatter();
        $record = new LogRecord(LogLevel::INFO, 'Test message');

        $formatted = $formatter->format($record);
        $data = json_decode($formatted, true);

        $this->assertIsArray($data);
        $this->assertEquals(LogLevel::INFO, $data['level']);
        $this->assertEquals('Test message', $data['message']);
        $this->assertArrayHasKey('timestamp', $data);
    }

    public function testJsonFormattingWithAllFields(): void
    {
        $formatter = new JsonFormatter();
        $datetime = new DateTime('2024-01-01 12:00:00');
        $record = new LogRecord(
            LogLevel::ERROR,
            'Error occurred',
            ['user' => 'john', 'action' => 'login'],
            $datetime,
            ['trace' => 'stack', 'file' => 'test.php'],
            'app',
            'req-123',
            'session-456'
        );

        $formatted = $formatter->format($record);
        $data = json_decode($formatted, true);

        $this->assertEquals(LogLevel::ERROR, $data['level']);
        $this->assertEquals('Error occurred', $data['message']);
        $this->assertEquals(['user' => 'john', 'action' => 'login'], $data['context']);
        $this->assertEquals(['trace' => 'stack', 'file' => 'test.php'], $data['extra']);
        $this->assertEquals('app', $data['channel']);
        $this->assertEquals('req-123', $data['request_id']);
        $this->assertEquals('session-456', $data['session_id']);
        $this->assertArrayHasKey('timestamp', $data);
    }

    public function testJsonFormattingWithoutContextAndExtra(): void
    {
        $formatter = new JsonFormatter(false, false);
        $record = new LogRecord(
            LogLevel::INFO,
            'Test message',
            ['user' => 'john'],
            null,
            ['trace' => 'stack']
        );

        $formatted = $formatter->format($record);
        $data = json_decode($formatted, true);

        $this->assertArrayNotHasKey('context', $data);
        $this->assertArrayNotHasKey('extra', $data);
    }

    public function testJsonFormattingWithEmptyContextAndExtra(): void
    {
        $formatter = new JsonFormatter();
        $record = new LogRecord(LogLevel::INFO, 'Test message', [], null, []);

        $formatted = $formatter->format($record);
        $data = json_decode($formatted, true);

        $this->assertArrayNotHasKey('context', $data);
        $this->assertArrayNotHasKey('extra', $data);
    }

    public function testPrettyPrintJson(): void
    {
        $formatter = new JsonFormatter(true, true, true);
        $record = new LogRecord(LogLevel::INFO, 'Test', ['key' => 'value']);

        $formatted = $formatter->format($record);

        $this->assertNotFalse(strpos($formatted, "\n"));
        $this->assertNotFalse(strpos($formatted, '    '));
    }

    public function testFormatBatch(): void
    {
        $formatter = new JsonFormatter();
        $records = [
            new LogRecord(LogLevel::INFO, 'Message 1'),
            new LogRecord(LogLevel::ERROR, 'Message 2'),
        ];

        $formatted = $formatter->formatBatch($records);
        $data = json_decode($formatted, true);

        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $this->assertEquals('Message 1', $data[0]['message']);
        $this->assertEquals('Message 2', $data[1]['message']);
    }

    public function testSettersAreFluentInterface(): void
    {
        $formatter = new JsonFormatter();

        $result = $formatter
            ->setIncludeContext(false)
            ->setIncludeExtra(false)
            ->setPrettyPrint(true);

        $this->assertSame($formatter, $result);
    }

    public function testJsonEncodingFailureHandling(): void
    {
        $formatter = new JsonFormatter();

        // Create a record with a problematic value
        $record = new LogRecord(LogLevel::ERROR, 'Test');
        $record->setContext(['resource' => fopen('php://memory', 'r')]);

        $formatted = $formatter->format($record);
        $data = json_decode($formatted, true);

        $this->assertEquals('error', $data['level']);
        $this->assertNotFalse(strpos($data['message'], 'Failed to encode'));
        $this->assertArrayHasKey('error', $data);

        // Close the resource
        fclose($record->getContext()['resource']);
    }

    public function testMessageInterpolation(): void
    {
        $formatter = new JsonFormatter();
        $record = new LogRecord(
            LogLevel::INFO,
            'User {user} performed {action}',
            ['user' => 'john', 'action' => 'login']
        );

        $formatted = $formatter->format($record);
        $data = json_decode($formatted, true);

        $this->assertEquals('User john performed login', $data['message']);
    }

    public function testTimestampFormat(): void
    {
        $formatter = new JsonFormatter();
        $datetime = new DateTime('2024-01-01 12:00:00.123456');
        $record = new LogRecord(LogLevel::INFO, 'Test', [], $datetime);

        $formatted = $formatter->format($record);
        $data = json_decode($formatted, true);

        // Should be RFC3339 extended format (allow for different microsecond lengths)
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{1,6}[+-]\d{2}:\d{2}$/',
            $data['timestamp']
        );
    }

    public function testOptionalFieldsNotIncludedWhenNull(): void
    {
        $formatter = new JsonFormatter();
        $record = new LogRecord(LogLevel::INFO, 'Test');

        $formatted = $formatter->format($record);
        $data = json_decode($formatted, true);

        $this->assertArrayNotHasKey('channel', $data);
        $this->assertArrayNotHasKey('request_id', $data);
        $this->assertArrayNotHasKey('session_id', $data);
    }
}
