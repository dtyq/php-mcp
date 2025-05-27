<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Server\Core;

use Dtyq\PhpMcp\Server\Core\LifecycleManager;
use Dtyq\PhpMcp\Server\Core\McpServerInterface;
use Dtyq\PhpMcp\Server\Core\ServerConfig;
use Dtyq\PhpMcp\Server\Core\TransportInterface;
use Dtyq\PhpMcp\Shared\Exceptions\McpError;
use Dtyq\PhpMcp\Shared\Exceptions\ProtocolError;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Test cases for LifecycleManager.
 * @internal
 */
class LifecycleManagerTest extends TestCase
{
    private LifecycleManager $lifecycleManager;

    /** @var McpServerInterface&MockObject */
    private MockObject $mockServer;

    /** @var LoggerInterface&MockObject */
    private MockObject $mockLogger;

    /** @var MockObject&ServerConfig */
    private MockObject $mockConfig;

    /** @var MockObject&TransportInterface */
    private MockObject $mockTransport;

    protected function setUp(): void
    {
        $this->mockServer = $this->createMock(McpServerInterface::class);
        $this->mockLogger = $this->createMock(LoggerInterface::class);
        $this->mockConfig = $this->createMock(ServerConfig::class);
        $this->mockTransport = $this->createMock(TransportInterface::class);

        $this->lifecycleManager = new LifecycleManager($this->mockServer, $this->mockLogger);
    }

    public function testConstructorSetsInitialState(): void
    {
        $this->assertSame(LifecycleManager::PHASE_STOPPED, $this->lifecycleManager->getCurrentPhase());
        $this->assertTrue($this->lifecycleManager->isHealthy());
    }

    public function testInitializeFailsWhenNotStopped(): void
    {
        // First initialize to change state
        $this->setupValidConfig();
        $this->lifecycleManager->initialize();

        // Try to initialize again
        $this->expectException(ProtocolError::class);
        $this->expectExceptionMessage('Invalid state for operation \'initialize\'');

        $this->lifecycleManager->initialize();
    }

    public function testRegisterHookWithInvalidPhase(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid value for field \'phase\'');

        $this->lifecycleManager->registerHook('invalid_phase', function () {
        });
    }

    public function testGetStatsReturnsCompleteInformation(): void
    {
        $stats = $this->lifecycleManager->getStats();

        $this->assertArrayHasKey('initialization_count', $stats);
        $this->assertArrayHasKey('startup_count', $stats);
        $this->assertArrayHasKey('shutdown_count', $stats);
        $this->assertArrayHasKey('restart_count', $stats);
        $this->assertArrayHasKey('error_count', $stats);
        $this->assertArrayHasKey('current_phase', $stats);
        $this->assertArrayHasKey('healthy', $stats);
        $this->assertArrayHasKey('last_error', $stats);

        $this->assertSame(LifecycleManager::PHASE_STOPPED, $stats['current_phase']);
        $this->assertTrue($stats['healthy']);
        $this->assertNull($stats['last_error']);
    }

    public function testShutdownWhenAlreadyStopped(): void
    {
        // Should not throw exception when already stopped
        $this->lifecycleManager->shutdown();

        $this->assertSame(LifecycleManager::PHASE_STOPPED, $this->lifecycleManager->getCurrentPhase());
    }

    public function testHookPriorityOrdering(): void
    {
        $executionOrder = [];

        $hook1 = function () use (&$executionOrder) {
            $executionOrder[] = 'hook1';
        };
        $hook2 = function () use (&$executionOrder) {
            $executionOrder[] = 'hook2';
        };
        $hook3 = function () use (&$executionOrder) {
            $executionOrder[] = 'hook3';
        };

        // Register hooks with different priorities (higher priority executes first)
        $this->lifecycleManager->registerHook('initialize', $hook1, 10);
        $this->lifecycleManager->registerHook('initialize', $hook2, 20);
        $this->lifecycleManager->registerHook('initialize', $hook3, 15);

        // Setup minimal mocks
        $this->setupValidConfig();

        $this->lifecycleManager->initialize();

        // Verify execution order (highest priority first)
        $this->assertSame(['hook2', 'hook3', 'hook1'], $executionOrder);
    }

    public function testInitializeSuccess(): void
    {
        $this->setupValidConfig();

        // Execute
        $this->lifecycleManager->initialize();

        // Verify
        $this->assertSame(LifecycleManager::PHASE_INITIALIZING, $this->lifecycleManager->getCurrentPhase());
        $this->assertTrue($this->lifecycleManager->isHealthy());

        $stats = $this->lifecycleManager->getStats();
        $this->assertSame(1, $stats['initialization_count']);
    }

    public function testStartupSuccess(): void
    {
        // Setup and initialize first
        $this->setupValidConfig();
        $this->lifecycleManager->initialize();

        // Setup startup mocks
        $this->mockServer->method('getTransport')
            ->willReturn($this->mockTransport);

        $this->mockTransport->method('start');

        $this->mockServer->method('getCapabilities')
            ->willReturn(null);

        // Execute
        $this->lifecycleManager->startup();

        // Verify
        $this->assertSame(LifecycleManager::PHASE_RUNNING, $this->lifecycleManager->getCurrentPhase());
        $this->assertTrue($this->lifecycleManager->isHealthy());

        $stats = $this->lifecycleManager->getStats();
        $this->assertSame(1, $stats['startup_count']);
        $this->assertNotNull($stats['last_startup_time']);
    }

    public function testShutdownSuccess(): void
    {
        // Setup, initialize and start server
        $this->setupValidConfig();
        $this->lifecycleManager->initialize();

        $this->mockServer->method('getTransport')
            ->willReturn($this->mockTransport);

        $this->mockTransport->method('start');

        $this->mockServer->method('getCapabilities')
            ->willReturn(null);

        $this->lifecycleManager->startup();

        // Wait a moment to ensure uptime > 0
        usleep(1000); // 1ms

        // Setup shutdown mocks
        $this->mockTransport->method('stop');
        $this->mockTransport->method('isRunning')
            ->willReturn(false);

        // Execute
        $this->lifecycleManager->shutdown();

        // Verify
        $this->assertSame(LifecycleManager::PHASE_STOPPED, $this->lifecycleManager->getCurrentPhase());

        $stats = $this->lifecycleManager->getStats();
        $this->assertSame(1, $stats['shutdown_count']);
        $this->assertNotNull($stats['last_shutdown_time']);
        $this->assertGreaterThanOrEqual(0, $stats['total_uptime']);
    }

    public function testErrorHandlingUpdatesHealthAndStats(): void
    {
        // Force an error by providing invalid config
        $this->mockServer->method('getConfig')
            ->willThrowException(new RuntimeException('Config error'));

        try {
            $this->lifecycleManager->initialize();
        } catch (McpError $e) {
            // Expected
        }

        // Verify error state
        $this->assertSame(LifecycleManager::PHASE_ERROR, $this->lifecycleManager->getCurrentPhase());
        $this->assertFalse($this->lifecycleManager->isHealthy());

        $stats = $this->lifecycleManager->getStats();
        $this->assertSame(1, $stats['error_count']);
        $this->assertNotNull($stats['last_error']);
        $this->assertSame('initialize', $stats['last_error']['phase']);
    }

    private function setupValidConfig(): void
    {
        $this->mockServer->method('getConfig')
            ->willReturn($this->mockConfig);

        $this->mockConfig->method('getName')
            ->willReturn('test-server');

        $this->mockConfig->method('getVersion')
            ->willReturn('1.0.0');

        $this->mockServer->method('getTransport')
            ->willReturn($this->mockTransport);

        $this->mockServer->method('getRouter')
            ->willReturn(null);

        $this->mockServer->method('setRouter');

        $this->mockServer->method('getCapabilities')
            ->willReturn(null);

        $this->mockServer->method('setCapabilities');
    }
}
