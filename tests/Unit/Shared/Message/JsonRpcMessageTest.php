<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Shared\Message;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Shared\Message\JsonRpcMessage;
use JsonException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Unit tests for JsonRpcMessage class.
 * @internal
 */
class JsonRpcMessageTest extends TestCase
{
    public function testCreateRequest(): void
    {
        $message = JsonRpcMessage::createRequest('test_method', ['param' => 'value'], 'req_1');

        $this->assertTrue($message->isRequest());
        $this->assertFalse($message->isNotification());
        $this->assertFalse($message->isResponse());
        $this->assertFalse($message->isError());

        $this->assertEquals('2.0', $message->getJsonrpc());
        $this->assertEquals('test_method', $message->getMethod());
        $this->assertEquals(['param' => 'value'], $message->getParams());
        $this->assertEquals('req_1', $message->getId());
        $this->assertNull($message->getResult());
        $this->assertNull($message->getError());
        $this->assertEquals(JsonRpcMessage::TYPE_REQUEST, $message->getType());
    }

    public function testCreateNotification(): void
    {
        $message = JsonRpcMessage::createNotification('notify_method', ['data' => 'test']);

        $this->assertFalse($message->isRequest());
        $this->assertTrue($message->isNotification());
        $this->assertFalse($message->isResponse());
        $this->assertFalse($message->isError());

        $this->assertEquals('2.0', $message->getJsonrpc());
        $this->assertEquals('notify_method', $message->getMethod());
        $this->assertEquals(['data' => 'test'], $message->getParams());
        $this->assertNull($message->getId());
        $this->assertNull($message->getResult());
        $this->assertNull($message->getError());
        $this->assertEquals(JsonRpcMessage::TYPE_NOTIFICATION, $message->getType());
    }

    public function testCreateResponse(): void
    {
        $result = ['success' => true];
        $message = JsonRpcMessage::createResponse('req_1', $result);

        $this->assertFalse($message->isRequest());
        $this->assertFalse($message->isNotification());
        $this->assertTrue($message->isResponse());
        $this->assertFalse($message->isError());

        $this->assertEquals('2.0', $message->getJsonrpc());
        $this->assertNull($message->getMethod());
        $this->assertNull($message->getParams());
        $this->assertEquals('req_1', $message->getId());
        $this->assertEquals($result, $message->getResult());
        $this->assertNull($message->getError());
        $this->assertEquals(JsonRpcMessage::TYPE_RESPONSE, $message->getType());
    }

    public function testCreateError(): void
    {
        $error = ['code' => -32600, 'message' => 'Invalid Request'];
        $message = JsonRpcMessage::createError('req_1', $error);

        $this->assertFalse($message->isRequest());
        $this->assertFalse($message->isNotification());
        $this->assertFalse($message->isResponse());
        $this->assertTrue($message->isError());

        $this->assertEquals('2.0', $message->getJsonrpc());
        $this->assertNull($message->getMethod());
        $this->assertNull($message->getParams());
        $this->assertEquals('req_1', $message->getId());
        $this->assertNull($message->getResult());
        $this->assertEquals($error, $message->getError());
        $this->assertEquals(JsonRpcMessage::TYPE_ERROR, $message->getType());
    }

    public function testToArray(): void
    {
        $message = JsonRpcMessage::createRequest('test', ['param' => 'value'], 123);
        $array = $message->toArray();

        $expected = [
            'jsonrpc' => '2.0',
            'method' => 'test',
            'params' => ['param' => 'value'],
            'id' => 123,
        ];

        $this->assertEquals($expected, $array);
    }

    public function testToArrayResponse(): void
    {
        $message = JsonRpcMessage::createResponse(456, ['result' => 'success']);
        $array = $message->toArray();

        $expected = [
            'jsonrpc' => '2.0',
            'id' => 456,
            'result' => ['result' => 'success'],
        ];

        $this->assertEquals($expected, $array);
    }

    public function testToArrayNotification(): void
    {
        $message = JsonRpcMessage::createNotification('test', ['data' => 'value']);
        $array = $message->toArray();

        $expected = [
            'jsonrpc' => '2.0',
            'method' => 'test',
            'params' => ['data' => 'value'],
        ];

        $this->assertEquals($expected, $array);
    }

    public function testFromArray(): void
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'test',
            'params' => ['param' => 'value'],
            'id' => 'req_1',
        ];

        $message = JsonRpcMessage::fromArray($data);

        $this->assertTrue($message->isRequest());
        $this->assertEquals('test', $message->getMethod());
        $this->assertEquals(['param' => 'value'], $message->getParams());
        $this->assertEquals('req_1', $message->getId());
    }

    public function testFromArrayInvalidVersion(): void
    {
        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Invalid JSON-RPC version');

        $data = [
            'jsonrpc' => '1.0',
            'method' => 'test',
            'id' => 1,
        ];

        JsonRpcMessage::fromArray($data);
    }

    public function testFromArrayMissingVersion(): void
    {
        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Invalid JSON-RPC version');

        $data = [
            'method' => 'test',
            'id' => 1,
        ];

        JsonRpcMessage::fromArray($data);
    }

    public function testToJson(): void
    {
        $message = JsonRpcMessage::createRequest('test', null, 1);
        $json = $message->toJson();

        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertEquals('2.0', $decoded['jsonrpc']);
        $this->assertEquals('test', $decoded['method']);
        $this->assertEquals(1, $decoded['id']);
    }

    public function testFromJson(): void
    {
        $json = '{"jsonrpc":"2.0","method":"test","params":{"key":"value"},"id":"req_1"}';
        $message = JsonRpcMessage::fromJson($json);

        $this->assertTrue($message->isRequest());
        $this->assertEquals('test', $message->getMethod());
        $this->assertEquals(['key' => 'value'], $message->getParams());
        $this->assertEquals('req_1', $message->getId());
    }

    public function testFromJsonInvalid(): void
    {
        $this->expectException(ValidationError::class);

        JsonRpcMessage::fromJson('invalid json');
    }

    public function testIsValidRequest(): void
    {
        $message = JsonRpcMessage::createRequest('test', null, 1);
        $this->assertTrue($message->isValid());
    }

    public function testIsValidNotification(): void
    {
        $message = JsonRpcMessage::createNotification('test', null);
        $this->assertTrue($message->isValid());
    }

    public function testIsValidResponse(): void
    {
        $message = JsonRpcMessage::createResponse(1, 'result');
        $this->assertTrue($message->isValid());
    }

    public function testIsValidError(): void
    {
        $message = JsonRpcMessage::createError(1, ['code' => -1, 'message' => 'Error']);
        $this->assertTrue($message->isValid());
    }

    public function testIsValidInvalidVersion(): void
    {
        $message = new JsonRpcMessage('test', null, 1);
        // Manually set wrong version
        $reflection = new ReflectionClass($message);
        $property = $reflection->getProperty('jsonrpc');
        $property->setAccessible(true);
        $property->setValue($message, '1.0');

        $this->assertFalse($message->isValid());
    }

    public function testIsValidInvalidStructure(): void
    {
        // Create message with both result and error (invalid)
        $message = new JsonRpcMessage(null, null, 1, 'result', ['code' => -1, 'message' => 'Error']);
        $this->assertFalse($message->isValid());
    }

    public function testConstants(): void
    {
        $this->assertEquals('2.0', JsonRpcMessage::VERSION);
        $this->assertEquals('request', JsonRpcMessage::TYPE_REQUEST);
        $this->assertEquals('response', JsonRpcMessage::TYPE_RESPONSE);
        $this->assertEquals('notification', JsonRpcMessage::TYPE_NOTIFICATION);
        $this->assertEquals('error', JsonRpcMessage::TYPE_ERROR);
    }

    public function testRoundTripSerialization(): void
    {
        $originalMessage = JsonRpcMessage::createRequest('test_method', ['param' => 'value'], 42);

        // Convert to array and back
        $array = $originalMessage->toArray();
        $messageFromArray = JsonRpcMessage::fromArray($array);

        $this->assertEquals($originalMessage->getJsonrpc(), $messageFromArray->getJsonrpc());
        $this->assertEquals($originalMessage->getMethod(), $messageFromArray->getMethod());
        $this->assertEquals($originalMessage->getParams(), $messageFromArray->getParams());
        $this->assertEquals($originalMessage->getId(), $messageFromArray->getId());
        $this->assertEquals($originalMessage->getType(), $messageFromArray->getType());

        // Convert to JSON and back
        $json = $originalMessage->toJson();
        $messageFromJson = JsonRpcMessage::fromJson($json);

        $this->assertEquals($originalMessage->getJsonrpc(), $messageFromJson->getJsonrpc());
        $this->assertEquals($originalMessage->getMethod(), $messageFromJson->getMethod());
        $this->assertEquals($originalMessage->getParams(), $messageFromJson->getParams());
        $this->assertEquals($originalMessage->getId(), $messageFromJson->getId());
        $this->assertEquals($originalMessage->getType(), $messageFromJson->getType());
    }
}
