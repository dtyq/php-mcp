<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Client\Session;

use Dtyq\PhpMcp\Client\Core\TransportInterface;
use Dtyq\PhpMcp\Client\Session\ClientSession;
use Dtyq\PhpMcp\Client\Session\SessionFactory;
use Dtyq\PhpMcp\Client\Session\SessionMetadata;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test case for SessionFactory.
 * @internal
 */
class SessionFactoryTest extends TestCase
{
    /** @var MockObject&TransportInterface */
    private TransportInterface $mockTransport;

    protected function setUp(): void
    {
        $this->mockTransport = $this->createMockTransport();
    }

    public function testCreate(): void
    {
        $session = SessionFactory::create($this->mockTransport);

        $this->assertInstanceOf(ClientSession::class, $session);
    }

    public function testCreateWithConfig(): void
    {
        $config = [
            'client_name' => 'test-client',
            'client_version' => '2.0.0',
            'response_timeout' => 60.0,
        ];

        $session = SessionFactory::create($this->mockTransport, $config);

        $this->assertInstanceOf(ClientSession::class, $session);
    }

    public function testCreateWithCapabilities(): void
    {
        $capabilities = ['tools' => ['list' => true]];

        $session = SessionFactory::create($this->mockTransport, null, $capabilities);

        $this->assertInstanceOf(ClientSession::class, $session);
    }

    public function testCreateForStdio(): void
    {
        $session = SessionFactory::createForStdio($this->mockTransport);

        $this->assertInstanceOf(ClientSession::class, $session);
    }

    public function testCreateForStdioWithConfig(): void
    {
        $config = [
            'response_timeout' => 45.0,
            'client_name' => 'custom-stdio-client',
        ];

        $session = SessionFactory::createForStdio($this->mockTransport, $config);

        $this->assertInstanceOf(ClientSession::class, $session);
    }

    public function testCreateForDevelopment(): void
    {
        $session = SessionFactory::createForDevelopment($this->mockTransport);

        $this->assertInstanceOf(ClientSession::class, $session);
    }

    public function testCreateForDevelopmentWithConfig(): void
    {
        $config = [
            'response_timeout' => 300.0,
            'client_name' => 'dev-client',
        ];

        $session = SessionFactory::createForDevelopment($this->mockTransport, $config);

        $this->assertInstanceOf(ClientSession::class, $session);
    }

    public function testCreateMetadata(): void
    {
        $config = [
            'client_name' => 'test-client',
            'client_version' => '1.0.0',
            'response_timeout' => 30.0,
        ];

        $metadata = SessionFactory::createMetadata($config);

        $this->assertInstanceOf(SessionMetadata::class, $metadata);
    }

    public function testCreateMetadataWithInvalidTimeout(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('must be greater than 0');

        SessionFactory::createMetadata([
            'response_timeout' => -1.0,
        ]);
    }

    public function testCreateMetadataWithInvalidInitializationTimeout(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('must be greater than 0');

        SessionFactory::createMetadata([
            'initialization_timeout' => 0.0,
        ]);
    }

    public function testValidateTransport(): void
    {
        // Should not throw for connected transport
        SessionFactory::validateTransport($this->mockTransport);

        $this->addToAssertionCount(1);
    }

    public function testValidateTransportNotConnected(): void
    {
        /** @var MockObject&TransportInterface $disconnectedTransport */
        $disconnectedTransport = $this->createMock(TransportInterface::class);
        $disconnectedTransport->method('isConnected')->willReturn(false);
        $disconnectedTransport->method('getType')->willReturn('stdio');

        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('must be connected before creating session');

        SessionFactory::validateTransport($disconnectedTransport);
    }

    public function testCreateMultiple(): void
    {
        $transports = [
            $this->createMockTransport(),
            $this->createMockTransport(),
            $this->createMockTransport(),
        ];

        $sessions = SessionFactory::createMultiple($transports);

        $this->assertIsArray($sessions);
        $this->assertCount(3, $sessions);

        foreach ($sessions as $session) {
            $this->assertInstanceOf(ClientSession::class, $session);
        }
    }

    public function testCreateMultipleWithEmptyArray(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('transports');

        SessionFactory::createMultiple([]);
    }

    public function testCreateMultipleWithInvalidTransport(): void
    {
        $transports = [
            $this->createMockTransport(),
            'invalid-transport', // Invalid type
        ];

        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('TransportInterface');

        SessionFactory::createMultiple($transports);
    }

    /**
     * @return MockObject&TransportInterface
     */
    private function createMockTransport(): TransportInterface
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->method('isConnected')->willReturn(true);
        $transport->method('getType')->willReturn('stdio');

        return $transport;
    }
}
