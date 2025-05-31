<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Client\Transport\Http;

use Dtyq\PhpMcp\Client\Configuration\HttpConfig;
use Dtyq\PhpMcp\Client\Transport\Http\HttpConnectionManager;
use Dtyq\PhpMcp\Shared\Kernel\Logger\LoggerProxy;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Test case for HttpConnectionManager.
 * @internal
 */
class HttpConnectionManagerTest extends TestCase
{
    private HttpConfig $config;

    /** @var LoggerProxy&MockObject */
    private $logger;

    private HttpConnectionManager $manager;

    protected function setUp(): void
    {
        $this->config = new HttpConfig('https://api.example.com');
        $this->logger = $this->createMock(LoggerProxy::class);
        $this->manager = new HttpConnectionManager($this->config, $this->logger);
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(HttpConnectionManager::class, $this->manager);
        $this->assertFalse($this->manager->isInitialized());
        $this->assertNull($this->manager->getSessionId());
    }

    public function testInitializeWithoutSessionId(): void
    {
        $this->manager->initialize();

        $this->assertTrue($this->manager->isInitialized());
        $this->assertNull($this->manager->getSessionId());
    }

    public function testInitializeWithSessionId(): void
    {
        $sessionId = 'test-session-123';
        $this->manager->initialize($sessionId);

        $this->assertTrue($this->manager->isInitialized());
        $this->assertEquals($sessionId, $this->manager->getSessionId());
    }

    public function testSetSessionId(): void
    {
        $sessionId = 'new-session-456';
        $this->manager->setSessionId($sessionId);

        $this->assertEquals($sessionId, $this->manager->getSessionId());
    }

    public function testSetSessionIdToNull(): void
    {
        $this->manager->setSessionId('test-session');
        $this->manager->setSessionId(null);

        $this->assertNull($this->manager->getSessionId());
    }

    public function testGetDefaultHeaders(): void
    {
        $headers = $this->manager->getDefaultHeaders();

        $this->assertIsArray($headers);
        $this->assertArrayHasKey(ProtocolConstants::HTTP_HEADER_CONTENT_TYPE, $headers);
        $this->assertArrayHasKey(ProtocolConstants::HTTP_HEADER_ACCEPT, $headers);
        $this->assertArrayHasKey(ProtocolConstants::HTTP_HEADER_USER_AGENT, $headers);

        $this->assertEquals(ProtocolConstants::HTTP_CONTENT_TYPE_JSON, $headers[ProtocolConstants::HTTP_HEADER_CONTENT_TYPE]);
        $this->assertEquals(ProtocolConstants::HTTP_ACCEPT_SSE_JSON, $headers[ProtocolConstants::HTTP_HEADER_ACCEPT]);
        $this->assertEquals($this->config->getUserAgent(), $headers[ProtocolConstants::HTTP_HEADER_USER_AGENT]);
    }

    public function testGetDefaultHeadersWithSession(): void
    {
        $sessionId = 'test-session-789';
        $this->manager->setSessionId($sessionId);

        $headers = $this->manager->getDefaultHeaders();

        $this->assertArrayHasKey(ProtocolConstants::HTTP_HEADER_SESSION_ID, $headers);
        $this->assertEquals($sessionId, $headers[ProtocolConstants::HTTP_HEADER_SESSION_ID]);
    }

    public function testGetDefaultHeadersWithCustomHeaders(): void
    {
        $customHeaders = ['X-Custom-Header' => 'custom-value', 'Authorization' => 'Bearer token'];
        $this->config->setHeaders($customHeaders);

        // Create new manager to apply custom headers
        $manager = new HttpConnectionManager($this->config, $this->logger);
        $headers = $manager->getDefaultHeaders();

        foreach ($customHeaders as $name => $value) {
            $this->assertArrayHasKey($name, $headers);
            $this->assertEquals($value, $headers[$name]);
        }
    }

    public function testUpdateSessionHeaders(): void
    {
        $sessionId = 'update-test-session';
        $this->manager->setSessionId($sessionId);
        $this->manager->updateSessionHeaders();

        $headers = $this->manager->getDefaultHeaders();
        $this->assertArrayHasKey(ProtocolConstants::HTTP_HEADER_SESSION_ID, $headers);
        $this->assertEquals($sessionId, $headers[ProtocolConstants::HTTP_HEADER_SESSION_ID]);
    }

    public function testUpdateSessionHeadersWithoutSession(): void
    {
        $this->manager->setSessionId('temp-session');
        $this->manager->setSessionId(null);
        $this->manager->updateSessionHeaders();

        $headers = $this->manager->getDefaultHeaders();
        $this->assertArrayNotHasKey(ProtocolConstants::HTTP_HEADER_SESSION_ID, $headers);
    }

    public function testIsSessionValidWithoutSession(): void
    {
        $this->assertFalse($this->manager->isSessionValid());
    }

    public function testIsSessionValidWithSession(): void
    {
        $this->manager->setSessionId('valid-session');
        $this->assertTrue($this->manager->isSessionValid());
    }

    public function testIsSessionValidWithMaxAge(): void
    {
        $this->manager->setSessionId('age-test-session');

        // Should be valid with a large max age
        $this->assertTrue($this->manager->isSessionValid(3600));

        // Should be invalid with a very small max age
        usleep(10000); // Wait 10ms for more reliable timing
        $this->assertFalse($this->manager->isSessionValid(0.001));
    }

    public function testGetSessionMetadata(): void
    {
        $sessionId = 'metadata-test-session';
        $this->manager->setSessionId($sessionId);

        $metadata = $this->manager->getSessionMetadata();

        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('session_id', $metadata);
        $this->assertArrayHasKey('created_at', $metadata);
        $this->assertArrayHasKey('last_activity_at', $metadata);
        $this->assertArrayHasKey('age', $metadata);
        $this->assertArrayHasKey('idle_time', $metadata);
        $this->assertArrayHasKey('base_url', $metadata);
        $this->assertArrayHasKey('session_resumable', $metadata);

        $this->assertEquals($sessionId, $metadata['session_id']);
        $this->assertEquals($this->config->getBaseUrl(), $metadata['base_url']);
        $this->assertEquals($this->config->isSessionResumable(), $metadata['session_resumable']);
        $this->assertIsFloat($metadata['created_at']);
        $this->assertIsFloat($metadata['last_activity_at']);
        $this->assertIsFloat($metadata['age']);
        $this->assertIsFloat($metadata['idle_time']);
    }

    public function testGetSessionMetadataWithoutSession(): void
    {
        $metadata = $this->manager->getSessionMetadata();

        $this->assertNull($metadata['session_id']);
        $this->assertNull($metadata['created_at']);
        $this->assertNull($metadata['last_activity_at']);
        $this->assertNull($metadata['age']);
        $this->assertNull($metadata['idle_time']);
    }

    public function testClearSession(): void
    {
        $this->manager->setSessionId('clear-test-session');
        $this->assertNotNull($this->manager->getSessionId());

        $this->manager->clearSession();

        $this->assertNull($this->manager->getSessionId());

        $headers = $this->manager->getDefaultHeaders();
        $this->assertArrayNotHasKey(ProtocolConstants::HTTP_HEADER_SESSION_ID, $headers);
    }

    public function testReset(): void
    {
        $this->manager->initialize('reset-test-session');
        $this->assertTrue($this->manager->isInitialized());
        $this->assertNotNull($this->manager->getSessionId());

        $this->manager->reset();

        $this->assertFalse($this->manager->isInitialized());
        $this->assertNull($this->manager->getSessionId());
    }

    public function testGetStats(): void
    {
        $stats = $this->manager->getStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('initializations', $stats);
        $this->assertArrayHasKey('sessions_created', $stats);
        $this->assertArrayHasKey('sessions_cleared', $stats);
        $this->assertArrayHasKey('activity_updates', $stats);
        $this->assertArrayHasKey('created_at', $stats);
        $this->assertArrayHasKey('initialized', $stats);
        $this->assertArrayHasKey('has_session', $stats);
        $this->assertArrayHasKey('session_metadata', $stats);

        // Initial values
        $this->assertEquals(0, $stats['initializations']);
        $this->assertEquals(0, $stats['sessions_created']);
        $this->assertEquals(0, $stats['sessions_cleared']);
        $this->assertFalse($stats['initialized']);
        $this->assertFalse($stats['has_session']);
        $this->assertIsFloat($stats['created_at']);
        $this->assertIsArray($stats['session_metadata']);
    }

    public function testStatsAfterOperations(): void
    {
        $this->manager->initialize();
        $this->manager->setSessionId('stats-test-session');
        $this->manager->clearSession();

        $stats = $this->manager->getStats();

        $this->assertEquals(1, $stats['initializations']);
        $this->assertEquals(1, $stats['sessions_created']);
        $this->assertEquals(1, $stats['sessions_cleared']);
        $this->assertTrue($stats['initialized']);
        $this->assertFalse($stats['has_session']); // Cleared
    }

    public function testBuildDefaultHeaders(): void
    {
        $reflection = new ReflectionClass($this->manager);
        $method = $reflection->getMethod('buildDefaultHeaders');
        $method->setAccessible(true);

        $method->invoke($this->manager);

        $headers = $this->manager->getDefaultHeaders();
        $this->assertIsArray($headers);
        $this->assertArrayHasKey(ProtocolConstants::HTTP_HEADER_CONTENT_TYPE, $headers);
        $this->assertArrayHasKey(ProtocolConstants::HTTP_HEADER_ACCEPT, $headers);
        $this->assertArrayHasKey(ProtocolConstants::HTTP_HEADER_USER_AGENT, $headers);
    }

    public function testUpdateActivity(): void
    {
        $reflection = new ReflectionClass($this->manager);
        $updateActivityMethod = $reflection->getMethod('updateActivity');
        $updateActivityMethod->setAccessible(true);
        $lastActivityProperty = $reflection->getProperty('lastActivityAt');
        $lastActivityProperty->setAccessible(true);

        $this->assertNull($lastActivityProperty->getValue($this->manager));

        $updateActivityMethod->invoke($this->manager);

        $this->assertIsFloat($lastActivityProperty->getValue($this->manager));
    }

    public function testUpdateStats(): void
    {
        $reflection = new ReflectionClass($this->manager);
        $method = $reflection->getMethod('updateStats');
        $method->setAccessible(true);

        $method->invoke($this->manager, 'sessions_created');
        $method->invoke($this->manager, 'sessions_created');

        $stats = $this->manager->getStats();
        $this->assertEquals(2, $stats['sessions_created']);
    }

    public function testUpdateStatsWithValue(): void
    {
        $reflection = new ReflectionClass($this->manager);
        $method = $reflection->getMethod('updateStats');
        $method->setAccessible(true);

        $method->invoke($this->manager, 'custom_value', 42);

        $stats = $this->manager->getStats();
        $this->assertEquals(42, $stats['custom_value']);
    }
}
