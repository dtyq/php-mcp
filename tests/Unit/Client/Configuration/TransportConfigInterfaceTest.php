<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Client\Configuration;

use Dtyq\PhpMcp\Client\Configuration\HttpConfig;
use Dtyq\PhpMcp\Client\Configuration\StdioConfig;
use Dtyq\PhpMcp\Client\Configuration\TransportConfigInterface;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class TransportConfigInterfaceTest extends TestCase
{
    public function testStdioConfigImplementsInterface(): void
    {
        $config = new StdioConfig('php');
        $this->assertInstanceOf(TransportConfigInterface::class, $config);
    }

    public function testHttpConfigImplementsInterface(): void
    {
        $config = new HttpConfig('https://example.com');
        $this->assertInstanceOf(TransportConfigInterface::class, $config);
    }

    public function testStdioConfigFromArray(): void
    {
        $config = StdioConfig::fromArray([
            'command' => 'php',
            'args' => ['server.php'],
            'read_timeout' => 30.0,
        ]);

        $this->assertInstanceOf(TransportConfigInterface::class, $config);
        $this->assertSame(['php', 'server.php'], $config->getCommand());
        $this->assertSame(30.0, $config->getReadTimeout());
    }

    public function testHttpConfigFromArray(): void
    {
        $config = HttpConfig::fromArray([
            'base_url' => 'https://api.example.com',
            'timeout' => 20.0,
            'validate_ssl' => false,
        ]);

        $this->assertInstanceOf(TransportConfigInterface::class, $config);
        $this->assertSame('https://api.example.com', $config->getBaseUrl());
        $this->assertSame(20.0, $config->getTimeout());
        $this->assertFalse($config->getValidateSsl());
    }

    public function testStdioConfigToArray(): void
    {
        $config = new StdioConfig('python', ['script.py']);
        $array = $config->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('command', $array);
        $this->assertArrayHasKey('read_timeout', $array);
        $this->assertSame(['python', 'script.py'], $array['command']);
    }

    public function testHttpConfigToArray(): void
    {
        $config = new HttpConfig('https://example.com');
        $array = $config->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('base_url', $array);
        $this->assertArrayHasKey('timeout', $array);
        $this->assertSame('https://example.com', $array['base_url']);
    }

    public function testStdioConfigValidate(): void
    {
        $config = new StdioConfig('php');

        // Should not throw exception
        $config->validate();
        $this->assertTrue(true);
    }

    public function testHttpConfigValidate(): void
    {
        $config = new HttpConfig('https://example.com');

        // Should not throw exception
        $config->validate();
        $this->assertTrue(true);
    }

    public function testHttpConfigValidateWithInvalidBaseUrl(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('must be a valid URL');

        $config = new HttpConfig('invalid-url');
        $config->validate();
    }

    public function testStdioConfigWithChanges(): void
    {
        $config = new StdioConfig('php');
        $newConfig = $config->withChanges([
            'command' => 'python',
            'read_timeout' => 45.0,
        ]);

        $this->assertInstanceOf(TransportConfigInterface::class, $newConfig);
        $this->assertSame(['python'], $newConfig->getCommand());
        $this->assertSame(45.0, $newConfig->getReadTimeout());

        // Original should be unchanged
        $this->assertSame(['php'], $config->getCommand());
        $this->assertSame(30.0, $config->getReadTimeout());
    }

    public function testHttpConfigWithChanges(): void
    {
        $config = new HttpConfig('https://example.com');
        $newConfig = $config->withChanges([
            'base_url' => 'https://api.example.com',
            'timeout' => 45.0,
        ]);

        $this->assertInstanceOf(TransportConfigInterface::class, $newConfig);
        $this->assertSame('https://api.example.com', $newConfig->getBaseUrl());
        $this->assertSame(45.0, $newConfig->getTimeout());

        // Original should be unchanged
        $this->assertSame('https://example.com', $config->getBaseUrl());
        $this->assertSame(30.0, $config->getTimeout());
    }

    public function testStdioConfigJsonSerialize(): void
    {
        $config = new StdioConfig('node', ['server.js']);
        $json = $config->jsonSerialize();

        $this->assertIsArray($json);
        $this->assertArrayHasKey('command', $json);
        $this->assertSame(['node', 'server.js'], $json['command']);
    }

    public function testHttpConfigJsonSerialize(): void
    {
        $config = new HttpConfig('https://example.com');
        $json = $config->jsonSerialize();

        $this->assertIsArray($json);
        $this->assertArrayHasKey('base_url', $json);
        $this->assertSame('https://example.com', $json['base_url']);
    }
}
