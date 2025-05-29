<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Shared\Message;

use Dtyq\PhpMcp\Shared\Message\JsonRpcMessage;
use Dtyq\PhpMcp\Shared\Message\MessageUtils;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;
use PHPUnit\Framework\TestCase;

/**
 * Test MessageUtils utility class.
 * @internal
 */
class MessageUtilsTest extends TestCase
{
    public function testMcpProtocolVersion(): void
    {
        // Test that MessageUtils uses the correct protocol version from ProtocolConstants
        $clientInfo = ['name' => 'TestClient', 'version' => '1.0.0'];
        $message = MessageUtils::createInitializeRequest(1, $clientInfo);
        $params = $message->getParams();
        $this->assertSame(ProtocolConstants::LATEST_PROTOCOL_VERSION, $params['protocolVersion']);
    }

    public function testCreateInitializeRequest(): void
    {
        $clientInfo = ['name' => 'TestClient', 'version' => '1.0.0'];
        $capabilities = ['tools' => true, 'resources' => true];

        $message = MessageUtils::createInitializeRequest(1, $clientInfo, $capabilities);

        $this->assertInstanceOf(JsonRpcMessage::class, $message);
        $this->assertTrue($message->isRequest());
        $this->assertSame('initialize', $message->getMethod());
        $this->assertSame(1, $message->getId());

        $params = $message->getParams();
        $this->assertSame('2025-03-26', $params['protocolVersion']);
        $this->assertSame($clientInfo, $params['clientInfo']);
        $this->assertSame($capabilities, $params['capabilities']);
    }

    public function testCreateInitializeResponse(): void
    {
        $serverInfo = ['name' => 'TestServer', 'version' => '1.0.0'];
        $capabilities = ['tools' => true, 'resources' => true];

        $message = MessageUtils::createInitializeResponse(1, $serverInfo, $capabilities);

        $this->assertInstanceOf(JsonRpcMessage::class, $message);
        $this->assertTrue($message->isResponse());
        $this->assertSame(1, $message->getId());

        $result = $message->getResult();
        $this->assertSame('2025-03-26', $result['protocolVersion']);
        $this->assertSame($serverInfo, $result['serverInfo']);
        $this->assertSame($capabilities, $result['capabilities']);
    }

    public function testCreateInitializedNotification(): void
    {
        $message = MessageUtils::createInitializedNotification();

        $this->assertInstanceOf(JsonRpcMessage::class, $message);
        $this->assertTrue($message->isNotification());
        $this->assertSame('notifications/initialized', $message->getMethod());
        $this->assertNull($message->getId());
    }

    public function testCreatePingRequest(): void
    {
        $message = MessageUtils::createPingRequest(2);

        $this->assertInstanceOf(JsonRpcMessage::class, $message);
        $this->assertTrue($message->isRequest());
        $this->assertSame('ping', $message->getMethod());
        $this->assertSame(2, $message->getId());
        $this->assertNull($message->getParams());
    }

    public function testCreatePongResponse(): void
    {
        $message = MessageUtils::createPongResponse(2);

        $this->assertInstanceOf(JsonRpcMessage::class, $message);
        $this->assertTrue($message->isResponse());
        $this->assertSame(2, $message->getId());
        $this->assertNotNull($message->getResult());
    }

    public function testCreateListToolsRequest(): void
    {
        $message = MessageUtils::createListToolsRequest(3, 'cursor123');

        $this->assertInstanceOf(JsonRpcMessage::class, $message);
        $this->assertTrue($message->isRequest());
        $this->assertSame('tools/list', $message->getMethod());
        $this->assertSame(3, $message->getId());

        $params = $message->getParams();
        $this->assertSame('cursor123', $params['cursor']);
    }

    public function testCreateListToolsRequestWithoutCursor(): void
    {
        $message = MessageUtils::createListToolsRequest(3);

        $this->assertInstanceOf(JsonRpcMessage::class, $message);
        $this->assertTrue($message->isRequest());
        $this->assertSame('tools/list', $message->getMethod());
        $this->assertSame(3, $message->getId());
        $this->assertNull($message->getParams());
    }

    public function testCreateCallToolRequest(): void
    {
        $arguments = ['file' => 'test.txt', 'mode' => 'read'];
        $message = MessageUtils::createCallToolRequest(4, 'read_file', $arguments);

        $this->assertInstanceOf(JsonRpcMessage::class, $message);
        $this->assertTrue($message->isRequest());
        $this->assertSame('tools/call', $message->getMethod());
        $this->assertSame(4, $message->getId());

        $params = $message->getParams();
        $this->assertSame('read_file', $params['name']);
        $this->assertSame($arguments, $params['arguments']);
    }

    public function testCreateListResourcesRequest(): void
    {
        $message = MessageUtils::createListResourcesRequest(5, 'cursor456');

        $this->assertInstanceOf(JsonRpcMessage::class, $message);
        $this->assertTrue($message->isRequest());
        $this->assertSame('resources/list', $message->getMethod());
        $this->assertSame(5, $message->getId());

        $params = $message->getParams();
        $this->assertSame('cursor456', $params['cursor']);
    }

    public function testCreateReadResourceRequest(): void
    {
        $message = MessageUtils::createReadResourceRequest(6, 'file:///path/to/file.txt');

        $this->assertInstanceOf(JsonRpcMessage::class, $message);
        $this->assertTrue($message->isRequest());
        $this->assertSame('resources/read', $message->getMethod());
        $this->assertSame(6, $message->getId());

        $params = $message->getParams();
        $this->assertSame('file:///path/to/file.txt', $params['uri']);
    }

    public function testCreateSubscribeRequest(): void
    {
        $message = MessageUtils::createSubscribeRequest(7, 'file:///watch/file.txt');

        $this->assertInstanceOf(JsonRpcMessage::class, $message);
        $this->assertTrue($message->isRequest());
        $this->assertSame('resources/subscribe', $message->getMethod());
        $this->assertSame(7, $message->getId());

        $params = $message->getParams();
        $this->assertSame('file:///watch/file.txt', $params['uri']);
    }

    public function testCreateUnsubscribeRequest(): void
    {
        $message = MessageUtils::createUnsubscribeRequest(8, 'file:///watch/file.txt');

        $this->assertInstanceOf(JsonRpcMessage::class, $message);
        $this->assertTrue($message->isRequest());
        $this->assertSame('resources/unsubscribe', $message->getMethod());
        $this->assertSame(8, $message->getId());

        $params = $message->getParams();
        $this->assertSame('file:///watch/file.txt', $params['uri']);
    }

    public function testCreateListPromptsRequest(): void
    {
        $message = MessageUtils::createListPromptsRequest(9, 'cursor789');

        $this->assertInstanceOf(JsonRpcMessage::class, $message);
        $this->assertTrue($message->isRequest());
        $this->assertSame('prompts/list', $message->getMethod());
        $this->assertSame(9, $message->getId());

        $params = $message->getParams();
        $this->assertSame('cursor789', $params['cursor']);
    }

    public function testCreateGetPromptRequest(): void
    {
        $arguments = ['name' => 'John', 'age' => 30];
        $message = MessageUtils::createGetPromptRequest(10, 'greeting', $arguments);

        $this->assertInstanceOf(JsonRpcMessage::class, $message);
        $this->assertTrue($message->isRequest());
        $this->assertSame('prompts/get', $message->getMethod());
        $this->assertSame(10, $message->getId());

        $params = $message->getParams();
        $this->assertSame('greeting', $params['name']);
        $this->assertSame($arguments, $params['arguments']);
    }

    public function testCreateProgressNotification(): void
    {
        $message = MessageUtils::createProgressNotification('token123', 0.75, 100);

        $this->assertInstanceOf(JsonRpcMessage::class, $message);
        $this->assertTrue($message->isNotification());
        $this->assertSame('notifications/progress', $message->getMethod());
        $this->assertNull($message->getId());

        $params = $message->getParams();
        $this->assertSame('token123', $params['progressToken']);
        $this->assertSame(0.75, $params['progress']);
        $this->assertSame(100.0, $params['total']);
    }

    public function testCreateProgressNotificationWithoutTotal(): void
    {
        $message = MessageUtils::createProgressNotification('token123', 0.5);

        $params = $message->getParams();
        $this->assertSame('token123', $params['progressToken']);
        $this->assertSame(0.5, $params['progress']);
        $this->assertArrayNotHasKey('total', $params);
    }

    public function testCreateCancelledNotification(): void
    {
        $message = MessageUtils::createCancelledNotification(123, 'User cancelled operation');

        $this->assertInstanceOf(JsonRpcMessage::class, $message);
        $this->assertTrue($message->isNotification());
        $this->assertSame('notifications/cancelled', $message->getMethod());
        $this->assertNull($message->getId());

        $params = $message->getParams();
        $this->assertSame(123, $params['requestId']);
        $this->assertSame('User cancelled operation', $params['reason']);
    }

    public function testCreateLoggingNotification(): void
    {
        $message = MessageUtils::createLoggingNotification('info', 'Test log message', 'test-logger');

        $this->assertInstanceOf(JsonRpcMessage::class, $message);
        $this->assertTrue($message->isNotification());
        $this->assertSame('notifications/message', $message->getMethod());
        $this->assertNull($message->getId());

        $params = $message->getParams();
        $this->assertSame('info', $params['level']);
        $this->assertSame('Test log message', $params['data']);
        $this->assertSame('test-logger', $params['logger']);
    }

    public function testIsStandardMethod(): void
    {
        $this->assertTrue(MessageUtils::isStandardMethod('initialize'));
        $this->assertTrue(MessageUtils::isStandardMethod('ping'));
        $this->assertTrue(MessageUtils::isStandardMethod('tools/list'));
        $this->assertTrue(MessageUtils::isStandardMethod('tools/call'));
        $this->assertTrue(MessageUtils::isStandardMethod('resources/list'));
        $this->assertTrue(MessageUtils::isStandardMethod('resources/read'));
        $this->assertTrue(MessageUtils::isStandardMethod('resources/subscribe'));
        $this->assertTrue(MessageUtils::isStandardMethod('resources/unsubscribe'));
        $this->assertTrue(MessageUtils::isStandardMethod('prompts/list'));
        $this->assertTrue(MessageUtils::isStandardMethod('prompts/get'));

        $this->assertFalse(MessageUtils::isStandardMethod('custom/method'));
        $this->assertFalse(MessageUtils::isStandardMethod('unknown'));
    }

    public function testIsNotificationMethod(): void
    {
        $this->assertTrue(MessageUtils::isNotificationMethod('notifications/progress'));
        $this->assertTrue(MessageUtils::isNotificationMethod('notifications/message'));
        $this->assertTrue(MessageUtils::isNotificationMethod('notifications/cancelled'));

        $this->assertFalse(MessageUtils::isNotificationMethod('initialize'));
        $this->assertFalse(MessageUtils::isNotificationMethod('tools/list'));
    }

    public function testGenerateRequestId(): void
    {
        $id1 = MessageUtils::generateRequestId();
        $id2 = MessageUtils::generateRequestId();

        $this->assertIsString($id1);
        $this->assertIsString($id2);
        $this->assertNotSame($id1, $id2);
    }
}
