<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Tests\Client\Session;

use Dtyq\PhpMcp\Client\Core\TransportInterface;
use Dtyq\PhpMcp\Client\Session\ClientSession;
use Dtyq\PhpMcp\Client\Session\SessionMetadata;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for ClientSession class.
 * @internal
 */
class ClientSessionTest extends TestCase
{
    private MockObject&TransportInterface $mockTransport;

    private SessionMetadata $metadata;

    private ClientSession $session;

    protected function setUp(): void
    {
        $this->mockTransport = $this->createMock(TransportInterface::class);
        $this->metadata = SessionMetadata::fromArray([
            'client_name' => 'test-client',
            'client_version' => '1.0.0',
            'response_timeout' => 30.0,
            'initialization_timeout' => 60.0,
        ]);

        $this->session = new ClientSession($this->mockTransport, $this->metadata);
    }

    public function testSessionIdGeneration(): void
    {
        $sessionId = $this->session->getSessionId();

        $this->assertIsString($sessionId);
        $this->assertNotEmpty($sessionId);
        $this->assertStringStartsWith('mcp_session_', $sessionId);
    }

    public function testSessionIdConsistency(): void
    {
        $sessionId1 = $this->session->getSessionId();
        $sessionId2 = $this->session->getSessionId();

        // Session ID should be consistent
        $this->assertEquals($sessionId1, $sessionId2);
    }

    public function testCustomSessionId(): void
    {
        $customId = 'custom_session_123';
        $session = new ClientSession(
            $this->mockTransport,
            $this->metadata,
            null,
            $customId
        );

        $this->assertEquals($customId, $session->getSessionId());
    }

    public function testUniqueSessionIds(): void
    {
        $session1 = new ClientSession($this->mockTransport, $this->metadata);
        $session2 = new ClientSession($this->mockTransport, $this->metadata);

        $this->assertNotEquals($session1->getSessionId(), $session2->getSessionId());
    }

    public function testSessionIdInStats(): void
    {
        $stats = $this->session->getStats();

        $this->assertArrayHasKey('session_id', $stats);
        $this->assertEquals($this->session->getSessionId(), $stats['session_id']);
    }

    public function testSessionIdFormat(): void
    {
        $sessionId = $this->session->getSessionId();

        // Should match pattern: mcp_session_{timestamp}_{random_hex}
        $pattern = '/^mcp_session_\d+_[a-f0-9]{16}$/';
        $this->assertMatchesRegularExpression($pattern, $sessionId);
    }

    public function testInitialState(): void
    {
        $this->assertFalse($this->session->isInitialized());
        $this->assertEquals('disconnected', $this->session->getSessionState());
    }

    public function testMetadataAccess(): void
    {
        $retrievedMetadata = $this->session->getMetadata();
        $this->assertSame($this->metadata, $retrievedMetadata);
    }

    public function testStatsStructure(): void
    {
        $stats = $this->session->getStats();

        $expectedKeys = [
            'session_id',
            'initialized',
            'state',
            'transport_connected',
            'server_capabilities',
            'client_capabilities',
            'pending_requests',
            'default_timeout',
            'metadata',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $stats);
        }

        // Check metadata structure
        $this->assertArrayHasKey('client_name', $stats['metadata']);
        $this->assertArrayHasKey('client_version', $stats['metadata']);
        $this->assertArrayHasKey('response_timeout', $stats['metadata']);
        $this->assertArrayHasKey('initialize_timeout', $stats['metadata']);
    }

    public function testClose(): void
    {
        $this->mockTransport->expects($this->once())
            ->method('isConnected')
            ->willReturn(true);

        $this->mockTransport->expects($this->once())
            ->method('disconnect');

        $this->session->close();

        $this->assertFalse($this->session->isInitialized());
    }
}
