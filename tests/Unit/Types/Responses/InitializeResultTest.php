<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Types\Responses;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Responses\InitializeResult;
use PHPUnit\Framework\TestCase;

/**
 * Test case for InitializeResult class.
 * @internal
 */
class InitializeResultTest extends TestCase
{
    public function testConstructorWithValidData(): void
    {
        $protocolVersion = '2025-03-26';
        $capabilities = ['tools' => true, 'resources' => false];
        $serverInfo = ['name' => 'test-server', 'version' => '1.0.0'];
        $instructions = 'Use this server for testing';
        $meta = ['timestamp' => '2025-01-01T00:00:00Z'];

        $result = new InitializeResult($protocolVersion, $capabilities, $serverInfo, $instructions, $meta);

        $this->assertSame($protocolVersion, $result->getProtocolVersion());
        $this->assertSame($capabilities, $result->getCapabilities());
        $this->assertSame($serverInfo, $result->getServerInfo());
        $this->assertSame($instructions, $result->getInstructions());
        $this->assertTrue($result->hasMeta());
        $this->assertSame($meta, $result->getMeta());
        $this->assertFalse($result->isPaginated());
        $this->assertNull($result->getNextCursor());
    }

    public function testConstructorWithMinimalData(): void
    {
        $protocolVersion = '2025-03-26';
        $capabilities = [];
        $serverInfo = [];

        $result = new InitializeResult($protocolVersion, $capabilities, $serverInfo);

        $this->assertSame($protocolVersion, $result->getProtocolVersion());
        $this->assertSame($capabilities, $result->getCapabilities());
        $this->assertSame($serverInfo, $result->getServerInfo());
        $this->assertNull($result->getInstructions());
        $this->assertFalse($result->hasMeta());
        $this->assertNull($result->getMeta());
    }

    public function testSetProtocolVersion(): void
    {
        $result = new InitializeResult('1.0', [], []);

        $result->setProtocolVersion('2025-03-26');
        $this->assertSame('2025-03-26', $result->getProtocolVersion());
    }

    public function testSetProtocolVersionWithEmptyString(): void
    {
        $result = new InitializeResult('1.0', [], []);

        $this->expectException(ValidationError::class);
        $result->setProtocolVersion('');
    }

    public function testSetCapabilities(): void
    {
        $result = new InitializeResult('1.0', [], []);
        $capabilities = ['tools' => true, 'resources' => true];

        $result->setCapabilities($capabilities);
        $this->assertSame($capabilities, $result->getCapabilities());
    }

    public function testSetServerInfo(): void
    {
        $result = new InitializeResult('1.0', [], []);
        $serverInfo = ['name' => 'updated-server', 'version' => '2.0.0'];

        $result->setServerInfo($serverInfo);
        $this->assertSame($serverInfo, $result->getServerInfo());
    }

    public function testSetInstructions(): void
    {
        $result = new InitializeResult('1.0', [], []);

        $result->setInstructions('New instructions');
        $this->assertSame('New instructions', $result->getInstructions());

        $result->setInstructions(null);
        $this->assertNull($result->getInstructions());
    }

    public function testSetMeta(): void
    {
        $result = new InitializeResult('1.0', [], []);
        $meta = ['key' => 'value'];

        $result->setMeta($meta);
        $this->assertTrue($result->hasMeta());
        $this->assertSame($meta, $result->getMeta());

        $result->setMeta(null);
        $this->assertFalse($result->hasMeta());
        $this->assertNull($result->getMeta());
    }

    public function testToArray(): void
    {
        $protocolVersion = '2025-03-26';
        $capabilities = ['tools' => true];
        $serverInfo = ['name' => 'test'];
        $instructions = 'Test instructions';
        $meta = ['timestamp' => '2025-01-01T00:00:00Z'];

        $result = new InitializeResult($protocolVersion, $capabilities, $serverInfo, $instructions, $meta);

        $array = $result->toArray();
        $this->assertSame($protocolVersion, $array['protocolVersion']);
        $this->assertSame($capabilities, $array['capabilities']);
        $this->assertSame($serverInfo, $array['serverInfo']);
        $this->assertSame($instructions, $array['instructions']);
        $this->assertSame($meta, $array['_meta']);
    }

    public function testToArrayWithoutOptionalFields(): void
    {
        $result = new InitializeResult('1.0', [], []);

        $array = $result->toArray();
        $this->assertSame('1.0', $array['protocolVersion']);
        $this->assertSame([], $array['capabilities']);
        $this->assertSame([], $array['serverInfo']);
        $this->assertArrayNotHasKey('instructions', $array);
        $this->assertArrayNotHasKey('_meta', $array);
    }

    public function testFromArrayWithValidData(): void
    {
        $data = [
            'protocolVersion' => '2025-03-26',
            'capabilities' => ['tools' => true],
            'serverInfo' => ['name' => 'test'],
            'instructions' => 'Test instructions',
            '_meta' => ['timestamp' => '2025-01-01T00:00:00Z'],
        ];

        $result = InitializeResult::fromArray($data);

        $this->assertSame('2025-03-26', $result->getProtocolVersion());
        $this->assertSame(['tools' => true], $result->getCapabilities());
        $this->assertSame(['name' => 'test'], $result->getServerInfo());
        $this->assertSame('Test instructions', $result->getInstructions());
        $this->assertTrue($result->hasMeta());
        $this->assertSame(['timestamp' => '2025-01-01T00:00:00Z'], $result->getMeta());
    }

    public function testFromArrayWithMinimalData(): void
    {
        $data = [
            'protocolVersion' => '2025-03-26',
            'capabilities' => [],
            'serverInfo' => [],
        ];

        $result = InitializeResult::fromArray($data);

        $this->assertSame('2025-03-26', $result->getProtocolVersion());
        $this->assertSame([], $result->getCapabilities());
        $this->assertSame([], $result->getServerInfo());
        $this->assertNull($result->getInstructions());
        $this->assertFalse($result->hasMeta());
    }

    public function testFromArrayMissingProtocolVersion(): void
    {
        $data = [
            'capabilities' => [],
            'serverInfo' => [],
        ];

        $this->expectException(ValidationError::class);
        InitializeResult::fromArray($data);
    }

    public function testFromArrayMissingCapabilities(): void
    {
        $data = [
            'protocolVersion' => '2025-03-26',
            'serverInfo' => [],
        ];

        $this->expectException(ValidationError::class);
        InitializeResult::fromArray($data);
    }

    public function testFromArrayMissingServerInfo(): void
    {
        $data = [
            'protocolVersion' => '2025-03-26',
            'capabilities' => [],
        ];

        $this->expectException(ValidationError::class);
        InitializeResult::fromArray($data);
    }

    public function testPaginationMethods(): void
    {
        $result = new InitializeResult('1.0', [], []);

        $this->assertFalse($result->isPaginated());
        $this->assertNull($result->getNextCursor());
    }
}
