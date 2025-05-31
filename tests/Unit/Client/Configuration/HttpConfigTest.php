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
 * Test case for HttpConfig.
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
        $this->assertTrue($config->isSessionResumable());
        $this->assertTrue($config->shouldValidateSsl());
        $this->assertEquals('php-mcp-client/1.0', $config->getUserAgent());
        $this->assertEquals([], $config->getHeaders());
        $this->assertNull($config->getAuth());
    }

    public function testConstructorWithCustomValues(): void
    {
        $headers = ['X-Custom' => 'value'];
        $auth = ['type' => 'bearer', 'token' => 'test-token'];

        $config = new HttpConfig(
            'http://localhost:8080',
            45.0,    // timeout
            600.0,   // sseTimeout
            5,       // maxRetries
            2.0,     // retryDelay
            false,   // sessionResumable
            false,   // validateSsl
            'custom-agent/2.0', // userAgent
            $headers,
            $auth
        );

        $this->assertEquals('http://localhost:8080', $config->getBaseUrl());
        $this->assertEquals(45.0, $config->getTimeout());
        $this->assertEquals(600.0, $config->getSseTimeout());
        $this->assertEquals(5, $config->getMaxRetries());
        $this->assertEquals(2.0, $config->getRetryDelay());
        $this->assertFalse($config->isSessionResumable());
        $this->assertFalse($config->shouldValidateSsl());
        $this->assertEquals('custom-agent/2.0', $config->getUserAgent());
        $this->assertEquals($headers, $config->getHeaders());
        $this->assertEquals($auth, $config->getAuth());
    }

    public function testFromArray(): void
    {
        $data = [
            'base_url' => 'https://api.example.com',
            'timeout' => 60.0,
            'sse_timeout' => 900.0,
            'max_retries' => 2,
            'retry_delay' => 3.0,
            'session_resumable' => false,
            'validate_ssl' => false,
            'user_agent' => 'test-agent/1.0',
            'headers' => ['Authorization' => 'Bearer token'],
            'auth' => ['type' => 'basic', 'username' => 'user', 'password' => 'pass'],
        ];

        $config = HttpConfig::fromArray($data);

        $this->assertEquals('https://api.example.com', $config->getBaseUrl());
        $this->assertEquals(60.0, $config->getTimeout());
        $this->assertEquals(900.0, $config->getSseTimeout());
        $this->assertEquals(2, $config->getMaxRetries());
        $this->assertEquals(3.0, $config->getRetryDelay());
        $this->assertFalse($config->isSessionResumable());
        $this->assertFalse($config->shouldValidateSsl());
        $this->assertEquals('test-agent/1.0', $config->getUserAgent());
        $this->assertEquals(['Authorization' => 'Bearer token'], $config->getHeaders());
        $this->assertEquals(['type' => 'basic', 'username' => 'user', 'password' => 'pass'], $config->getAuth());
    }

    public function testFromArrayWithPartialData(): void
    {
        $data = [
            'base_url' => 'https://api.example.com',
            'timeout' => 120.0,
            'validate_ssl' => false,
        ];

        $config = HttpConfig::fromArray($data);

        // Should merge with defaults
        $this->assertEquals('https://api.example.com', $config->getBaseUrl());
        $this->assertEquals(120.0, $config->getTimeout());
        $this->assertEquals(300.0, $config->getSseTimeout()); // Default
        $this->assertFalse($config->shouldValidateSsl());
        $this->assertEquals(3, $config->getMaxRetries()); // Default
    }

    public function testFromArrayMissingBaseUrl(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Field \'base_url\' cannot be empty');

        HttpConfig::fromArray(['timeout' => 30.0]);
    }

    public function testToArray(): void
    {
        $config = new HttpConfig(
            'https://test.com',
            45.0,
            600.0,
            2,
            1.5,
            false,
            true,
            'test-agent/1.0',
            ['X-Test' => 'value'],
            ['type' => 'bearer', 'token' => 'test']
        );

        $expected = [
            'base_url' => 'https://test.com',
            'timeout' => 45.0,
            'sse_timeout' => 600.0,
            'max_retries' => 2,
            'retry_delay' => 1.5,
            'session_resumable' => false,
            'validate_ssl' => true,
            'user_agent' => 'test-agent/1.0',
            'headers' => ['X-Test' => 'value'],
            'auth' => ['type' => 'bearer', 'token' => 'test'],
        ];

        $this->assertEquals($expected, $config->toArray());
    }

    public function testGetDefaults(): void
    {
        $defaults = HttpConfig::getDefaults();

        $this->assertIsArray($defaults);
        $this->assertArrayHasKey('timeout', $defaults);
        $this->assertArrayHasKey('sse_timeout', $defaults);
        $this->assertArrayHasKey('max_retries', $defaults);
        $this->assertArrayHasKey('retry_delay', $defaults);
        $this->assertArrayHasKey('session_resumable', $defaults);
        $this->assertArrayHasKey('validate_ssl', $defaults);
        $this->assertArrayHasKey('user_agent', $defaults);
        $this->assertArrayHasKey('headers', $defaults);
        $this->assertArrayHasKey('auth', $defaults);
    }

    public function testSetBaseUrl(): void
    {
        $config = new HttpConfig('https://example.com');

        $config->setBaseUrl('http://localhost:3000');
        $this->assertEquals('http://localhost:3000', $config->getBaseUrl());

        // Test trailing slash removal
        $config->setBaseUrl('https://api.test.com/');
        $this->assertEquals('https://api.test.com', $config->getBaseUrl());
    }

    public function testSetBaseUrlEmpty(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Field \'base_url\' cannot be empty');

        $config = new HttpConfig('https://example.com');
        $config->setBaseUrl('');
    }

    public function testSetBaseUrlInvalid(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('must be a valid URL');

        $config = new HttpConfig('https://example.com');
        $config->setBaseUrl('not-a-url');
    }

    public function testSetTimeoutInvalid(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('must be greater than 0');

        $config = new HttpConfig('https://example.com');
        $config->setTimeout(0.0);
    }

    public function testSetTimeoutNegative(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('must be greater than 0');

        $config = new HttpConfig('https://example.com');
        $config->setTimeout(-5.0);
    }

    public function testSetSseTimeoutInvalid(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('must be greater than 0');

        $config = new HttpConfig('https://example.com');
        $config->setSseTimeout(0.0);
    }

    public function testSetMaxRetriesNegative(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('must be greater than or equal to 0');

        $config = new HttpConfig('https://example.com');
        $config->setMaxRetries(-1);
    }

    public function testSetMaxRetriesZero(): void
    {
        $config = new HttpConfig('https://example.com');
        $config->setMaxRetries(0);
        $this->assertEquals(0, $config->getMaxRetries());
    }

    public function testSetRetryDelayNegative(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('must be greater than or equal to 0');

        $config = new HttpConfig('https://example.com');
        $config->setRetryDelay(-1.0);
    }

    public function testSetRetryDelayZero(): void
    {
        $config = new HttpConfig('https://example.com');
        $config->setRetryDelay(0.0);
        $this->assertEquals(0.0, $config->getRetryDelay());
    }

    public function testSetSessionResumable(): void
    {
        $config = new HttpConfig('https://example.com');

        $config->setSessionResumable(false);
        $this->assertFalse($config->isSessionResumable());

        $config->setSessionResumable(true);
        $this->assertTrue($config->isSessionResumable());
    }

    public function testSetValidateSsl(): void
    {
        $config = new HttpConfig('https://example.com');

        $config->setValidateSsl(false);
        $this->assertFalse($config->shouldValidateSsl());

        $config->setValidateSsl(true);
        $this->assertTrue($config->shouldValidateSsl());
    }

    public function testSetUserAgentEmpty(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Field \'user_agent\' cannot be empty');

        $config = new HttpConfig('https://example.com');
        $config->setUserAgent('');
    }

    public function testSetHeaders(): void
    {
        $config = new HttpConfig('https://example.com');
        $headers = ['X-Custom' => 'value', 'Authorization' => 'Bearer token'];

        $config->setHeaders($headers);
        $this->assertEquals($headers, $config->getHeaders());
    }

    public function testSetHeadersInvalidType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('must be an array of string key-value pairs');

        $config = new HttpConfig('https://example.com');
        $config->setHeaders([123 => 'value']);
    }

    public function testSetHeadersInvalidValue(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('must be an array of string key-value pairs');

        $config = new HttpConfig('https://example.com');
        $config->setHeaders(['header' => 123]);
    }

    public function testSetAuth(): void
    {
        $config = new HttpConfig('https://example.com');
        $auth = ['type' => 'bearer', 'token' => 'test-token'];

        $config->setAuth($auth);
        $this->assertEquals($auth, $config->getAuth());

        $config->setAuth(null);
        $this->assertNull($config->getAuth());
    }

    public function testValidateHttpsUrl(): void
    {
        $config = new HttpConfig('https://example.com');
        $config->validate(); // Should not throw
        $this->assertTrue(true); // Assert that no exception was thrown
    }

    public function testValidateHttpUrl(): void
    {
        $config = new HttpConfig('http://localhost:8080');
        $config->validate(); // Should not throw
        $this->assertTrue(true); // Assert that no exception was thrown
    }

    public function testValidateInvalidScheme(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('must use http or https scheme');

        $config = new HttpConfig('ftp://example.com');
        $config->validate();
    }

    public function testValidateSseTimeoutLessThanTimeout(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('should not be less than timeout');

        $config = new HttpConfig('https://example.com');
        $config->setTimeout(60.0);
        $config->setSseTimeout(30.0);
        $config->validate();
    }

    public function testWithChanges(): void
    {
        $original = new HttpConfig('https://example.com');
        $changes = [
            'timeout' => 60.0,
            'max_retries' => 5,
            'validate_ssl' => false,
        ];

        $modified = $original->withChanges($changes);

        // Original should be unchanged
        $this->assertEquals(30.0, $original->getTimeout());
        $this->assertEquals(3, $original->getMaxRetries());
        $this->assertTrue($original->shouldValidateSsl());

        // Modified should have new values
        $this->assertEquals(60.0, $modified->getTimeout());
        $this->assertEquals(5, $modified->getMaxRetries());
        $this->assertFalse($modified->shouldValidateSsl());

        // Other values should be preserved
        $this->assertEquals('https://example.com', $modified->getBaseUrl());
    }

    public function testWithChangesInvalidValue(): void
    {
        $this->expectException(ValidationError::class);

        $config = new HttpConfig('https://example.com');
        $config->withChanges(['timeout' => -1.0]);
    }

    public function testGetEndpointUrl(): void
    {
        $config = new HttpConfig('https://api.example.com');

        $this->assertEquals('https://api.example.com', $config->getEndpointUrl());
        $this->assertEquals('https://api.example.com/path', $config->getEndpointUrl('path'));
        $this->assertEquals('https://api.example.com/path', $config->getEndpointUrl('/path'));
        $this->assertEquals('https://api.example.com/api/v1', $config->getEndpointUrl('api/v1'));
    }

    public function testHasAuth(): void
    {
        $config = new HttpConfig('https://example.com');
        $this->assertFalse($config->hasAuth());

        $config->setAuth(['type' => 'bearer', 'token' => 'test']);
        $this->assertTrue($config->hasAuth());

        $config->setAuth([]);
        $this->assertFalse($config->hasAuth());

        $config->setAuth(null);
        $this->assertFalse($config->hasAuth());
    }

    public function testGetAuthType(): void
    {
        $config = new HttpConfig('https://example.com');
        $this->assertNull($config->getAuthType());

        $config->setAuth(['type' => 'bearer', 'token' => 'test']);
        $this->assertEquals('bearer', $config->getAuthType());

        $config->setAuth(['token' => 'test']); // No type
        $this->assertNull($config->getAuthType());
    }

    public function testJsonSerialization(): void
    {
        $config = new HttpConfig(
            'https://api.example.com',
            45.0,
            600.0,
            2,
            1.5,
            false,
            true,
            'test-agent/1.0'
        );

        $json = json_encode($config);
        $decoded = json_decode($json, true);

        $this->assertEquals($config->toArray(), $decoded);
    }
}
