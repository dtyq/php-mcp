<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Server\Core;

use Dtyq\PhpMcp\Server\Core\McpServer;
use Dtyq\PhpMcp\Server\Core\MiddlewareInterface;
use Dtyq\PhpMcp\Server\Core\RequestRouterInterface;
use Dtyq\PhpMcp\Server\Core\ServerCapabilities;
use Dtyq\PhpMcp\Server\Core\ServerConfig;
use Dtyq\PhpMcp\Server\Core\TransportInterface;
use Dtyq\PhpMcp\Types\Core\JsonRpcError;
use Dtyq\PhpMcp\Types\Core\JsonRpcRequest;
use Dtyq\PhpMcp\Types\Core\JsonRpcResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Test cases for McpServer class.
 * @internal
 */
class McpServerTest extends TestCase
{
    private McpServer $server;

    private ServerConfig $config;

    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        $this->config = new ServerConfig('Test Server', '1.0.0', 'Test MCP Server');
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->server = new McpServer($this->config, $this->logger);
    }

    public function testServerInitialization(): void
    {
        $this->assertSame($this->config, $this->server->getConfig());
        $this->assertEquals(McpServer::STATE_STOPPED, $this->server->getState());
        $this->assertFalse($this->server->isRunning());
    }

    public function testServerConfiguration(): void
    {
        $this->assertEquals('Test Server', $this->server->getConfig()->getName());
        $this->assertEquals('1.0.0', $this->server->getConfig()->getVersion());
        $this->assertEquals('Test MCP Server', $this->server->getConfig()->getDescription());
    }

    public function testTransportManagement(): void
    {
        /** @var MockObject&TransportInterface $transport */
        $transport = $this->createMock(TransportInterface::class);

        $this->server->setTransport($transport);
        $this->assertSame($transport, $this->server->getTransport());
    }

    public function testRouterManagement(): void
    {
        /** @var MockObject&RequestRouterInterface $router */
        $router = $this->createMock(RequestRouterInterface::class);

        $this->server->setRouter($router);
        $this->assertSame($router, $this->server->getRouter());
    }

    public function testCapabilitiesManagement(): void
    {
        $capabilities = new ServerCapabilities(['tools' => true]);

        $this->server->setCapabilities($capabilities);
        $this->assertSame($capabilities, $this->server->getCapabilities());
    }

    public function testMiddlewareManagement(): void
    {
        /** @var MiddlewareInterface&MockObject $middleware */
        $middleware = $this->createMock(MiddlewareInterface::class);

        $this->server->addMiddleware($middleware);
        $middlewareStack = $this->server->getMiddleware();

        $this->assertCount(1, $middlewareStack);
        $this->assertSame($middleware, $middlewareStack[0]);
    }

    public function testEventListenerManagement(): void
    {
        $called = false;
        $listener = function () use (&$called) {
            $called = true;
        };

        $this->server->addEventListener('test.event', $listener);

        // We can't directly test event emission without making the method public
        // This test just verifies the listener is added without error
        $this->assertTrue(true);
    }

    public function testProcessRequestWithoutRouter(): void
    {
        $request = new JsonRpcRequest('test.method', [], 1);

        $response = $this->server->processRequest($request);

        $this->assertInstanceOf(JsonRpcError::class, $response);
        $this->assertEquals(-32601, $response->getCode()); // METHOD_NOT_FOUND
        $this->assertStringContainsString('Method not found', $response->getMessage());
    }

    public function testProcessRequestWithRouter(): void
    {
        $request = new JsonRpcRequest('test.method', [], 1);
        $expectedResponse = new JsonRpcResponse(1, ['result' => 'success']);

        /** @var MockObject&RequestRouterInterface $router */
        $router = $this->createMock(RequestRouterInterface::class);
        $router->expects($this->once())
            ->method('route')
            ->with($request)
            ->willReturn($expectedResponse);

        $this->server->setRouter($router);
        $response = $this->server->processRequest($request);

        $this->assertSame($expectedResponse, $response);
    }

    public function testProcessRequestWithMiddleware(): void
    {
        $request = new JsonRpcRequest('test.method', [], 1);
        $expectedResponse = new JsonRpcResponse(1, ['result' => 'middleware']);

        /** @var MiddlewareInterface&MockObject $middleware */
        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->with($request)
            ->willReturn($expectedResponse);

        $this->server->addMiddleware($middleware);
        $response = $this->server->processRequest($request);

        $this->assertSame($expectedResponse, $response);
    }

    public function testProcessRequestHandlesExceptions(): void
    {
        $request = new JsonRpcRequest('test.method', [], 1);

        /** @var MockObject&RequestRouterInterface $router */
        $router = $this->createMock(RequestRouterInterface::class);
        $router->expects($this->once())
            ->method('route')
            ->with($request)
            ->willThrowException(new RuntimeException('Test error'));

        $this->server->setRouter($router);
        $response = $this->server->processRequest($request);

        $this->assertInstanceOf(JsonRpcError::class, $response);
        $this->assertEquals(-32603, $response->getCode()); // INTERNAL_ERROR
        $this->assertStringContainsString('Internal server error', $response->getMessage());
    }

    public function testServerStats(): void
    {
        $stats = $this->server->getStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('requests_processed', $stats);
        $this->assertArrayHasKey('errors_occurred', $stats);
        $this->assertArrayHasKey('start_time', $stats);
        $this->assertArrayHasKey('uptime', $stats);

        $this->assertEquals(0, $stats['requests_processed']);
        $this->assertEquals(0, $stats['errors_occurred']);
        $this->assertNull($stats['start_time']);
        $this->assertEquals(0, $stats['uptime']);
    }

    public function testStatsUpdateOnRequestProcessing(): void
    {
        $request = new JsonRpcRequest('test.method', [], 1);

        $initialStats = $this->server->getStats();
        $this->server->processRequest($request);
        $updatedStats = $this->server->getStats();

        $this->assertEquals(
            $initialStats['requests_processed'] + 1,
            $updatedStats['requests_processed']
        );
    }

    public function testStatsUpdateOnError(): void
    {
        $request = new JsonRpcRequest('test.method', [], 1);

        /** @var MockObject&RequestRouterInterface $router */
        $router = $this->createMock(RequestRouterInterface::class);
        $router->method('route')->willThrowException(new RuntimeException('Test error'));
        $this->server->setRouter($router);

        $initialStats = $this->server->getStats();
        $this->server->processRequest($request);
        $updatedStats = $this->server->getStats();

        $this->assertEquals(
            $initialStats['errors_occurred'] + 1,
            $updatedStats['errors_occurred']
        );
    }
}
