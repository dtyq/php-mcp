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
 * @internal
 */
class StdioConfigTest extends TestCase
{
    public function testConstructorWithDefaults(): void
    {
        $config = new StdioConfig('php');

        $this->assertSame(['php'], $config->getCommand());
        $this->assertSame(30.0, $config->getReadTimeout());
        $this->assertSame(10.0, $config->getWriteTimeout());
        $this->assertSame(5.0, $config->getShutdownTimeout());
        $this->assertSame(8192, $config->getBufferSize());
        $this->assertTrue($config->shouldInheritEnvironment());
        $this->assertTrue($config->shouldValidateMessages());
        $this->assertTrue($config->shouldCaptureStderr());
        $this->assertSame([], $config->getEnv());
    }

    public function testConstructorWithStringCommand(): void
    {
        $config = new StdioConfig('php');
        $this->assertSame(['php'], $config->getCommand());
    }

    public function testConstructorWithArrayCommand(): void
    {
        $config = new StdioConfig(['php', '-v']);
        $this->assertSame(['php', '-v'], $config->getCommand());
    }

    public function testConstructorWithCommandAndArgs(): void
    {
        $config = new StdioConfig('php', ['server.php', '-v']);
        $this->assertSame(['php', 'server.php', '-v'], $config->getCommand());
    }

    public function testCreateMethodWithDefaults(): void
    {
        $config = StdioConfig::create('php');

        $this->assertSame(['php'], $config->getCommand());
        $this->assertSame(30.0, $config->getReadTimeout());
        $this->assertSame(10.0, $config->getWriteTimeout());
        $this->assertSame(5.0, $config->getShutdownTimeout());
        $this->assertSame(8192, $config->getBufferSize());
        $this->assertTrue($config->shouldInheritEnvironment());
        $this->assertTrue($config->shouldValidateMessages());
        $this->assertTrue($config->shouldCaptureStderr());
        $this->assertSame([], $config->getEnv());
    }

    public function testCreateMethodWithCustomParameters(): void
    {
        $config = StdioConfig::create(
            'python',
            ['server.py'],
            60.0,  // readTimeout
            20.0,  // writeTimeout
            10.0,  // shutdownTimeout
            16384, // bufferSize
            false, // inheritEnvironment
            false, // validateMessages
            false, // captureStderr
            ['DEBUG' => '1'] // env
        );

        $this->assertSame(['python', 'server.py'], $config->getCommand());
        $this->assertSame(60.0, $config->getReadTimeout());
        $this->assertSame(20.0, $config->getWriteTimeout());
        $this->assertSame(10.0, $config->getShutdownTimeout());
        $this->assertSame(16384, $config->getBufferSize());
        $this->assertFalse($config->shouldInheritEnvironment());
        $this->assertFalse($config->shouldValidateMessages());
        $this->assertFalse($config->shouldCaptureStderr());
        $this->assertSame(['DEBUG' => '1'], $config->getEnv());
    }

    public function testCreateMethodWithStringCommand(): void
    {
        $config = StdioConfig::create('node');
        $this->assertSame(['node'], $config->getCommand());
    }

    public function testCreateMethodWithArrayCommand(): void
    {
        $config = StdioConfig::create(['node', '--version']);
        $this->assertSame(['node', '--version'], $config->getCommand());
    }

    public function testConstructorWithAllParameters(): void
    {
        $config = new StdioConfig(
            'python',
            ['script.py'],
            25.0,
            15.0,
            8.0,
            4096,
            false,
            false,
            false,
            ['VAR' => 'value']
        );

        $this->assertSame(['python', 'script.py'], $config->getCommand());
        $this->assertSame(25.0, $config->getReadTimeout());
        $this->assertSame(15.0, $config->getWriteTimeout());
        $this->assertSame(8.0, $config->getShutdownTimeout());
        $this->assertSame(4096, $config->getBufferSize());
        $this->assertFalse($config->shouldInheritEnvironment());
        $this->assertFalse($config->shouldValidateMessages());
        $this->assertFalse($config->shouldCaptureStderr());
        $this->assertSame(['VAR' => 'value'], $config->getEnv());
    }

    public function testFromArray(): void
    {
        $config = StdioConfig::fromArray([
            'command' => 'node',
            'args' => ['server.js'],
            'read_timeout' => 20.0,
            'write_timeout' => 12.0,
            'shutdown_timeout' => 6.0,
            'buffer_size' => 4096,
            'inherit_environment' => false,
            'validate_messages' => false,
            'capture_stderr' => false,
            'env' => ['NODE_ENV' => 'production'],
        ]);

        $this->assertSame(['node', 'server.js'], $config->getCommand());
        $this->assertSame(20.0, $config->getReadTimeout());
        $this->assertSame(12.0, $config->getWriteTimeout());
        $this->assertSame(6.0, $config->getShutdownTimeout());
        $this->assertSame(4096, $config->getBufferSize());
        $this->assertFalse($config->shouldInheritEnvironment());
        $this->assertFalse($config->shouldValidateMessages());
        $this->assertFalse($config->shouldCaptureStderr());
        $this->assertSame(['NODE_ENV' => 'production'], $config->getEnv());
    }

    public function testFromArrayWithMissingCommand(): void
    {
        $this->expectException(ValidationError::class);
        StdioConfig::fromArray([
            'read_timeout' => 30.0,
        ]);
    }

    public function testFromArrayWithPartialConfig(): void
    {
        $config = StdioConfig::fromArray([
            'command' => 'php',
            'read_timeout' => 25.0,
        ]);

        $this->assertSame(['php'], $config->getCommand());
        $this->assertSame(25.0, $config->getReadTimeout());
        $this->assertSame(10.0, $config->getWriteTimeout()); // default
    }

    public function testToArray(): void
    {
        $config = new StdioConfig(
            'python',
            ['script.py'],
            25.0,
            15.0,
            8.0,
            4096,
            false,
            false,
            false,
            ['VAR' => 'value']
        );

        $array = $config->toArray();

        $this->assertSame(['python', 'script.py'], $array['command']);
        $this->assertSame(25.0, $array['read_timeout']);
        $this->assertSame(15.0, $array['write_timeout']);
        $this->assertSame(8.0, $array['shutdown_timeout']);
        $this->assertSame(4096, $array['buffer_size']);
        $this->assertFalse($array['inherit_environment']);
        $this->assertFalse($array['validate_messages']);
        $this->assertFalse($array['capture_stderr']);
        $this->assertSame(['VAR' => 'value'], $array['env']);
    }

    public function testSettersWithValidValues(): void
    {
        $config = new StdioConfig('php');

        $config->setCommand('node', ['server.js']);
        $this->assertSame(['node', 'server.js'], $config->getCommand());

        $config->setReadTimeout(35.0);
        $this->assertSame(35.0, $config->getReadTimeout());

        $config->setWriteTimeout(15.0);
        $this->assertSame(15.0, $config->getWriteTimeout());

        $config->setShutdownTimeout(8.0);
        $this->assertSame(8.0, $config->getShutdownTimeout());

        $config->setBufferSize(4096);
        $this->assertSame(4096, $config->getBufferSize());

        $config->setInheritEnvironment(false);
        $this->assertFalse($config->shouldInheritEnvironment());

        $config->setValidateMessages(false);
        $this->assertFalse($config->shouldValidateMessages());

        $config->setCaptureStderr(false);
        $this->assertFalse($config->shouldCaptureStderr());

        $config->setEnv(['VAR' => 'value']);
        $this->assertSame(['VAR' => 'value'], $config->getEnv());
    }

    public function testSetCommandWithInvalidValues(): void
    {
        $config = new StdioConfig('php');

        $config->setCommand('');
        $this->expectException(ValidationError::class);
        $config->validate();
    }

    public function testSetCommandWithInvalidType(): void
    {
        $config = new StdioConfig('php');

        $config->setCommand(123);
        $this->expectException(ValidationError::class);
        $config->validate();
    }

    public function testSetCommandWithEmptyParts(): void
    {
        $config = new StdioConfig('php');

        $config->setCommand(['php', '']);
        $this->expectException(ValidationError::class);
        $config->validate();
    }

    public function testSetReadTimeoutWithInvalidValue(): void
    {
        $config = new StdioConfig('php');

        $config->setReadTimeout(0.0);
        $this->expectException(ValidationError::class);
        $config->validate();
    }

    public function testSetWriteTimeoutWithInvalidValue(): void
    {
        $config = new StdioConfig('php');

        $config->setWriteTimeout(-1.0);
        $this->expectException(ValidationError::class);
        $config->validate();
    }

    public function testSetShutdownTimeoutWithInvalidValue(): void
    {
        $config = new StdioConfig('php');

        $config->setShutdownTimeout(0.0);
        $this->expectException(ValidationError::class);
        $config->validate();
    }

    public function testSetBufferSizeWithInvalidValue(): void
    {
        $config = new StdioConfig('php');
        $config->setBufferSize(-1);

        $this->expectException(ValidationError::class);
        $config->validate();
    }

    public function testWithChanges(): void
    {
        $config = new StdioConfig('php');
        $newConfig = $config->withChanges([
            'command' => 'python',
            'read_timeout' => 35.0,
        ]);

        $this->assertSame(['python'], $newConfig->getCommand());
        $this->assertSame(35.0, $newConfig->getReadTimeout());
        $this->assertSame(10.0, $newConfig->getWriteTimeout()); // unchanged

        // Original config should be unchanged
        $this->assertSame(['php'], $config->getCommand());
        $this->assertSame(30.0, $config->getReadTimeout());
    }

    public function testValidate(): void
    {
        $config = new StdioConfig('php');

        // Should not throw exception
        $config->validate();
        $this->assertTrue(true);
    }

    public function testJsonSerialize(): void
    {
        $config = new StdioConfig('php');
        $json = $config->jsonSerialize();

        $this->assertIsArray($json);
        $this->assertSame(['php'], $json['command']);
        $this->assertSame(30.0, $json['read_timeout']);
    }
}
