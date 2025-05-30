<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Tests\Unit\Types\Core;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Core\MessageValidator;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * Tests for MessageValidator class
 */
class MessageValidatorTest extends TestCase
{
    public function testValidUtf8Message(): void
    {
        $message = '{"jsonrpc":"2.0","method":"test","id":1}';

        // Should not throw exception
        MessageValidator::validateUtf8($message);

        // Convenience method should return true
        $this->assertTrue(MessageValidator::isValidMessage($message));
    }

    public function testInvalidUtf8Message(): void
    {
        $message = "Invalid \xFF\xFE UTF-8";

        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Message contains invalid UTF-8 encoding');

        MessageValidator::validateUtf8($message);
    }

    public function testValidStdioFormat(): void
    {
        $message = '{"jsonrpc":"2.0","method":"test","id":1}';

        // Should not throw exception
        MessageValidator::validateStdioFormat($message);

        // If we get here without exception, test passed
        $this->assertTrue(true);
    }

    public function testInvalidStdioFormatWithNewline(): void
    {
        $message = "{\n\"jsonrpc\":\"2.0\",\"method\":\"test\",\"id\":1}";

        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Message contains embedded newlines, which violates MCP stdio transport specification');

        MessageValidator::validateStdioFormat($message);
    }

    public function testInvalidStdioFormatWithCarriageReturn(): void
    {
        $message = "{\r\"jsonrpc\":\"2.0\",\"method\":\"test\",\"id\":1}";

        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Message contains embedded newlines, which violates MCP stdio transport specification');

        MessageValidator::validateStdioFormat($message);
    }

    public function testValidJsonRpcRequest(): void
    {
        $message = '{"jsonrpc":"2.0","method":"test","id":1}';

        // Should not throw exception
        MessageValidator::validateMessage($message);

        // Convenience method should return true
        $this->assertTrue(MessageValidator::isValidMessage($message));
    }

    public function testValidJsonRpcNotification(): void
    {
        $message = '{"jsonrpc":"2.0","method":"test"}';

        // Should not throw exception
        MessageValidator::validateMessage($message);

        // Convenience method should return true
        $this->assertTrue(MessageValidator::isValidMessage($message));
    }

    public function testValidJsonRpcResponse(): void
    {
        $message = '{"jsonrpc":"2.0","result":"success","id":1}';

        // Should not throw exception
        MessageValidator::validateMessage($message);

        // Convenience method should return true
        $this->assertTrue(MessageValidator::isValidMessage($message));
    }

    public function testInvalidJsonFormat(): void
    {
        $message = '{"jsonrpc":"2.0","method":"test"'; // Missing closing brace

        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid JSON format');

        MessageValidator::validateMessage($message);
    }

    public function testMissingJsonRpcVersion(): void
    {
        $message = '{"method":"test","id":1}';

        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage("Required field 'jsonrpc' is missing");

        MessageValidator::validateMessage($message);
    }

    public function testInvalidJsonRpcVersion(): void
    {
        $message = '{"jsonrpc":"1.0","method":"test","id":1}';

        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid value for field \'jsonrpc\': must be "2.0"');

        MessageValidator::validateMessage($message);
    }

    public function testMissingMethodInRequest(): void
    {
        $message = '{"jsonrpc":"2.0","id":1}';

        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid JSON-RPC message: must have either "method"');

        MessageValidator::validateMessage($message);
    }

    public function testInvalidMethodType(): void
    {
        $message = '{"jsonrpc":"2.0","method":123,"id":1}';

        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage("Invalid type for field 'method': expected string, got integer");

        MessageValidator::validateMessage($message);
    }

    public function testMissingIdInResponse(): void
    {
        $message = '{"jsonrpc":"2.0","result":"success"}';

        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage("Required field 'id' is missing for JSON-RPC response");

        MessageValidator::validateMessage($message);
    }

    public function testValidBatchMessage(): void
    {
        $message = '[
            {"jsonrpc":"2.0","method":"test1","id":1},
            {"jsonrpc":"2.0","method":"test2","id":2}
        ]';

        // Should not throw exception
        MessageValidator::validateMessage($message);

        // If we get here without exception, validation passed
        $this->assertTrue(true);
    }

    public function testEmptyBatchMessage(): void
    {
        $message = '[]';

        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Batch message cannot be empty');

        MessageValidator::validateMessage($message);
    }

    public function testInvalidBatchMessageItem(): void
    {
        $message = '[
            {"jsonrpc":"2.0","method":"test1","id":1},
            "invalid item"
        ]';

        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage("Invalid type for field 'batch[1]': expected array, got string");

        MessageValidator::validateMessage($message);
    }

    public function testInvalidBatchMessageContent(): void
    {
        $message = '[
            {"jsonrpc":"2.0","method":"test1","id":1},
            {"jsonrpc":"1.0","method":"test2","id":2}
        ]';

        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid message at batch index 1: Invalid value for field \'jsonrpc\': must be "2.0"');

        MessageValidator::validateMessage($message);
    }

    public function testStrictModeValidation(): void
    {
        $messageWithNewline = "{\n\"jsonrpc\":\"2.0\",\"method\":\"test\",\"id\":1}";

        // Should pass without strict mode
        $this->assertTrue(MessageValidator::isValidMessage($messageWithNewline, false));

        // Should fail with strict mode
        $this->assertFalse(MessageValidator::isValidMessage($messageWithNewline, true));

        // Direct validation should throw in strict mode
        $this->expectException(ValidationError::class);
        MessageValidator::validateMessage($messageWithNewline, true);
    }

    public function testGetMessageInfo(): void
    {
        // Test request
        $request = '{"jsonrpc":"2.0","method":"test","id":1}';
        $info = MessageValidator::getMessageInfo($request);
        $this->assertEquals('request', $info['type']);
        $this->assertFalse($info['isBatch']);
        $this->assertEquals('test', $info['method']);
        $this->assertEquals(1, $info['id']);

        // Test notification
        $notification = '{"jsonrpc":"2.0","method":"notify"}';
        $info = MessageValidator::getMessageInfo($notification);
        $this->assertEquals('notification', $info['type']);
        $this->assertFalse($info['isBatch']);
        $this->assertEquals('notify', $info['method']);
        $this->assertArrayNotHasKey('id', $info);

        // Test response
        $response = '{"jsonrpc":"2.0","result":"success","id":1}';
        $info = MessageValidator::getMessageInfo($response);
        $this->assertEquals('response', $info['type']);
        $this->assertFalse($info['isBatch']);
        $this->assertEquals(1, $info['id']);

        // Test batch
        $batch = '[{"jsonrpc":"2.0","method":"test","id":1}]';
        $info = MessageValidator::getMessageInfo($batch);
        $this->assertEquals('batch', $info['type']);
        $this->assertTrue($info['isBatch']);
        $this->assertEquals(1, $info['count']);
    }

    public function testGetMessageInfoWithInvalidJson(): void
    {
        $message = '{"invalid": json}';

        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid JSON format');

        MessageValidator::getMessageInfo($message);
    }

    public function testIsValidMessageConvenienceMethod(): void
    {
        // Valid message
        $valid = '{"jsonrpc":"2.0","method":"test","id":1}';
        $this->assertTrue(MessageValidator::isValidMessage($valid));

        // Invalid message
        $invalid = '{"jsonrpc":"1.0","method":"test","id":1}';
        $this->assertFalse(MessageValidator::isValidMessage($invalid));

        // Invalid JSON
        $invalidJson = '{"invalid": json}';
        $this->assertFalse(MessageValidator::isValidMessage($invalidJson));
    }

    public function testNonArrayStructure(): void
    {
        $message = '"just a string"';

        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage("Invalid type for field 'message': expected array, got string");

        MessageValidator::validateMessage($message);
    }
}
