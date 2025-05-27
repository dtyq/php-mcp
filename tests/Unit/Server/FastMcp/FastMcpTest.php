<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Server\FastMcp;

use Dtyq\PhpMcp\Server\Core\McpServer;
use Dtyq\PhpMcp\Server\Core\MiddlewareInterface;
use Dtyq\PhpMcp\Server\Core\TransportInterface;
use Dtyq\PhpMcp\Server\FastMcp\FastMcp;
use Dtyq\PhpMcp\Types\Core\JsonRpcRequest;
use Dtyq\PhpMcp\Types\Core\JsonRpcResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Test cases for FastMcp class.
 * @internal
 */
class FastMcpTest extends TestCase
{
    public function testCreateBasicServer(): void
    {
        $server = FastMcp::create('Test Server')
            ->withDescription('A test server')
            ->withVersion('1.0.0')
            ->build();

        $this->assertInstanceOf(McpServer::class, $server);
        $this->assertEquals('Test Server', $server->getConfig()->getName());
        $this->assertEquals('A test server', $server->getConfig()->getDescription());
        $this->assertEquals('1.0.0', $server->getConfig()->getVersion());
    }

    public function testFluentConfiguration(): void
    {
        $server = FastMcp::create('Fluent Server')
            ->withDescription('Testing fluent API')
            ->withVersion('2.0.0')
            ->withVendor('Test Vendor', 'https://example.com')
            ->withLogLevel('debug')
            ->build();

        $config = $server->getConfig();
        $this->assertEquals('Fluent Server', $config->getName());
        $this->assertEquals('Testing fluent API', $config->getDescription());
        $this->assertEquals('2.0.0', $config->getVersion());
        $this->assertEquals('debug', $config->getLogLevel());

        $vendor = $config->getVendor();
        $this->assertEquals('Test Vendor', $vendor['name']);
        $this->assertEquals('https://example.com', $vendor['url']);
    }

    public function testCapabilitiesConfiguration(): void
    {
        $server = FastMcp::create('Capabilities Server')
            ->withTools(['calculator', 'weather'])
            ->withResources(['files', 'database'])
            ->withPrompts(['greeting', 'help'])
            ->withSampling()
            ->withRoots(['/tmp', '/var'])
            ->build();

        $capabilities = $server->getCapabilities();
        $this->assertNotNull($capabilities);

        $caps = $capabilities->toArray();
        $this->assertArrayHasKey('tools', $caps);
        $this->assertArrayHasKey('resources', $caps);
        $this->assertArrayHasKey('prompts', $caps);
        $this->assertArrayHasKey('sampling', $caps);
        $this->assertArrayHasKey('roots', $caps);
    }

    public function testTransportConfiguration(): void
    {
        /** @var MockObject&TransportInterface $transport */
        $transport = $this->createMock(TransportInterface::class);

        $server = FastMcp::create('Transport Server')
            ->withTransport($transport)
            ->build();

        $this->assertSame($transport, $server->getTransport());
    }

    public function testMiddlewareConfiguration(): void
    {
        /** @var MiddlewareInterface&MockObject $middleware */
        $middleware = $this->createMock(MiddlewareInterface::class);

        $server = FastMcp::create('Middleware Server')
            ->withMiddleware($middleware)
            ->build();

        $middlewareStack = $server->getMiddleware();
        $this->assertCount(1, $middlewareStack);
        $this->assertSame($middleware, $middlewareStack[0]);
    }

    public function testRouteConfiguration(): void
    {
        $handler = function (JsonRpcRequest $request) {
            return new JsonRpcResponse($request->getId(), ['result' => 'test']);
        };

        $server = FastMcp::create('Route Server')
            ->withRoute('test.method', $handler)
            ->build();

        $router = $server->getRouter();
        $this->assertNotNull($router);

        $routes = $router->getRoutes();
        $this->assertContains('test.method', $routes);
    }

    public function testMultipleRoutesConfiguration(): void
    {
        $routes = [
            'method1' => function () { return new JsonRpcResponse(1, ['result' => '1']); },
            'method2' => function () { return new JsonRpcResponse(2, ['result' => '2']); },
        ];

        $server = FastMcp::create('Multi Route Server')
            ->withRoutes($routes)
            ->build();

        $router = $server->getRouter();
        $this->assertNotNull($router);

        $routeNames = $router->getRoutes();
        $this->assertContains('method1', $routeNames);
        $this->assertContains('method2', $routeNames);
    }

    public function testEventListenerConfiguration(): void
    {
        $listener = function () { return true; };

        $server = FastMcp::create('Event Server')
            ->withEventListener('test.event', $listener)
            ->build();

        // We can't directly test event listeners without exposing internal state
        // This test just verifies the configuration doesn't throw errors
        $this->assertInstanceOf(McpServer::class, $server);
    }

    public function testPerformanceConfiguration(): void
    {
        $server = FastMcp::create('Performance Server')
            ->withPerformance([
                'max_request_size' => 2048,
                'request_timeout' => 60,
            ])
            ->build();

        $config = $server->getConfig();
        $this->assertEquals(2048, $config->getMaxRequestSize());
        $this->assertEquals(60, $config->getRequestTimeout());
    }

    public function testSecurityConfiguration(): void
    {
        $server = FastMcp::create('Security Server')
            ->withSecurity([
                'enable_cors' => true,
                'allowed_origins' => ['https://example.com'],
            ])
            ->build();

        $config = $server->getConfig();
        $this->assertTrue($config->isCorsEnabled());
        $this->assertEquals(['https://example.com'], $config->getAllowedOrigins());
    }

    public function testCorsConfiguration(): void
    {
        $server = FastMcp::create('CORS Server')
            ->withCors(['https://trusted.com'])
            ->build();

        $config = $server->getConfig();
        $this->assertTrue($config->isCorsEnabled());
        $this->assertEquals(['https://trusted.com'], $config->getAllowedOrigins());
    }

    public function testSimpleServerFactory(): void
    {
        $routes = [
            'ping' => function () { return new JsonRpcResponse(1, ['result' => 'pong']); },
        ];

        $server = FastMcp::simple('Simple Server', $routes);

        $this->assertInstanceOf(McpServer::class, $server);
        $this->assertEquals('Simple Server', $server->getConfig()->getName());
        $this->assertStringContainsString('Simple MCP server', $server->getConfig()->getDescription());

        $router = $server->getRouter();
        $this->assertNotNull($router);
        $this->assertContains('ping', $router->getRoutes());
    }

    public function testFullServerFactory(): void
    {
        $builder = FastMcp::full('Full Server');

        $this->assertInstanceOf(FastMcp::class, $builder);

        $server = $builder->build();
        $capabilities = $server->getCapabilities();
        $caps = $capabilities->toArray();

        // Verify all capabilities are enabled
        $this->assertArrayHasKey('tools', $caps);
        $this->assertArrayHasKey('resources', $caps);
        $this->assertArrayHasKey('prompts', $caps);
        $this->assertArrayHasKey('sampling', $caps);
        $this->assertArrayHasKey('roots', $caps);

        // Verify CORS is enabled
        $this->assertTrue($server->getConfig()->isCorsEnabled());
    }

    public function testCustomLoggerConfiguration(): void
    {
        /** @var LoggerInterface&MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);

        $server = FastMcp::create('Logger Server')
            ->withLogger($logger)
            ->build();

        // We can't directly test the logger without exposing internal state
        // This test just verifies the configuration doesn't throw errors
        $this->assertInstanceOf(McpServer::class, $server);
    }
}
