<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Tests\Client\Core;

use Dtyq\PhpMcp\Client\Core\ClientStats;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for ClientStats class.
 * @internal
 */
class ClientStatsTest extends TestCase
{
    private ClientStats $stats;

    protected function setUp(): void
    {
        $this->stats = new ClientStats();
    }

    public function testInitialState(): void
    {
        $this->assertEquals('created', $this->stats->getStatus());
        $this->assertFalse($this->stats->isConnected());
        $this->assertFalse($this->stats->hasActiveSession());
        $this->assertFalse($this->stats->isSessionInitialized());
        $this->assertEquals(0, $this->stats->getConnectionAttempts());
        $this->assertEquals(0, $this->stats->getConnectionErrors());
        $this->assertEquals(0, $this->stats->getCloseErrors());
        $this->assertEquals(0.0, $this->stats->getUptime());
        $this->assertEquals(0.0, $this->stats->getConnectionSuccessRate());
    }

    public function testConnectionAttempt(): void
    {
        $beforeTime = microtime(true);
        $this->stats->recordConnectionAttempt();
        $afterTime = microtime(true);

        $this->assertEquals(1, $this->stats->getConnectionAttempts());
        $this->assertTrue($this->stats->isConnected());
        $this->assertEquals('connecting', $this->stats->getStatus());

        $connectedAt = $this->stats->getConnectedAt();
        $this->assertNotNull($connectedAt);
        $this->assertGreaterThanOrEqual($beforeTime, $connectedAt);
        $this->assertLessThanOrEqual($afterTime, $connectedAt);
    }

    public function testConnectionError(): void
    {
        $this->stats->recordConnectionError();

        $this->assertEquals(1, $this->stats->getConnectionErrors());
    }

    public function testMultipleConnectionsWithErrors(): void
    {
        // Record 3 attempts, 1 error
        $this->stats->recordConnectionAttempt();
        $this->stats->recordConnectionAttempt();
        $this->stats->recordConnectionError();
        $this->stats->recordConnectionAttempt();

        $this->assertEquals(3, $this->stats->getConnectionAttempts());
        $this->assertEquals(1, $this->stats->getConnectionErrors());
        $this->assertEquals(2.0 / 3.0, $this->stats->getConnectionSuccessRate(), '', 0.001);
    }

    public function testSessionStats(): void
    {
        $sessionData = [
            'totalSessions' => 2,
            'activeSessions' => 1,
            'averageResponseTime' => 150.5,
        ];

        $this->stats->updateSessionStats($sessionData);

        $this->assertTrue($this->stats->hasActiveSession());
        $this->assertTrue($this->stats->isSessionInitialized());
    }

    public function testStatusProgression(): void
    {
        // Initial state
        $this->assertEquals('created', $this->stats->getStatus());

        // After connection
        $this->stats->recordConnectionAttempt();
        $this->assertEquals('connecting', $this->stats->getStatus());

        // After session becomes active
        $this->stats->updateSessionStats(['totalSessions' => 1, 'activeSessions' => 1]);
        $this->assertEquals('connected', $this->stats->getStatus());

        // After closure
        $this->stats->recordClosure();
        $this->assertEquals('closed', $this->stats->getStatus());
        $this->assertFalse($this->stats->isConnected());
    }

    public function testUptime(): void
    {
        // No connection yet
        $this->assertEquals(0.0, $this->stats->getUptime());

        // After connection
        $this->stats->recordConnectionAttempt();
        usleep(100000); // 0.1 seconds

        $uptime = $this->stats->getUptime();
        $this->assertGreaterThan(0.0, $uptime);
        $this->assertLessThan(1.0, $uptime); // Should be less than 1 second

        // After closure
        usleep(100000); // Another 0.1 seconds
        $this->stats->recordClosure();

        $finalUptime = $this->stats->getUptime();
        $this->assertGreaterThan($uptime, $finalUptime);
    }

    public function testCloseErrors(): void
    {
        $this->stats->recordCloseError();
        $this->stats->recordCloseError();

        $this->assertEquals(2, $this->stats->getCloseErrors());
    }

    public function testToArray(): void
    {
        $this->stats->recordConnectionAttempt();
        $this->stats->updateSessionStats(['totalSessions' => 1, 'activeSessions' => 1]);

        $array = $this->stats->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('createdAt', $array);
        $this->assertArrayHasKey('connectedAt', $array);
        $this->assertArrayHasKey('connectionAttempts', $array);
        $this->assertArrayHasKey('uptime', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('hasActiveSession', $array);
        $this->assertArrayHasKey('sessionInitialized', $array);
        $this->assertArrayHasKey('connectionSuccessRate', $array);

        $this->assertEquals(1, $array['connectionAttempts']);
        $this->assertEquals('connected', $array['status']);
        $this->assertTrue($array['hasActiveSession']);
    }

    public function testJsonSerialization(): void
    {
        $this->stats->recordConnectionAttempt();

        $json = json_encode($this->stats);
        $this->assertIsString($json);

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertEquals(1, $decoded['connectionAttempts']);
        $this->assertEquals('connecting', $decoded['status']);
    }

    public function testFromArray(): void
    {
        $data = [
            'createdAt' => 1640995200.123,
            'connectedAt' => 1640995210.456,
            'connectionAttempts' => 3,
            'connectionErrors' => 1,
            'sessionManager' => ['totalSessions' => 2, 'activeSessions' => 1],
        ];

        $stats = ClientStats::fromArray($data);

        $this->assertEquals(1640995200.123, $stats->getCreatedAt());
        $this->assertEquals(1640995210.456, $stats->getConnectedAt());
        $this->assertEquals(3, $stats->getConnectionAttempts());
        $this->assertEquals(1, $stats->getConnectionErrors());
        $this->assertTrue($stats->hasActiveSession());
        $this->assertTrue($stats->isSessionInitialized());
    }

    public function testReset(): void
    {
        // Set up some state
        $this->stats->recordConnectionAttempt();
        $this->stats->recordConnectionError();
        $this->stats->updateSessionStats(['totalSessions' => 1]);

        // Reset
        $this->stats->reset();

        // Verify reset state
        $this->assertEquals('created', $this->stats->getStatus());
        $this->assertEquals(0, $this->stats->getConnectionAttempts());
        $this->assertEquals(0, $this->stats->getConnectionErrors());
        $this->assertFalse($this->stats->hasActiveSession());
        $this->assertNull($this->stats->getConnectedAt());
        $this->assertNull($this->stats->getClosedAt());
    }

    public function testSuccessRateEdgeCases(): void
    {
        // No attempts yet
        $this->assertEquals(0.0, $this->stats->getConnectionSuccessRate());

        // All attempts successful
        $this->stats->recordConnectionAttempt();
        $this->stats->recordConnectionAttempt();
        $this->assertEquals(1.0, $this->stats->getConnectionSuccessRate());

        // All attempts failed
        $this->stats->recordConnectionError();
        $this->stats->recordConnectionError();
        $this->assertEquals(0.0, $this->stats->getConnectionSuccessRate());
    }
}
