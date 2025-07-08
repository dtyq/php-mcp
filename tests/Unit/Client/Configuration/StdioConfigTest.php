<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Client\Configuration;

use Dtyq\PhpMcp\Client\Configuration\StdioConfig;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use PHPUnit\Framework\TestCase;

/**
 * Test case for StdioConfig.
 * @internal
 */
class StdioConfigTest extends TestCase
{
    public function testConstructorWithDefaults(): void
    {
        $config = new StdioConfig();

        $this->assertEquals(30.0, $config->getReadTimeout());
        $this->assertEquals(10.0, $config->getWriteTimeout());
        $this->assertEquals(5.0, $config->getShutdownTimeout());
        $this->assertEquals(8192, $config->getBufferSize());
        $this->assertTrue($config->shouldInheritEnvironment());
        $this->assertTrue($config->shouldValidateMessages());
        $this->assertTrue($config->shouldCaptureStderr());
        $this->assertEquals([], $config->getEnv());
    }

    public function testConstructorWithCustomValues(): void
    {
        $env = ['CUSTOM_VAR' => 'custom_value'];

        $config = new StdioConfig(
            60.0,    // readTimeout
            45.0,    // writeTimeout
            10.0,    // shutdownTimeout
            4096,    // bufferSize
            false,   // inheritEnvironment
            false,   // validateMessages
            false,   // captureStderr
            $env     // env
        );

        $this->assertEquals(60.0, $config->getReadTimeout());
        $this->assertEquals(45.0, $config->getWriteTimeout());
        $this->assertEquals(10.0, $config->getShutdownTimeout());
        $this->assertEquals(4096, $config->getBufferSize());
        $this->assertFalse($config->shouldInheritEnvironment());
        $this->assertFalse($config->shouldValidateMessages());
        $this->assertFalse($config->shouldCaptureStderr());
        $this->assertEquals($env, $config->getEnv());
    }

    public function testFromArray(): void
    {
        $env = ['FROM_ARRAY_VAR' => 'from_array_value'];

        $data = [
            'read_timeout' => 90.0,
            'write_timeout' => 75.0,
            'shutdown_timeout' => 15.0,
            'buffer_size' => 16384,
            'inherit_environment' => false,
            'validate_messages' => false,
            'capture_stderr' => false,
            'env' => $env,
        ];

        $config = StdioConfig::fromArray($data);

        $this->assertEquals(90.0, $config->getReadTimeout());
        $this->assertEquals(75.0, $config->getWriteTimeout());
        $this->assertEquals(15.0, $config->getShutdownTimeout());
        $this->assertEquals(16384, $config->getBufferSize());
        $this->assertFalse($config->shouldInheritEnvironment());
        $this->assertFalse($config->shouldValidateMessages());
        $this->assertFalse($config->shouldCaptureStderr());
        $this->assertEquals($env, $config->getEnv());
    }

    public function testFromArrayWithPartialData(): void
    {
        $data = [
            'read_timeout' => 120.0,
            'validate_messages' => false,
        ];

        $config = StdioConfig::fromArray($data);

        // Should merge with defaults
        $this->assertEquals(120.0, $config->getReadTimeout());
        $this->assertEquals(10.0, $config->getWriteTimeout()); // Default
        $this->assertFalse($config->shouldValidateMessages());
        $this->assertEquals(5.0, $config->getShutdownTimeout()); // Default
        $this->assertEquals([], $config->getEnv()); // Default
    }

    public function testConstructorWithEnvVariables(): void
    {
        $env = [
            'API_KEY' => 'test-key',
            'DEBUG' => 'true',
        ];

        $config = new StdioConfig(
            30.0,    // readTimeout (default)
            10.0,    // writeTimeout (default)
            5.0,     // shutdownTimeout (default)
            8192,    // bufferSize (default)
            true,    // inheritEnvironment (default)
            true,    // validateMessages (default)
            false,   // captureStderr (default)
            $env     // env
        );

        $this->assertEquals($env, $config->getEnv());
    }

    public function testFromArrayWithEnvVariables(): void
    {
        $env = [
            'OPENAPI_MCP_HEADERS' => '{"Authorization": "Bearer token"}',
            'NODE_ENV' => 'production',
        ];

        $data = [
            'read_timeout' => 45.0,
            'env' => $env,
        ];

        $config = StdioConfig::fromArray($data);

        $this->assertEquals($env, $config->getEnv());
        $this->assertEquals(45.0, $config->getReadTimeout());
    }

    public function testGetEnvDefault(): void
    {
        $config = new StdioConfig();
        $this->assertEquals([], $config->getEnv());
    }

    public function testSetEnv(): void
    {
        $config = new StdioConfig();
        $env = [
            'MY_VAR' => 'my_value',
            'ANOTHER_VAR' => 'another_value',
        ];

        $config->setEnv($env);
        $this->assertEquals($env, $config->getEnv());
    }

    public function testSetEnvEmpty(): void
    {
        $config = new StdioConfig();
        $config->setEnv([]);
        $this->assertEquals([], $config->getEnv());
    }

    public function testWithChangesIncludesEnv(): void
    {
        $original = new StdioConfig();
        $newEnv = [
            'NEW_VAR' => 'new_value',
            'ANOTHER_VAR' => 'another_value',
        ];

        $modified = $original->withChanges([
            'env' => $newEnv,
            'read_timeout' => 45.0,
        ]);

        // Original should be unchanged
        $this->assertEquals([], $original->getEnv());
        $this->assertEquals(30.0, $original->getReadTimeout());

        // Modified should have changes
        $this->assertEquals($newEnv, $modified->getEnv());
        $this->assertEquals(45.0, $modified->getReadTimeout());
    }

    public function testJsonSerializationWithEnv(): void
    {
        $env = [
            'API_KEY' => 'secret-key',
            'DEBUG' => 'true',
        ];

        $config = new StdioConfig(
            30.0,    // readTimeout (default)
            10.0,    // writeTimeout (default)
            5.0,     // shutdownTimeout (default)
            8192,    // bufferSize (default)
            true,    // inheritEnvironment (default)
            true,    // validateMessages (default)
            false,   // captureStderr (default)
            $env     // env
        );

        $json = json_encode($config);
        $this->assertIsString($json);

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('env', $decoded);
        $this->assertEquals($env, $decoded['env']);
    }

    public function testToArray(): void
    {
        $env = ['TEST_VAR' => 'test_value'];

        $config = new StdioConfig(
            45.0,    // readTimeout
            35.0,    // writeTimeout
            8.0,     // shutdownTimeout
            4096,    // bufferSize
            false,   // inheritEnvironment
            false,   // validateMessages
            true,    // captureStderr
            $env     // env
        );

        $expected = [
            'read_timeout' => 45.0,
            'write_timeout' => 35.0,
            'shutdown_timeout' => 8.0,
            'buffer_size' => 4096,
            'inherit_environment' => false,
            'validate_messages' => false,
            'capture_stderr' => true,
            'env' => $env,
        ];

        $this->assertEquals($expected, $config->toArray());
    }

    public function testGetDefaults(): void
    {
        $defaults = StdioConfig::getDefaults();

        $this->assertIsArray($defaults);
        $this->assertArrayHasKey('read_timeout', $defaults);
        $this->assertArrayHasKey('write_timeout', $defaults);
        $this->assertArrayHasKey('shutdown_timeout', $defaults);
        $this->assertArrayHasKey('buffer_size', $defaults);
        $this->assertArrayHasKey('inherit_environment', $defaults);
        $this->assertArrayHasKey('validate_messages', $defaults);
        $this->assertArrayHasKey('capture_stderr', $defaults);
        $this->assertArrayHasKey('env', $defaults);
        $this->assertEquals([], $defaults['env']);
    }

    public function testSetReadTimeoutInvalid(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('must be greater than 0');

        $config = new StdioConfig();
        $config->setReadTimeout(0.0);
    }

    public function testSetReadTimeoutNegative(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('must be greater than 0');

        $config = new StdioConfig();
        $config->setReadTimeout(-5.0);
    }

    public function testSetWriteTimeoutInvalid(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('must be greater than 0');

        $config = new StdioConfig();
        $config->setWriteTimeout(0.0);
    }

    public function testSetShutdownTimeoutInvalid(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('must be greater than 0');

        $config = new StdioConfig();
        $config->setShutdownTimeout(-1.0);
    }

    public function testSetBufferSize(): void
    {
        $config = new StdioConfig();

        $config->setBufferSize(16384);
        $this->assertEquals(16384, $config->getBufferSize());
    }

    public function testSetBufferSizeInvalid(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('must be greater than 0');

        $config = new StdioConfig();
        $config->setBufferSize(0);
    }

    public function testSetInheritEnvironment(): void
    {
        $config = new StdioConfig();

        $config->setInheritEnvironment(false);
        $this->assertFalse($config->shouldInheritEnvironment());

        $config->setInheritEnvironment(true);
        $this->assertTrue($config->shouldInheritEnvironment());
    }

    public function testSetValidateMessages(): void
    {
        $config = new StdioConfig();

        $config->setValidateMessages(false);
        $this->assertFalse($config->shouldValidateMessages());

        $config->setValidateMessages(true);
        $this->assertTrue($config->shouldValidateMessages());
    }

    public function testSetCaptureStderr(): void
    {
        $config = new StdioConfig();

        $config->setCaptureStderr(false);
        $this->assertFalse($config->shouldCaptureStderr());

        $config->setCaptureStderr(true);
        $this->assertTrue($config->shouldCaptureStderr());
    }

    public function testValidate(): void
    {
        $config = new StdioConfig();

        // Should not throw for valid configuration
        $config->validate();

        $this->addToAssertionCount(1);
    }

    public function testWithChanges(): void
    {
        $originalEnv = ['ORIGINAL_VAR' => 'original_value'];

        $original = new StdioConfig(
            30.0,    // readTimeout
            10.0,    // writeTimeout
            5.0,     // shutdownTimeout
            8192,    // bufferSize
            true,    // inheritEnvironment
            true,    // validateMessages
            true,    // captureStderr
            $originalEnv // env
        );

        $changes = [
            'read_timeout' => 60.0,
            'validate_messages' => false,
            'buffer_size' => 4096,
        ];

        $modified = $original->withChanges($changes);

        // Original should be unchanged
        $this->assertEquals(30.0, $original->getReadTimeout());
        $this->assertTrue($original->shouldValidateMessages());
        $this->assertEquals(8192, $original->getBufferSize());
        $this->assertEquals($originalEnv, $original->getEnv());

        // Modified should have changes
        $this->assertEquals(60.0, $modified->getReadTimeout());
        $this->assertFalse($modified->shouldValidateMessages());
        $this->assertEquals(4096, $modified->getBufferSize());

        // Unchanged values should be preserved
        $this->assertEquals(10.0, $modified->getWriteTimeout());
        $this->assertEquals(5.0, $modified->getShutdownTimeout());
        $this->assertTrue($modified->shouldInheritEnvironment());
        $this->assertEquals($originalEnv, $modified->getEnv());
    }

    public function testWithChangesInvalidValue(): void
    {
        $config = new StdioConfig();

        $this->expectException(ValidationError::class);

        $config->withChanges([
            'read_timeout' => -1.0,
        ]);
    }

    public function testJsonSerialization(): void
    {
        $env = ['JSON_VAR' => 'json_value'];

        $config = new StdioConfig(
            45.0,    // readTimeout
            35.0,    // writeTimeout
            8.0,     // shutdownTimeout
            4096,    // bufferSize
            false,   // inheritEnvironment
            false,   // validateMessages
            true,    // captureStderr
            $env     // env
        );

        $json = json_encode($config);
        $this->assertIsString($json);

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertEquals(45.0, $decoded['read_timeout']);
        $this->assertEquals(35.0, $decoded['write_timeout']);
        $this->assertFalse($decoded['validate_messages']);
        $this->assertTrue($decoded['capture_stderr']);
        $this->assertEquals($env, $decoded['env']);
    }
}
