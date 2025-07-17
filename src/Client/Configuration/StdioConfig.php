<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client\Configuration;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Constants\TransportTypes;

/**
 * Configuration for stdio transport.
 * stdio 传输的配置。
 *
 * This class holds all stdio-specific configuration options including
 * command execution, timeouts, buffer sizes, environment handling, and validation settings.
 * 此类包含所有 stdio 特定的配置选项，包括命令执行、超时、缓冲区大小、环境处理和验证设置。
 */
class StdioConfig implements TransportConfigInterface
{
    /**
     * Command to execute.
     * 要执行的命令。
     *
     * This is the command that will be executed to start the MCP server process.
     * Can be a single command string or an array of command parts.
     * 这是将要执行以启动 MCP 服务器进程的命令。可以是单个命令字符串或命令部分数组。
     *
     * @var array<string>
     */
    private array $command;

    /**
     * Timeout for read operations in seconds.
     * 读操作超时时间（秒）。
     *
     * Maximum time to wait for reading data from the child process.
     * If exceeded, the operation will timeout and throw an exception.
     * 从子进程读取数据的最大等待时间。如果超过此时间，操作将超时并抛出异常。
     */
    private float $readTimeout;

    /**
     * Timeout for write operations in seconds.
     * 写操作超时时间（秒）。
     *
     * Maximum time to wait for writing data to the child process.
     * If exceeded, the operation will timeout and throw an exception.
     * 向子进程写入数据的最大等待时间。如果超过此时间，操作将超时并抛出异常。
     */
    private float $writeTimeout;

    /**
     * Timeout for graceful shutdown in seconds.
     * 优雅关闭超时时间（秒）。
     *
     * Maximum time to wait for the child process to terminate gracefully
     * before forcefully killing it.
     * 在强制终止子进程之前等待其优雅终止的最大时间。
     */
    private float $shutdownTimeout;

    /**
     * Buffer size for stream operations.
     * 流操作的缓冲区大小。
     *
     * Size of the buffer used for reading from and writing to the child process streams.
     * Larger buffers can improve performance but use more memory.
     * 用于从子进程流读取和写入的缓冲区大小。较大的缓冲区可以提高性能但使用更多内存。
     */
    private int $bufferSize;

    /**
     * Whether to inherit environment variables from parent process.
     * 是否从父进程继承环境变量。
     *
     * If true, the child process will inherit all environment variables from the parent.
     * If false, only the variables specified in the 'env' array will be available.
     * 如果为 true，子进程将从父进程继承所有环境变量。如果为 false，只有 'env' 数组中指定的变量可用。
     */
    private bool $inheritEnvironment;

    /**
     * Whether to validate messages according to MCP specification.
     * 是否根据 MCP 规范验证消息。
     *
     * If true, all incoming and outgoing messages will be validated against the MCP protocol.
     * This helps catch protocol violations but may impact performance.
     * 如果为 true，所有传入和传出的消息都将根据 MCP 协议进行验证。这有助于捕获协议违规，但可能影响性能。
     */
    private bool $validateMessages;

    /**
     * Whether to capture stderr output from child process.
     * 是否捕获子进程的标准错误输出。
     *
     * If true, stderr output from the child process will be captured and can be accessed
     * for debugging purposes. If false, stderr will be ignored.
     * 如果为 true，将捕获子进程的标准错误输出，可用于调试目的。如果为 false，将忽略标准错误。
     */
    private bool $captureStderr;

    /**
     * Custom environment variables.
     * 自定义环境变量。
     *
     * Additional environment variables to set for the child process.
     * These are added to (or override) the inherited environment variables.
     * 为子进程设置的额外环境变量。这些变量会添加到（或覆盖）继承的环境变量中。
     *
     * @var array<string, string>
     */
    private array $env;

    /**
     * Constructor for stdio configuration.
     * stdio 配置构造函数。
     *
     * @param array<string>|string $command Command to execute / 要执行的命令
     * @param null|array<string> $args Command arguments (optional if command is array) / 命令参数（如果命令是数组则可选）
     * @param float $readTimeout Timeout for read operations in seconds / 读操作超时时间（秒）
     * @param float $writeTimeout Timeout for write operations in seconds / 写操作超时时间（秒）
     * @param float $shutdownTimeout Timeout for graceful shutdown in seconds / 优雅关闭超时时间（秒）
     * @param int $bufferSize Buffer size for stream operations / 流操作缓冲区大小
     * @param bool $inheritEnvironment Whether to inherit environment variables / 是否继承环境变量
     * @param bool $validateMessages Whether to validate messages / 是否验证消息
     * @param bool $captureStderr Whether to capture stderr output / 是否捕获标准错误输出
     * @param array<string, string> $env Custom environment variables / 自定义环境变量
     */
    public function __construct(
        $command,
        ?array $args = null,
        float $readTimeout = 30.0,
        float $writeTimeout = 10.0,
        float $shutdownTimeout = 5.0,
        int $bufferSize = 8192,
        bool $inheritEnvironment = true,
        bool $validateMessages = true,
        bool $captureStderr = true,
        array $env = []
    ) {
        $this->setCommand($command, $args);
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
     * Create stdio configuration instance with named parameters.
     * 使用命名参数创建 stdio 配置实例。
     *
     * This static factory method provides an alternative way to create configuration
     * instances with the same parameters as the constructor. Useful for fluent APIs
     * or when you prefer static factory methods over constructors.
     * 此静态工厂方法提供了使用与构造函数相同参数创建配置实例的替代方法。
     * 适用于流畅的 API 或当您更喜欢静态工厂方法而不是构造函数时。
     *
     * @param array<string>|string $command Command to execute / 要执行的命令
     * @param null|array<string> $args Command arguments (optional if command is array) / 命令参数（如果命令是数组则可选）
     * @param float $readTimeout Timeout for read operations in seconds / 读操作超时时间（秒）
     * @param float $writeTimeout Timeout for write operations in seconds / 写操作超时时间（秒）
     * @param float $shutdownTimeout Timeout for graceful shutdown in seconds / 优雅关闭超时时间（秒）
     * @param int $bufferSize Buffer size for stream operations / 流操作缓冲区大小
     * @param bool $inheritEnvironment Whether to inherit environment variables / 是否继承环境变量
     * @param bool $validateMessages Whether to validate messages / 是否验证消息
     * @param bool $captureStderr Whether to capture stderr output / 是否捕获标准错误输出
     * @param array<string, string> $env Custom environment variables / 自定义环境变量
     * @return self New configuration instance / 新的配置实例
     */
    public static function create(
        $command,
        ?array $args = null,
        float $readTimeout = 30.0,
        float $writeTimeout = 10.0,
        float $shutdownTimeout = 5.0,
        int $bufferSize = 8192,
        bool $inheritEnvironment = true,
        bool $validateMessages = true,
        bool $captureStderr = true,
        array $env = []
    ): self {
        return new self(
            $command,
            $args,
            $readTimeout,
            $writeTimeout,
            $shutdownTimeout,
            $bufferSize,
            $inheritEnvironment,
            $validateMessages,
            $captureStderr,
            $env
        );
    }

    /**
     * Create configuration from array data.
     * 从数组数据创建配置。
     *
     * This method allows creating configuration instances from associative arrays,
     * commonly used for loading configuration from files, environment variables,
     * or other external sources. Missing keys will use default values.
     * 此方法允许从关联数组创建配置实例，通常用于从文件、环境变量或其他外部源加载配置。
     * 缺少的键将使用默认值。
     *
     * @param array<string, mixed> $config Configuration array / 配置数组
     * @return self New configuration instance / 新的配置实例
     * @throws ValidationError If configuration is invalid / 如果配置无效
     */
    public static function fromArray(array $config): self
    {
        // Extract command and args
        $command = $config['command'] ?? null;
        $args = $config['args'] ?? null;

        if ($command === null) {
            throw ValidationError::emptyField('command');
        }

        // Use default values directly from constructor
        return new self(
            $command,
            $args,
            $config['read_timeout'] ?? 30.0,
            $config['write_timeout'] ?? 10.0,
            $config['shutdown_timeout'] ?? 5.0,
            $config['buffer_size'] ?? 8192,
            $config['inherit_environment'] ?? true,
            $config['validate_messages'] ?? true,
            $config['capture_stderr'] ?? true,
            $config['env'] ?? []
        );
    }

    /**
     * Convert configuration to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'command' => $this->command,
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

    /**
     * Get command array for stdio transport.
     * 获取 stdio 传输的命令数组。
     *
     * @return array<string> Command array / 命令数组
     */
    public function getCommand(): array
    {
        return $this->command;
    }

    /**
     * Get the transport type identifier.
     * 获取传输类型标识符。
     *
     * @return string Transport type identifier / 传输类型标识符
     */
    public function getTransportType(): string
    {
        return TransportTypes::TRANSPORT_TYPE_STDIO;
    }

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

    /**
     * Set command with normalization.
     *
     * @param array<string>|string $command Command to execute
     * @param null|array<string> $args Command arguments
     */
    public function setCommand($command, ?array $args = null): void
    {
        // Normalize command to array
        if (is_string($command)) {
            $normalizedCommand = [$command];
        } elseif (is_array($command)) {
            $normalizedCommand = $command;
        } else {
            $normalizedCommand = [$command]; // Let validation handle the error
        }

        // Add args if provided
        if ($args !== null) {
            $normalizedCommand = array_merge($normalizedCommand, $args);
        }

        // Set the command without validation
        $this->command = $normalizedCommand;
    }

    public function setReadTimeout(float $readTimeout): void
    {
        $this->readTimeout = $readTimeout;
    }

    public function setWriteTimeout(float $writeTimeout): void
    {
        $this->writeTimeout = $writeTimeout;
    }

    public function setShutdownTimeout(float $shutdownTimeout): void
    {
        $this->shutdownTimeout = $shutdownTimeout;
    }

    public function setBufferSize(int $bufferSize): void
    {
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
        $this->validateCommand($this->command);
        $this->validateReadTimeout($this->readTimeout);
        $this->validateWriteTimeout($this->writeTimeout);
        $this->validateShutdownTimeout($this->shutdownTimeout);
        $this->validateBufferSize($this->bufferSize);
        $this->validateEnv($this->env);
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

    /**
     * Validate command without modifying state.
     *
     * @param array<string> $command Command to validate
     * @throws ValidationError If command is invalid
     */
    private function validateCommand(array $command): void
    {
        if (empty($command)) {
            throw ValidationError::emptyField('command');
        }

        foreach ($command as $part) {
            if (! is_string($part) || empty($part)) {
                throw ValidationError::invalidFieldValue(
                    'command',
                    'All command parts must be non-empty strings'
                );
            }
        }
    }

    /**
     * Validate read timeout without modifying state.
     *
     * @param float $readTimeout Read timeout to validate
     * @throws ValidationError If timeout is invalid
     */
    private function validateReadTimeout(float $readTimeout): void
    {
        if ($readTimeout <= 0) {
            throw ValidationError::invalidFieldValue(
                'read_timeout',
                'must be greater than 0',
                ['value' => $readTimeout]
            );
        }
    }

    /**
     * Validate write timeout without modifying state.
     *
     * @param float $writeTimeout Write timeout to validate
     * @throws ValidationError If timeout is invalid
     */
    private function validateWriteTimeout(float $writeTimeout): void
    {
        if ($writeTimeout <= 0) {
            throw ValidationError::invalidFieldValue(
                'write_timeout',
                'must be greater than 0',
                ['value' => $writeTimeout]
            );
        }
    }

    /**
     * Validate shutdown timeout without modifying state.
     *
     * @param float $shutdownTimeout Shutdown timeout to validate
     * @throws ValidationError If timeout is invalid
     */
    private function validateShutdownTimeout(float $shutdownTimeout): void
    {
        if ($shutdownTimeout <= 0) {
            throw ValidationError::invalidFieldValue(
                'shutdown_timeout',
                'must be greater than 0',
                ['value' => $shutdownTimeout]
            );
        }
    }

    /**
     * Validate buffer size without modifying state.
     *
     * @param int $bufferSize Buffer size to validate
     * @throws ValidationError If buffer size is invalid
     */
    private function validateBufferSize(int $bufferSize): void
    {
        if ($bufferSize <= 0) {
            throw ValidationError::invalidFieldValue(
                'buffer_size',
                'must be greater than 0',
                ['value' => $bufferSize]
            );
        }
    }

    /**
     * Validate environment variables without modifying state.
     *
     * @param array<string, string> $env Environment variables to validate
     * @throws ValidationError If environment variables are invalid
     */
    private function validateEnv(array $env): void
    {
        foreach ($env as $key => $value) {
            if (! is_string($key) || empty($key)) {
                throw ValidationError::invalidFieldValue(
                    'env',
                    'Environment variable keys must be non-empty strings',
                    ['key' => $key]
                );
            }

            if (! is_string($value)) {
                throw ValidationError::invalidFieldValue(
                    'env',
                    'Environment variable values must be strings',
                    ['key' => $key, 'value' => $value]
                );
            }
        }
    }
}
