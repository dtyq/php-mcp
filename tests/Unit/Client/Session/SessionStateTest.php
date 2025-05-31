<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Client\Session;

use Dtyq\PhpMcp\Client\Session\SessionState;
use Dtyq\PhpMcp\Types\Responses\InitializeResult;
use PHPUnit\Framework\TestCase;

/**
 * Test case for SessionState.
 * @internal
 */
class SessionStateTest extends TestCase
{
    private SessionState $sessionState;

    protected function setUp(): void
    {
        $this->sessionState = new SessionState();
    }

    public function testInitialState(): void
    {
        $this->assertEquals(SessionState::STATE_DISCONNECTED, $this->sessionState->getCurrentState());
        $this->assertFalse($this->sessionState->isInitialized());
        $this->assertNull($this->sessionState->getServerCapabilities());
        $this->assertEmpty($this->sessionState->getServerInfo());
        $this->assertNull($this->sessionState->getInitializedAt());
    }

    public function testSetState(): void
    {
        $this->sessionState->setState(SessionState::STATE_CONNECTING);
        $this->assertEquals(SessionState::STATE_CONNECTING, $this->sessionState->getCurrentState());

        $this->sessionState->setState(SessionState::STATE_READY);
        $this->assertEquals(SessionState::STATE_READY, $this->sessionState->getCurrentState());
    }

    public function testMarkAsInitialized(): void
    {
        $serverCapabilities = $this->createMockInitializeResult();

        $beforeTime = microtime(true);
        $this->sessionState->markAsInitialized($serverCapabilities);
        $afterTime = microtime(true);

        $this->assertTrue($this->sessionState->isInitialized());
        $this->assertEquals(SessionState::STATE_READY, $this->sessionState->getCurrentState());
        $this->assertSame($serverCapabilities, $this->sessionState->getServerCapabilities());

        $initializedAt = $this->sessionState->getInitializedAt();
        $this->assertNotNull($initializedAt);
        $this->assertGreaterThanOrEqual($beforeTime, $initializedAt);
        $this->assertLessThanOrEqual($afterTime, $initializedAt);
    }

    public function testReset(): void
    {
        // Initialize with some data
        $serverCapabilities = $this->createMockInitializeResult();
        $this->sessionState->markAsInitialized($serverCapabilities);
        $this->sessionState->setServerInfo(['name' => 'test-server']);
        $this->sessionState->setStateData('key', 'value');

        // Reset
        $this->sessionState->reset();

        // Verify reset
        $this->assertEquals(SessionState::STATE_DISCONNECTED, $this->sessionState->getCurrentState());
        $this->assertFalse($this->sessionState->isInitialized());
        $this->assertNull($this->sessionState->getServerCapabilities());
        $this->assertEmpty($this->sessionState->getServerInfo());
        $this->assertNull($this->sessionState->getInitializedAt());
        $this->assertNull($this->sessionState->getStateData('key'));
    }

    public function testServerInfo(): void
    {
        $serverInfo = [
            'name' => 'test-server',
            'version' => '1.0.0',
        ];

        $this->sessionState->setServerInfo($serverInfo);
        $this->assertEquals($serverInfo, $this->sessionState->getServerInfo());
    }

    public function testStateData(): void
    {
        $this->sessionState->setStateData('key1', 'value1');
        $this->sessionState->setStateData('key2', 42);
        $this->sessionState->setStateData('key3', ['nested' => 'data']);

        $this->assertEquals('value1', $this->sessionState->getStateData('key1'));
        $this->assertEquals(42, $this->sessionState->getStateData('key2'));
        $this->assertEquals(['nested' => 'data'], $this->sessionState->getStateData('key3'));
        $this->assertEquals('default', $this->sessionState->getStateData('nonexistent', 'default'));
    }

    public function testRemoveStateData(): void
    {
        $this->sessionState->setStateData('key', 'value');
        $this->assertEquals('value', $this->sessionState->getStateData('key'));

        $this->sessionState->removeStateData('key');
        $this->assertNull($this->sessionState->getStateData('key'));
    }

    public function testIsConnected(): void
    {
        $this->assertFalse($this->sessionState->isConnected());

        $this->sessionState->setState(SessionState::STATE_CONNECTED);
        $this->assertTrue($this->sessionState->isConnected());

        $this->sessionState->setState(SessionState::STATE_READY);
        $this->assertTrue($this->sessionState->isConnected());

        $this->sessionState->setState(SessionState::STATE_DISCONNECTED);
        $this->assertFalse($this->sessionState->isConnected());
    }

    public function testIsReady(): void
    {
        $this->assertFalse($this->sessionState->isReady());

        // Just setting state to ready is not enough
        $this->sessionState->setState(SessionState::STATE_READY);
        $this->assertFalse($this->sessionState->isReady()); // Not initialized yet

        // Need to mark as initialized for isReady to return true
        $serverCapabilities = $this->createMockInitializeResult();
        $this->sessionState->markAsInitialized($serverCapabilities);
        $this->assertTrue($this->sessionState->isReady());

        $this->sessionState->setState(SessionState::STATE_CONNECTED);
        $this->assertFalse($this->sessionState->isReady());
    }

    public function testIsError(): void
    {
        $this->assertFalse($this->sessionState->isError());

        $this->sessionState->setState(SessionState::STATE_ERROR);
        $this->assertTrue($this->sessionState->isError());

        $this->sessionState->setState(SessionState::STATE_READY);
        $this->assertFalse($this->sessionState->isError());
    }

    public function testGetSummary(): void
    {
        $summary = $this->sessionState->getSummary();

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('state', $summary);
        $this->assertArrayHasKey('initialized', $summary);
        $this->assertArrayHasKey('initializedAt', $summary);
        $this->assertArrayHasKey('hasServerCapabilities', $summary);
        $this->assertArrayHasKey('serverInfo', $summary);
        $this->assertArrayHasKey('stateDataKeys', $summary);
    }

    public function testHasServerCapability(): void
    {
        // Without server capabilities
        $this->assertFalse($this->sessionState->hasServerCapability('tools'));

        // With server capabilities
        $serverCapabilities = $this->createMockInitializeResult([
            'tools' => ['list' => true],
            'prompts' => ['list' => false],
        ]);
        $this->sessionState->markAsInitialized($serverCapabilities);

        $this->assertTrue($this->sessionState->hasServerCapability('tools'));
        $this->assertTrue($this->sessionState->hasServerCapability('tools.list'));
        $this->assertFalse($this->sessionState->hasServerCapability('tools.call'));
        $this->assertFalse($this->sessionState->hasServerCapability('nonexistent'));
    }

    public function testGetServerCapability(): void
    {
        // Without server capabilities
        $this->assertNull($this->sessionState->getServerCapability('tools'));
        $this->assertEquals('default', $this->sessionState->getServerCapability('tools', 'default'));

        // With server capabilities
        $serverCapabilities = $this->createMockInitializeResult([
            'tools' => ['list' => true, 'call' => false],
            'prompts' => ['list' => true],
        ]);
        $this->sessionState->markAsInitialized($serverCapabilities);

        $this->assertEquals(['list' => true, 'call' => false], $this->sessionState->getServerCapability('tools'));
        $this->assertTrue($this->sessionState->getServerCapability('tools.list'));
        $this->assertFalse($this->sessionState->getServerCapability('tools.call'));
        $this->assertEquals('default', $this->sessionState->getServerCapability('nonexistent', 'default'));
    }

    /**
     * @param array<string, mixed> $capabilities
     */
    private function createMockInitializeResult(array $capabilities = []): InitializeResult
    {
        $mock = $this->createMock(InitializeResult::class);
        $mock->method('getCapabilities')->willReturn($capabilities);
        return $mock;
    }
}
