<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client\Configuration;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use JsonSerializable;

/**
 * Configuration for stdio transport.
 *
 * This class holds all stdio-specific configuration options including
 * timeouts, buffer sizes, environment handling, and validation settings.
 */
class StdioConfig implements JsonSerializable
{
    /**
     * Default configuration values.
     */
    public const DEFAULTS = [
        'read_timeout' => 30.0,
        'write_timeout' => 10.0,
        'shutdown_timeout' => 5.0,
        'buffer_size' => 8192,
        'inherit_environment' => true,
        'validate_messages' => true,
        'capture_stderr' => true,
        'env' => [],
    ];

    /** @var float Timeout for read operations in seconds */
    private float $readTimeout;

    /** @var float Timeout for write operations in seconds */
    private float $writeTimeout;

    /** @var float Timeout for graceful shutdown in seconds */
    private float $shutdownTimeout;

    /** @var int Buffer size for stream operations */
    private int $bufferSize;

    /** @var bool Whether to inherit environment variables from parent process */
    private bool $inheritEnvironment;

    /** @var bool Whether to validate messages according to MCP specification */
    private bool $validateMessages;

    /** @var bool Whether to capture stderr output from child process */
    private bool $captureStderr;

    /** @var array<string, string> Custom environment variables */
    private array $env;

    /**
     * @param float $readTimeout Timeout for read operations in seconds
     * @param float $writeTimeout Timeout for write operations in seconds
     * @param float $shutdownTimeout Timeout for graceful shutdown in seconds
     * @param int $bufferSize Buffer size for stream operations
     * @param bool $inheritEnvironment Whether to inherit environment variables
     * @param bool $validateMessages Whether to validate messages
     * @param bool $captureStderr Whether to capture stderr output
     * @param array<string, string> $env Custom environment variables
     */
    public function __construct(
        float $readTimeout = self::DEFAULTS['read_timeout'],
        float $writeTimeout = self::DEFAULTS['write_timeout'],
        float $shutdownTimeout = self::DEFAULTS['shutdown_timeout'],
        int $bufferSize = self::DEFAULTS['buffer_size'],
        bool $inheritEnvironment = self::DEFAULTS['inherit_environment'],
        bool $validateMessages = self::DEFAULTS['validate_messages'],
        bool $captureStderr = self::DEFAULTS['capture_stderr'],
        array $env = self::DEFAULTS['env']
    ) {
        $this->setReadTimeout($readTimeout);
        $this->setWriteTimeout($writeTimeout);
        $this->setShutdownTimeout($shutdownTimeout);
        $this->setBufferSize($bufferSize);
        $this->setInheritEnvironment($inheritEnvironment);
        $this->setValidateMessages($validateMessages);
        $this->setCaptureStderr($captureStderr);
        $this->setEnv($env);
    }

    /**
     * Create configuration from array.
     *
     * @param array<string, mixed> $config Configuration array
     * @throws ValidationError If configuration is invalid
     */
    public static function fromArray(array $config): self
    {
        // Merge with defaults to ensure all required keys are present
        $config = array_merge(self::DEFAULTS, $config);

        return new self(
            $config['read_timeout'],
            $config['write_timeout'],
            $config['shutdown_timeout'],
            $config['buffer_size'],
            $config['inherit_environment'],
            $config['validate_messages'],
            $config['capture_stderr'],
            $config['env']
        );
    }

    /**
     * Get default configuration values.
     *
     * @return array<string, mixed>
     */
    public static function getDefaults(): array
    {
        return self::DEFAULTS;
    }

    /**
     * Convert configuration to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'read_timeout' => $this->readTimeout,
            'write_timeout' => $this->writeTimeout,
            'shutdown_timeout' => $this->shutdownTimeout,
            'buffer_size' => $this->bufferSize,
            'inherit_environment' => $this->inheritEnvironment,
            'validate_messages' => $this->validateMessages,
            'capture_stderr' => $this->captureStderr,
            'env' => $this->env,
        ];
    }

    // Getters
    public function getReadTimeout(): float
    {
        return $this->readTimeout;
    }

    public function getWriteTimeout(): float
    {
        return $this->writeTimeout;
    }

    public function getShutdownTimeout(): float
    {
        return $this->shutdownTimeout;
    }

    public function getBufferSize(): int
    {
        return $this->bufferSize;
    }

    public function shouldInheritEnvironment(): bool
    {
        return $this->inheritEnvironment;
    }

    public function shouldValidateMessages(): bool
    {
        return $this->validateMessages;
    }

    public function shouldCaptureStderr(): bool
    {
        return $this->captureStderr;
    }

    /**
     * @return array<string, string>
     */
    public function getEnv(): array
    {
        return $this->env;
    }

    // Setters with validation
    public function setReadTimeout(float $readTimeout): void
    {
        if ($readTimeout <= 0) {
            throw ValidationError::invalidFieldValue(
                'read_timeout',
                'must be greater than 0',
                ['value' => $readTimeout]
            );
        }
        $this->readTimeout = $readTimeout;
    }

    public function setWriteTimeout(float $writeTimeout): void
    {
        if ($writeTimeout <= 0) {
            throw ValidationError::invalidFieldValue(
                'write_timeout',
                'must be greater than 0',
                ['value' => $writeTimeout]
            );
        }
        $this->writeTimeout = $writeTimeout;
    }

    public function setShutdownTimeout(float $shutdownTimeout): void
    {
        if ($shutdownTimeout <= 0) {
            throw ValidationError::invalidFieldValue(
                'shutdown_timeout',
                'must be greater than 0',
                ['value' => $shutdownTimeout]
            );
        }
        $this->shutdownTimeout = $shutdownTimeout;
    }

    public function setBufferSize(int $bufferSize): void
    {
        if ($bufferSize <= 0) {
            throw ValidationError::invalidFieldValue(
                'buffer_size',
                'must be greater than 0',
                ['value' => $bufferSize]
            );
        }
        $this->bufferSize = $bufferSize;
    }

    public function setInheritEnvironment(bool $inheritEnvironment): void
    {
        $this->inheritEnvironment = $inheritEnvironment;
    }

    public function setValidateMessages(bool $validateMessages): void
    {
        $this->validateMessages = $validateMessages;
    }

    public function setCaptureStderr(bool $captureStderr): void
    {
        $this->captureStderr = $captureStderr;
    }

    /**
     * @param array<string, string> $env
     */
    public function setEnv(array $env): void
    {
        $this->env = $env;
    }

    /**
     * Validate the complete configuration.
     *
     * @throws ValidationError If configuration is invalid
     */
    public function validate(): void
    {
        // Re-validate all fields to ensure consistency
        $this->setReadTimeout($this->readTimeout);
        $this->setWriteTimeout($this->writeTimeout);
        $this->setShutdownTimeout($this->shutdownTimeout);
        $this->setBufferSize($this->bufferSize);
    }

    /**
     * Create a copy of this configuration with modified values.
     *
     * @param array<string, mixed> $changes Values to change
     * @return self New configuration instance
     */
    public function withChanges(array $changes): self
    {
        $config = $this->toArray();
        $config = array_merge($config, $changes);
        return self::fromArray($config);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
