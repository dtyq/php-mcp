<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Client\Core;

use Dtyq\PhpMcp\Client\Core\AbstractSession;
use Dtyq\PhpMcp\Client\Core\TransportInterface;
use Dtyq\PhpMcp\Shared\Exceptions\McpError;
use Dtyq\PhpMcp\Shared\Exceptions\ProtocolError;
use Dtyq\PhpMcp\Types\Core\JsonRpcError;
use Dtyq\PhpMcp\Types\Core\JsonRpcResponse;
use Dtyq\PhpMcp\Types\Core\NotificationInterface;
use Dtyq\PhpMcp\Types\Core\RequestInterface;
use Dtyq\PhpMcp\Types\Responses\CallToolResult;
use Dtyq\PhpMcp\Types\Responses\ListResourcesResult;
use Dtyq\PhpMcp\Types\Responses\ListToolsResult;
use Mockery;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Unit tests for AbstractSession error handling.
 * @internal
 */
class AbstractSessionErrorHandlingTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testParseResponseHandlesSuccessfulResponse(): void
    {
        $transport = Mockery::mock(TransportInterface::class);
        $session = $this->createTestSession($transport);

        $message = '{"jsonrpc":"2.0","id":"1","result":{"status":"success"}}';
        $response = $this->callProtectedMethod($session, 'parseResponse', [$message]);

        $this->assertInstanceOf(JsonRpcResponse::class, $response);
        $this->assertFalse($response->isError());
        $this->assertEquals('1', $response->getId());
        $this->assertEquals(['status' => 'success'], $response->getResult());
    }

    public function testParseResponseHandlesErrorResponse(): void
    {
        $transport = Mockery::mock(TransportInterface::class);
        $session = $this->createTestSession($transport);

        $message = '{"jsonrpc":"2.0","id":"3","error":{"code":-32603,"message":"Error executing tool ai_image_for_flux1_schnell: assistant_not_found","data":[]}}';
        $response = $this->callProtectedMethod($session, 'parseResponse', [$message]);

        $this->assertInstanceOf(JsonRpcError::class, $response);
        $this->assertTrue($response->isError());
        $this->assertEquals('3', $response->getId());
        $this->assertEquals(-32603, $response->getCode());
        $this->assertEquals('Error executing tool ai_image_for_flux1_schnell: assistant_not_found', $response->getMessage());
        $this->assertEquals([], $response->getData());
    }

    public function testWaitForResponseThrowsMcpErrorOnErrorResponse(): void
    {
        $transport = Mockery::mock(TransportInterface::class);
        $transport->shouldReceive('receive')
            ->once()
            ->with(30)
            ->andReturn('{"jsonrpc":"2.0","id":"1","error":{"code":-32603,"message":"Tool execution failed","data":{"tool":"test"}}}');

        $session = $this->createTestSession($transport);

        $this->expectException(McpError::class);
        $this->expectExceptionMessage('Tool execution failed');

        $this->callProtectedMethod($session, 'waitForResponse', ['1', 30.0]);
    }

    public function testWaitForResponseReturnsSuccessfulResponse(): void
    {
        $transport = Mockery::mock(TransportInterface::class);
        $transport->shouldReceive('receive')
            ->once()
            ->with(30)
            ->andReturn('{"jsonrpc":"2.0","id":"1","result":{"data":"success"}}');

        $session = $this->createTestSession($transport);

        $response = $this->callProtectedMethod($session, 'waitForResponse', ['1', 30.0]);

        $this->assertInstanceOf(JsonRpcResponse::class, $response);
        $this->assertFalse($response->isError());
        $this->assertEquals(['data' => 'success'], $response->getResult());
    }

    public function testParseResponseThrowsProtocolErrorOnInvalidJson(): void
    {
        $transport = Mockery::mock(TransportInterface::class);
        $session = $this->createTestSession($transport);

        $this->expectException(ProtocolError::class);
        $this->expectExceptionMessage('Failed to parse JSON-RPC response');

        $this->callProtectedMethod($session, 'parseResponse', ['invalid json']);
    }

    public function testParseResponseThrowsProtocolErrorOnMissingJsonRpcVersion(): void
    {
        $transport = Mockery::mock(TransportInterface::class);
        $session = $this->createTestSession($transport);

        $this->expectException(ProtocolError::class);
        $this->expectExceptionMessage('Failed to parse JSON-RPC response');

        $this->callProtectedMethod($session, 'parseResponse', ['{"id":"1","result":{}}']);
    }

    /**
     * Create a concrete test implementation of AbstractSession.
     */
    private function createTestSession(TransportInterface $transport): AbstractSession
    {
        return new class($transport) extends AbstractSession {
            public function initialize(): void
            {
                // Test implementation - do nothing
            }

            public function sendRequest(RequestInterface $request, ?int $timeout = null): array
            {
                // Test implementation - return empty array
                return [];
            }

            public function sendNotification(NotificationInterface $notification): void
            {
                // Test implementation - do nothing
            }

            public function listTools(): ListToolsResult
            {
                // Test implementation - create a mock result
                return new ListToolsResult([]);
            }

            public function callTool(string $name, ?array $arguments = null): CallToolResult
            {
                // Test implementation - create a mock result
                return new CallToolResult([], false);
            }

            public function listResources(): ListResourcesResult
            {
                // Test implementation - create a mock result
                return new ListResourcesResult([]);
            }
        };
    }

    /**
     * Call a protected method on an object.
     *
     * @param object $object
     * @param array<mixed> $parameters
     * @return mixed
     */
    private function callProtectedMethod($object, string $methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
