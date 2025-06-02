<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Client\Configuration;

use Dtyq\PhpMcp\Client\Configuration\HttpConfig;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for HttpConfig class.
 * @internal
 */
class HttpConfigTest extends TestCase
{
    public function testConstructorWithDefaults(): void
    {
        $config = new HttpConfig('https://example.com');

        $this->assertEquals('https://example.com', $config->getBaseUrl());
        $this->assertEquals(30.0, $config->getTimeout());
        $this->assertEquals(300.0, $config->getSseTimeout());
        $this->assertEquals(3, $config->getMaxRetries());
        $this->assertEquals(1.0, $config->getRetryDelay());
        $this->assertTrue($config->getValidateSsl());
        $this->assertEquals('php-mcp-client/1.0', $config->getUserAgent());
        $this->assertEquals([], $config->getHeaders());
        $this->assertNull($config->getAuth());
        $this->assertEquals('auto', $config->getProtocolVersion());
        $this->assertTrue($config->isResumptionEnabled());
        $this->assertEquals('memory', $config->getEventStoreType());
        $this->assertEquals([], $config->getEventStoreConfig());
        $this->assertFalse($config->isJsonResponseMode());
        $this->assertTrue($config->shouldTerminateOnClose());
    }

    public function testConstructorWithCustomValues(): void
    {
        $headers = ['X-Custom' => 'value'];
        $auth = ['type' => 'bearer', 'token' => 'token123'];

        $config = new HttpConfig(
            'https://api.example.com',
            60.0,
            600.0,
            5,
            2.0,
            false,
            'custom-agent/1.0',
            $headers,
            $auth,
            '2025-03-26',
            false,
            'redis',
            ['host' => 'localhost'],
            true,
            false
        );

        $this->assertEquals('https://api.example.com', $config->getBaseUrl());
        $this->assertEquals(60.0, $config->getTimeout());
        $this->assertEquals(600.0, $config->getSseTimeout());
        $this->assertEquals(5, $config->getMaxRetries());
        $this->assertEquals(2.0, $config->getRetryDelay());
        $this->assertFalse($config->getValidateSsl());
        $this->assertEquals('custom-agent/1.0', $config->getUserAgent());
        $this->assertEquals($headers, $config->getHeaders());
        $this->assertEquals($auth, $config->getAuth());
        $this->assertEquals('2025-03-26', $config->getProtocolVersion());
        $this->assertFalse($config->isResumptionEnabled());
        $this->assertEquals('redis', $config->getEventStoreType());
        $this->assertEquals(['host' => 'localhost'], $config->getEventStoreConfig());
        $this->assertTrue($config->isJsonResponseMode());
        $this->assertFalse($config->shouldTerminateOnClose());
    }

    public function testFromArray(): void
    {
        $configArray = [
            'base_url' => 'https://example.com',
            'timeout' => 45.0,
            'sse_timeout' => 400.0,
            'max_retries' => 2,
            'retry_delay' => 1.5,
            'validate_ssl' => false,
            'user_agent' => 'test-agent/1.0',
            'headers' => ['Authorization' => 'Bearer token'],
            'auth' => ['type' => 'basic', 'username' => 'user', 'password' => 'pass'],
            'protocol_version' => '2024-11-05',
            'enable_resumption' => false,
            'event_store_type' => 'file',
            'event_store_config' => ['path' => '/tmp/events'],
            'json_response_mode' => true,
            'terminate_on_close' => false,
        ];

        $config = HttpConfig::fromArray($configArray);

        $this->assertEquals('https://example.com', $config->getBaseUrl());
        $this->assertEquals(45.0, $config->getTimeout());
        $this->assertEquals(400.0, $config->getSseTimeout());
        $this->assertEquals(2, $config->getMaxRetries());
        $this->assertEquals(1.5, $config->getRetryDelay());
        $this->assertFalse($config->getValidateSsl());
        $this->assertEquals('test-agent/1.0', $config->getUserAgent());
        $this->assertEquals(['Authorization' => 'Bearer token'], $config->getHeaders());
        $this->assertEquals(['type' => 'basic', 'username' => 'user', 'password' => 'pass'], $config->getAuth());
        $this->assertEquals('2024-11-05', $config->getProtocolVersion());
        $this->assertFalse($config->isResumptionEnabled());
        $this->assertEquals('file', $config->getEventStoreType());
        $this->assertEquals(['path' => '/tmp/events'], $config->getEventStoreConfig());
        $this->assertTrue($config->isJsonResponseMode());
        $this->assertFalse($config->shouldTerminateOnClose());
    }

    public function testFromArrayWithDefaults(): void
    {
        $configArray = ['base_url' => 'https://example.com'];
        $config = HttpConfig::fromArray($configArray);

        $this->assertEquals('https://example.com', $config->getBaseUrl());
        $this->assertEquals(HttpConfig::DEFAULTS['timeout'], $config->getTimeout());
    }

    public function testGetDefaults(): void
    {
        $defaults = HttpConfig::getDefaults();
        $this->assertIsArray($defaults);
        $this->assertArrayHasKey('base_url', $defaults);
        $this->assertArrayHasKey('timeout', $defaults);
        $this->assertEquals(30.0, $defaults['timeout']);
    }

    public function testToArray(): void
    {
        $config = new HttpConfig('https://example.com', 60.0);
        $array = $config->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('https://example.com', $array['base_url']);
        $this->assertEquals(60.0, $array['timeout']);
        $this->assertArrayHasKey('sse_timeout', $array);
        $this->assertArrayHasKey('max_retries', $array);
    }

    public function testJsonSerialize(): void
    {
        $config = new HttpConfig('https://example.com');
        $json = json_encode($config);
        $this->assertIsString($json);

        $decoded = json_decode($json, true);
        $this->assertEquals('https://example.com', $decoded['base_url']);
    }

    public function testWithChanges(): void
    {
        $original = new HttpConfig('https://example.com');
        $modified = $original->withChanges(['timeout' => 60.0, 'max_retries' => 5]);

        // Original should be unchanged
        $this->assertEquals(30.0, $original->getTimeout());
        $this->assertEquals(3, $original->getMaxRetries());

        // Modified should have changes
        $this->assertEquals(60.0, $modified->getTimeout());
        $this->assertEquals(5, $modified->getMaxRetries());
        $this->assertEquals('https://example.com', $modified->getBaseUrl());
    }

    public function testInvalidBaseUrl(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('must be a valid URL');

        new HttpConfig('invalid-url');
    }

    public function testEmptyBaseUrlWhenProvided(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('cannot be empty when provided');

        new HttpConfig('   ');
    }

    public function testInvalidTimeout(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('must be greater than 0');

        new HttpConfig('https://example.com', 0.0);
    }

    public function testNegativeTimeout(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('must be greater than 0');

        new HttpConfig('https://example.com', -1.0);
    }

    public function testInvalidSseTimeout(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('must be greater than 0');

        new HttpConfig('https://example.com', 30.0, 0.0);
    }

    public function testNegativeMaxRetries(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('cannot be negative');

        new HttpConfig('https://example.com', 30.0, 300.0, -1);
    }

    public function testNegativeRetryDelay(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('cannot be negative');

        new HttpConfig('https://example.com', 30.0, 300.0, 3, -1.0);
    }

    public function testEmptyUserAgent(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('cannot be empty');

        new HttpConfig('https://example.com', 30.0, 300.0, 3, 1.0, true, '');
    }

    public function testInvalidHeaders(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('must be an array of string key-value pairs');

        new HttpConfig('https://example.com', 30.0, 300.0, 3, 1.0, true, 'agent', [123 => 'value']);
    }

    public function testInvalidProtocolVersion(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('must be one of');

        new HttpConfig('https://example.com', 30.0, 300.0, 3, 1.0, true, 'agent', [], null, 'invalid');
    }

    public function testInvalidEventStoreType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('must be one of');

        new HttpConfig('https://example.com', 30.0, 300.0, 3, 1.0, true, 'agent', [], null, 'auto', true, 'invalid');
    }

    public function testValidateRequiresBaseUrl(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('base_url');

        $config = new HttpConfig(null);
        $config->validate();
    }

    public function testValidateBearerAuth(): void
    {
        $auth = ['type' => 'bearer', 'token' => 'valid-token'];
        $config = new HttpConfig('https://example.com', auth: $auth);
        $config->validate();

        $this->addToAssertionCount(1); // No exception thrown
    }

    public function testValidateInvalidBearerAuth(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('is required for bearer authentication');

        $auth = ['type' => 'bearer']; // Missing token
        new HttpConfig('https://example.com', auth: $auth);
    }

    public function testValidateBasicAuth(): void
    {
        $auth = ['type' => 'basic', 'username' => 'user', 'password' => 'pass'];
        $config = new HttpConfig('https://example.com', auth: $auth);
        $config->validate();

        $this->addToAssertionCount(1); // No exception thrown
    }

    public function testValidateInvalidBasicAuth(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('is required for basic authentication');

        $auth = ['type' => 'basic', 'username' => 'user']; // Missing password
        new HttpConfig('https://example.com', auth: $auth);
    }

    public function testValidateOAuth2Auth(): void
    {
        $auth = ['type' => 'oauth2', 'client_id' => 'id', 'client_secret' => 'secret'];
        $config = new HttpConfig('https://example.com', auth: $auth);
        $config->validate();

        $this->addToAssertionCount(1); // No exception thrown
    }

    public function testValidateInvalidOAuth2Auth(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('is required for OAuth2 authentication');

        $auth = ['type' => 'oauth2', 'client_id' => 'id']; // Missing client_secret
        new HttpConfig('https://example.com', auth: $auth);
    }

    public function testValidateCustomAuth(): void
    {
        $auth = ['type' => 'custom', 'headers' => ['X-API-Key' => 'key123']];
        $config = new HttpConfig('https://example.com', auth: $auth);
        $config->validate();

        $this->addToAssertionCount(1); // No exception thrown
    }

    public function testValidateInvalidCustomAuth(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('is required for custom authentication');

        $auth = ['type' => 'custom']; // Missing headers
        new HttpConfig('https://example.com', auth: $auth);
    }

    public function testValidateInvalidAuthType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('must be one of');

        $auth = ['type' => 'invalid'];
        new HttpConfig('https://example.com', auth: $auth);
    }

    public function testValidateEmptyAuthType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('auth.type');

        $auth = []; // Missing type
        new HttpConfig('https://example.com', auth: $auth);
    }
}
