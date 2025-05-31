<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Tests\Client\Session;

use Dtyq\PhpMcp\Client\Core\TransportInterface;
use Dtyq\PhpMcp\Client\Session\ClientSession;
use Dtyq\PhpMcp\Client\Session\SessionManager;
use Dtyq\PhpMcp\Client\Session\SessionMetadata;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for simplified SessionManager class.
 * @internal
 */
class SessionManagerTest extends TestCase
{
    private SessionManager $sessionManager;

    private MockObject&TransportInterface $mockTransport;

    private SessionMetadata $metadata;

    protected function setUp(): void
    {
        $this->sessionManager = new SessionManager();
        $this->mockTransport = $this->createMock(TransportInterface::class);
        $this->metadata = SessionMetadata::fromArray([
            'client_name' => 'test-client',
            'client_version' => '1.0.0',
            'response_timeout' => 30.0,
            'initialization_timeout' => 60.0,
        ]);
    }

    public function testInitialState(): void
    {
        $this->assertEquals([], $this->sessionManager->getSessionIds());
        $this->assertEquals(0, $this->sessionManager->getSessionCount());

        $stats = $this->sessionManager->getStats();
        $this->assertEquals(0, $stats['totalSessions']);
        $this->assertEquals([], $stats['sessionIds']);
    }

    public function testAddSession(): void
    {
        $session = new ClientSession($this->mockTransport, $this->metadata);
        $sessionId = $session->getSessionId();

        $this->sessionManager->addSession($sessionId, $session);

        $this->assertEquals([$sessionId], $this->sessionManager->getSessionIds());
        $this->assertTrue($this->sessionManager->hasSession($sessionId));
        $this->assertSame($session, $this->sessionManager->getSession($sessionId));
        $this->assertEquals(1, $this->sessionManager->getSessionCount());
    }

    public function testAddDuplicateSessionThrowsException(): void
    {
        $session = new ClientSession($this->mockTransport, $this->metadata);
        $sessionId = $session->getSessionId();

        $this->sessionManager->addSession($sessionId, $session);

        $this->expectException(ValidationError::class);
        $this->sessionManager->addSession($sessionId, $session);
    }

    public function testGetNonExistentSessionThrowsException(): void
    {
        $this->expectException(ValidationError::class);
        $this->sessionManager->getSession('non-existent-session');
    }

    public function testHasSession(): void
    {
        $session = new ClientSession($this->mockTransport, $this->metadata);
        $sessionId = $session->getSessionId();

        $this->assertFalse($this->sessionManager->hasSession($sessionId));

        $this->sessionManager->addSession($sessionId, $session);
        $this->assertTrue($this->sessionManager->hasSession($sessionId));
    }

    public function testRemoveSession(): void
    {
        $session = new ClientSession($this->mockTransport, $this->metadata);
        $sessionId = $session->getSessionId();

        $this->sessionManager->addSession($sessionId, $session);
        $this->assertTrue($this->sessionManager->removeSession($sessionId));

        $this->assertFalse($this->sessionManager->hasSession($sessionId));
        $this->assertEquals([], $this->sessionManager->getSessionIds());
        $this->assertEquals(0, $this->sessionManager->getSessionCount());
    }

    public function testRemoveNonExistentSession(): void
    {
        $this->assertFalse($this->sessionManager->removeSession('non-existent'));
    }

    public function testGetSessionCount(): void
    {
        $this->assertEquals(0, $this->sessionManager->getSessionCount());

        $session1 = new ClientSession($this->mockTransport, $this->metadata);
        $this->sessionManager->addSession($session1->getSessionId(), $session1);
        $this->assertEquals(1, $this->sessionManager->getSessionCount());

        $session2 = new ClientSession($this->mockTransport, $this->metadata);
        $this->sessionManager->addSession($session2->getSessionId(), $session2);
        $this->assertEquals(2, $this->sessionManager->getSessionCount());
    }

    public function testGetStats(): void
    {
        $session = new ClientSession($this->mockTransport, $this->metadata);
        $sessionId = $session->getSessionId();

        $this->sessionManager->addSession($sessionId, $session);

        $stats = $this->sessionManager->getStats();

        $this->assertEquals(1, $stats['totalSessions']);
        $this->assertEquals([$sessionId], $stats['sessionIds']);
    }

    public function testCloseAll(): void
    {
        $session1 = new ClientSession($this->mockTransport, $this->metadata);
        $session2 = new ClientSession($this->mockTransport, $this->metadata);

        $this->sessionManager->addSession($session1->getSessionId(), $session1);
        $this->sessionManager->addSession($session2->getSessionId(), $session2);

        $this->sessionManager->closeAll();

        $this->assertEquals([], $this->sessionManager->getSessionIds());
        $this->assertEquals(0, $this->sessionManager->getSessionCount());

        $stats = $this->sessionManager->getStats();
        $this->assertEquals(0, $stats['totalSessions']);
    }
}
