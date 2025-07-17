<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Client\Configuration;

use Dtyq\PhpMcp\Client\Configuration\ConnectionConfig;
use Dtyq\PhpMcp\Client\Configuration\HttpConfig;
use Dtyq\PhpMcp\Client\Configuration\StdioConfig;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Constants\TransportTypes;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ConnectionConfigTest extends TestCase
{
    public function testStdioConnectionConfig(): void
    {
        $config = ConnectionConfig::stdio(
            'php',
            ['server.php'],
            30.0,
            10.0
        );

        $this->assertSame(TransportTypes::TRANSPORT_TYPE_STDIO, $config->getTransportType());
        $this->assertTrue($config->isStdio());
        $this->assertFalse($config->isHttp());

        $stdioConfig = $config->getStdioConfig();
        $this->assertInstanceOf(StdioConfig::class, $stdioConfig);
        $this->assertSame(['php', 'server.php'], $stdioConfig->getCommand());
        $this->assertSame(30.0, $stdioConfig->getReadTimeout());
        $this->assertSame(10.0, $stdioConfig->getWriteTimeout());
    }

    public function testHttpConnectionConfig(): void
    {
        $config = ConnectionConfig::http(
            'https://example.com',
            15.0,
            300.0,
            3,
            1.0,
            false
        );

        $this->assertSame(TransportTypes::TRANSPORT_TYPE_HTTP, $config->getTransportType());
        $this->assertFalse($config->isStdio());
        $this->assertTrue($config->isHttp());

        $httpConfig = $config->getHttpConfig();
        $this->assertInstanceOf(HttpConfig::class, $httpConfig);
        $this->assertSame('https://example.com', $httpConfig->getBaseUrl());
        $this->assertSame(15.0, $httpConfig->getTimeout());
        $this->assertFalse($httpConfig->getValidateSsl());
    }

    public function testFromArrayStdio(): void
    {
        $config = ConnectionConfig::fromArray('stdio', [
            'command' => 'python',
            'args' => ['script.py'],
            'read_timeout' => 25.0,
        ]);

        $this->assertTrue($config->isStdio());
        $stdioConfig = $config->getStdioConfig();
        $this->assertSame(['python', 'script.py'], $stdioConfig->getCommand());
        $this->assertSame(25.0, $stdioConfig->getReadTimeout());
    }

    public function testFromArrayHttp(): void
    {
        $config = ConnectionConfig::fromArray('http', [
            'base_url' => 'https://api.example.com',
            'timeout' => 20.0,
        ]);

        $this->assertTrue($config->isHttp());
        $httpConfig = $config->getHttpConfig();
        $this->assertSame('https://api.example.com', $httpConfig->getBaseUrl());
        $this->assertSame(20.0, $httpConfig->getTimeout());
    }

    public function testInvalidTransportType(): void
    {
        $this->expectException(ValidationError::class);
        ConnectionConfig::fromArray('invalid', []);
    }

    public function testGetStdioConfigFromHttpConfig(): void
    {
        $config = ConnectionConfig::http('https://example.com');

        $this->expectException(ValidationError::class);
        $config->getStdioConfig();
    }

    public function testGetHttpConfigFromStdioConfig(): void
    {
        $config = ConnectionConfig::stdio('php');

        $this->expectException(ValidationError::class);
        $config->getHttpConfig();
    }

    public function testToArray(): void
    {
        $config = ConnectionConfig::stdio(
            'node',
            ['server.js'],
            20.0
        );

        $array = $config->toArray();
        $this->assertArrayHasKey('command', $array);
        $this->assertArrayHasKey('read_timeout', $array);
        $this->assertSame(['node', 'server.js'], $array['command']);
        $this->assertSame(20.0, $array['read_timeout']);
    }

    public function testJsonSerialize(): void
    {
        $config = ConnectionConfig::stdio('php');
        $json = $config->jsonSerialize();

        $this->assertArrayHasKey('transport_type', $json);
        $this->assertArrayHasKey('transport_config', $json);
        $this->assertSame(TransportTypes::TRANSPORT_TYPE_STDIO, $json['transport_type']);
    }
}
