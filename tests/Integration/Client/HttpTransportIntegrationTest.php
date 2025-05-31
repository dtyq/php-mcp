<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Integration\Client;

use Dtyq\PhpMcp\Client\Configuration\HttpConfig;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use PHPUnit\Framework\TestCase;

/**
 * Integration test case for HTTP transport configuration.
 *
 * These tests verify the HTTP transport configuration functionality
 * and basic validation without requiring complex application setup.
 *
 * @internal
 */
class HttpTransportIntegrationTest extends TestCase
{
    public function testHttpConfigValidation(): void
    {
        // Test valid configuration
        $validConfig = [
            'base_url' => 'https://api.example.com/mcp',
            'timeout' => 30.0,
            'max_retries' => 3,
        ];

        $httpConfig = HttpConfig::fromArray($validConfig);
        $this->assertEquals('https://api.example.com/mcp', $httpConfig->getBaseUrl());
        $this->assertEquals(30.0, $httpConfig->getTimeout());
        $this->assertEquals(3, $httpConfig->getMaxRetries());

        // Test invalid configuration (missing base_url)
        $this->expectException(ValidationError::class);
        HttpConfig::fromArray(['timeout' => 30.0]);
    }

    public function testHttpConfigDefaultValues(): void
    {
        $config = new HttpConfig('https://api.example.com/mcp');

        $this->assertEquals('https://api.example.com/mcp', $config->getBaseUrl());
        $this->assertEquals(30.0, $config->getTimeout());
        $this->assertEquals(300.0, $config->getSseTimeout());
        $this->assertEquals(3, $config->getMaxRetries());
        $this->assertEquals(1.0, $config->getRetryDelay());
        $this->assertTrue($config->isSessionResumable());
        $this->assertTrue($config->shouldValidateSsl());
        $this->assertEquals('php-mcp-client/1.0', $config->getUserAgent());
        $this->assertEquals([], $config->getHeaders());
        $this->assertNull($config->getAuth());
    }

    public function testHttpConfigCustomValues(): void
    {
        $config = new HttpConfig(
            'https://custom.example.com/mcp',
            60.0,
            600.0,
            5,
            2.0,
            false,
            false,
            'custom-agent/2.0',
            ['X-Custom' => 'value'],
            ['type' => 'bearer', 'token' => 'test-token']
        );

        $this->assertEquals('https://custom.example.com/mcp', $config->getBaseUrl());
        $this->assertEquals(60.0, $config->getTimeout());
        $this->assertEquals(600.0, $config->getSseTimeout());
        $this->assertEquals(5, $config->getMaxRetries());
        $this->assertEquals(2.0, $config->getRetryDelay());
        $this->assertFalse($config->isSessionResumable());
        $this->assertFalse($config->shouldValidateSsl());
        $this->assertEquals('custom-agent/2.0', $config->getUserAgent());
        $this->assertEquals(['X-Custom' => 'value'], $config->getHeaders());
        $this->assertEquals(['type' => 'bearer', 'token' => 'test-token'], $config->getAuth());
    }

    public function testHttpConfigEndpointUrl(): void
    {
        $config = new HttpConfig('https://api.example.com/mcp');

        // Test base URL
        $this->assertEquals('https://api.example.com/mcp', $config->getEndpointUrl());

        // Test with endpoint path
        $this->assertEquals('https://api.example.com/mcp/status', $config->getEndpointUrl('status'));
        $this->assertEquals('https://api.example.com/mcp/status', $config->getEndpointUrl('/status'));
    }

    public function testHttpConfigSerialization(): void
    {
        $originalConfig = new HttpConfig(
            'https://api.example.com/mcp',
            45.0,
            350.0,
            4,
            1.5,
            true,
            false,
            'test-agent/1.0',
            ['X-Test' => 'value'],
            ['type' => 'bearer', 'token' => 'secret']
        );

        // Test JSON serialization
        $json = json_encode($originalConfig);
        $this->assertIsString($json);

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);

        // Recreate config from decoded data
        $restoredConfig = HttpConfig::fromArray($decoded);

        $this->assertEquals($originalConfig->getBaseUrl(), $restoredConfig->getBaseUrl());
        $this->assertEquals($originalConfig->getTimeout(), $restoredConfig->getTimeout());
        $this->assertEquals($originalConfig->getSseTimeout(), $restoredConfig->getSseTimeout());
        $this->assertEquals($originalConfig->getMaxRetries(), $restoredConfig->getMaxRetries());
        $this->assertEquals($originalConfig->getRetryDelay(), $restoredConfig->getRetryDelay());
        $this->assertEquals($originalConfig->isSessionResumable(), $restoredConfig->isSessionResumable());
        $this->assertEquals($originalConfig->shouldValidateSsl(), $restoredConfig->shouldValidateSsl());
        $this->assertEquals($originalConfig->getUserAgent(), $restoredConfig->getUserAgent());
        $this->assertEquals($originalConfig->getHeaders(), $restoredConfig->getHeaders());
        $this->assertEquals($originalConfig->getAuth(), $restoredConfig->getAuth());
    }

    public function testHttpConfigValidationWithInvalidUrl(): void
    {
        $this->expectException(ValidationError::class);
        new HttpConfig('invalid-url');
    }

    public function testHttpConfigValidationWithInvalidTimeout(): void
    {
        $this->expectException(ValidationError::class);
        new HttpConfig('https://api.example.com/mcp', -1.0);
    }

    public function testHttpConfigValidationWithInvalidRetries(): void
    {
        $this->expectException(ValidationError::class);
        new HttpConfig('https://api.example.com/mcp', 30.0, 300.0, -1);
    }

    public function testHttpConfigAuthenticationTypes(): void
    {
        // Test bearer auth
        $bearerConfig = new HttpConfig(
            'https://api.example.com/mcp',
            30.0,
            300.0,
            3,
            1.0,
            true,
            true,
            'test-agent',
            [],
            ['type' => 'bearer', 'token' => 'test-token']
        );
        $this->assertTrue($bearerConfig->hasAuth());
        $this->assertEquals('bearer', $bearerConfig->getAuthType());

        // Test basic auth
        $basicConfig = new HttpConfig(
            'https://api.example.com/mcp',
            30.0,
            300.0,
            3,
            1.0,
            true,
            true,
            'test-agent',
            [],
            ['type' => 'basic', 'username' => 'user', 'password' => 'pass']
        );
        $this->assertTrue($basicConfig->hasAuth());
        $this->assertEquals('basic', $basicConfig->getAuthType());

        // Test no auth
        $noAuthConfig = new HttpConfig('https://api.example.com/mcp');
        $this->assertFalse($noAuthConfig->hasAuth());
        $this->assertNull($noAuthConfig->getAuthType());
    }
}
