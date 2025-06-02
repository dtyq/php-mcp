<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Client\Configuration;

use Dtyq\PhpMcp\Client\Configuration\ClientConfig;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;
use PHPUnit\Framework\TestCase;

/**
 * Test case for ClientConfig.
 * @internal
 */
class ClientConfigTest extends TestCase
{
    public function testConstructorWithDefaults(): void
    {
        $config = new ClientConfig();

        $this->assertEquals(ProtocolConstants::TRANSPORT_TYPE_STDIO, $config->getTransportType());
        $this->assertEquals([], $config->getTransportConfig());
        $this->assertEquals(30, $config->getDefaultTimeout());
        $this->assertEquals(3, $config->getMaxRetries());
        $this->assertEquals('php-mcp-client', $config->getClientName());
        $this->assertEquals('1.0.0', $config->getClientVersion());
        $this->assertEquals([], $config->getCapabilities());
        $this->assertFalse($config->isDebug());
    }

    public function testConstructorWithCustomValues(): void
    {
        $transportConfig = ['command' => ['php', 'server.php']];
        $capabilities = ['tools' => true];

        $config = new ClientConfig(
            'custom-transport',
            $transportConfig,
            60,
            5,
            'custom-client',
            '2.0.0',
            $capabilities,
            true
        );

        $this->assertEquals('custom-transport', $config->getTransportType());
        $this->assertEquals($transportConfig, $config->getTransportConfig());
        $this->assertEquals(60, $config->getDefaultTimeout());
        $this->assertEquals(5, $config->getMaxRetries());
        $this->assertEquals('custom-client', $config->getClientName());
        $this->assertEquals('2.0.0', $config->getClientVersion());
        $this->assertEquals($capabilities, $config->getCapabilities());
        $this->assertTrue($config->isDebug());
    }

    public function testFromArray(): void
    {
        $data = [
            'transport_type' => 'test-transport',
            'transport_config' => ['key' => 'value'],
            'default_timeout' => 45,
            'max_retries' => 2,
            'client_name' => 'array-client',
            'client_version' => '1.5.0',
            'capabilities' => ['prompts' => true],
            'debug' => true,
        ];

        $config = ClientConfig::fromArray($data);

        $this->assertEquals('test-transport', $config->getTransportType());
        $this->assertEquals(['key' => 'value'], $config->getTransportConfig());
        $this->assertEquals(45, $config->getDefaultTimeout());
        $this->assertEquals(2, $config->getMaxRetries());
        $this->assertEquals('array-client', $config->getClientName());
        $this->assertEquals('1.5.0', $config->getClientVersion());
        $this->assertEquals(['prompts' => true], $config->getCapabilities());
        $this->assertTrue($config->isDebug());
    }

    public function testFromArrayWithPartialData(): void
    {
        $data = [
            'client_name' => 'partial-client',
            'default_timeout' => 90,
        ];

        $config = ClientConfig::fromArray($data);

        // Should merge with defaults
        $this->assertEquals(ProtocolConstants::TRANSPORT_TYPE_STDIO, $config->getTransportType());
        $this->assertEquals('partial-client', $config->getClientName());
        $this->assertEquals(90, $config->getDefaultTimeout());
        $this->assertEquals(3, $config->getMaxRetries()); // Default value
    }

    public function testToArray(): void
    {
        $config = new ClientConfig(
            'test-transport',
            ['test' => true],
            120,
            4,
            'test-client',
            '3.0.0',
            ['resources' => true],
            true
        );

        $expected = [
            'transport_type' => 'test-transport',
            'transport_config' => ['test' => true],
            'default_timeout' => 120,
            'max_retries' => 4,
            'client_name' => 'test-client',
            'client_version' => '3.0.0',
            'capabilities' => ['resources' => true],
            'debug' => true,
        ];

        $this->assertEquals($expected, $config->toArray());
    }

    public function testSetTransportTypeEmpty(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('transport_type');

        $config = new ClientConfig();
        $config->setTransportType('');
    }

    public function testSetDefaultTimeoutInvalid(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('must be greater than 0');

        $config = new ClientConfig();
        $config->setDefaultTimeout(0);
    }

    public function testSetDefaultTimeoutNegative(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('must be greater than 0');

        $config = new ClientConfig();
        $config->setDefaultTimeout(-5);
    }

    public function testSetMaxRetriesInvalid(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('must be non-negative');

        $config = new ClientConfig();
        $config->setMaxRetries(-1);
    }

    public function testSetClientNameEmpty(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('client_name');

        $config = new ClientConfig();
        $config->setClientName('');
    }

    public function testSetClientVersionEmpty(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('client_version');

        $config = new ClientConfig();
        $config->setClientVersion('');
    }

    public function testSetTransportConfig(): void
    {
        $config = new ClientConfig();
        $transportConfig = ['command' => 'test'];

        $config->setTransportConfig($transportConfig);

        $this->assertEquals($transportConfig, $config->getTransportConfig());
    }

    public function testSetCapabilities(): void
    {
        $config = new ClientConfig();
        $capabilities = ['tools' => true, 'prompts' => false];

        $config->setCapabilities($capabilities);

        $this->assertEquals($capabilities, $config->getCapabilities());
    }

    public function testSetDebug(): void
    {
        $config = new ClientConfig();

        $config->setDebug(true);
        $this->assertTrue($config->isDebug());

        $config->setDebug(false);
        $this->assertFalse($config->isDebug());
    }

    public function testValidate(): void
    {
        $config = new ClientConfig();

        // Should not throw for valid configuration
        $config->validate();

        $this->addToAssertionCount(1);
    }

    public function testWithChanges(): void
    {
        $original = new ClientConfig(
            'original-transport',
            ['original' => true],
            30,
            3,
            'original-client',
            '1.0.0',
            ['original' => true],
            false
        );

        $changes = [
            'client_name' => 'modified-client',
            'default_timeout' => 60,
            'debug' => true,
        ];

        $modified = $original->withChanges($changes);

        // Original should be unchanged
        $this->assertEquals('original-client', $original->getClientName());
        $this->assertEquals(30, $original->getDefaultTimeout());
        $this->assertFalse($original->isDebug());

        // Modified should have changes
        $this->assertEquals('modified-client', $modified->getClientName());
        $this->assertEquals(60, $modified->getDefaultTimeout());
        $this->assertTrue($modified->isDebug());

        // Unchanged values should be preserved
        $this->assertEquals('original-transport', $modified->getTransportType());
        $this->assertEquals(['original' => true], $modified->getTransportConfig());
    }

    public function testWithChangesInvalidValue(): void
    {
        $config = new ClientConfig();

        $this->expectException(ValidationError::class);

        $config->withChanges([
            'default_timeout' => -1,
        ]);
    }
}
