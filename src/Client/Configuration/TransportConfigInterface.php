<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client\Configuration;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use JsonSerializable;

/**
 * Interface for transport configuration classes.
 * 传输配置类的接口。
 *
 * This interface defines the common contract that all transport configuration
 * classes must implement, providing consistent behavior for validation,
 * serialization, and configuration management.
 * 此接口定义了所有传输配置类必须实现的通用契约，提供验证、序列化和配置管理的一致行为。
 */
interface TransportConfigInterface extends JsonSerializable
{
    /**
     * Create configuration from array.
     * 从数组创建配置。
     *
     * This method allows creating a configuration instance from an associative array,
     * enabling easy configuration loading from files, environment variables, or other sources.
     * 此方法允许从关联数组创建配置实例，便于从文件、环境变量或其他来源加载配置。
     *
     * @param array<string, mixed> $config Configuration array / 配置数组
     * @return self Configuration instance / 配置实例
     * @throws ValidationError If configuration is invalid / 如果配置无效
     */
    public static function fromArray(array $config): self;

    /**
     * Get the transport type identifier.
     * 获取传输类型标识符。
     *
     * Returns the transport type identifier that this configuration is designed for.
     * This allows the factory to determine the appropriate transport implementation
     * without using instanceof checks.
     * 返回此配置设计用于的传输类型标识符。这允许工厂在不使用 instanceof 检查的情况下
     * 确定适当的传输实现。
     *
     * @return string Transport type identifier / 传输类型标识符
     */
    public function getTransportType(): string;

    /**
     * Convert configuration to array.
     * 将配置转换为数组。
     *
     * This method serializes the configuration object to an associative array,
     * useful for storage, logging, or passing to other systems.
     * 此方法将配置对象序列化为关联数组，用于存储、日志记录或传递给其他系统。
     *
     * @return array<string, mixed> Configuration as array / 配置数组
     */
    public function toArray(): array;

    /**
     * Validate the complete configuration.
     * 验证完整配置。
     *
     * This method performs comprehensive validation of all configuration values,
     * ensuring they meet the requirements for the specific transport type.
     * 此方法对所有配置值进行全面验证，确保它们满足特定传输类型的要求。
     *
     * @throws ValidationError If configuration is invalid / 如果配置无效
     */
    public function validate(): void;

    /**
     * Create a copy of this configuration with modified values.
     * 创建具有修改值的配置副本。
     *
     * This method creates a new configuration instance with the specified changes
     * applied, leaving the original configuration unchanged (immutable pattern).
     * 此方法创建一个应用了指定更改的新配置实例，保持原始配置不变（不可变模式）。
     *
     * @param array<string, mixed> $changes Values to change / 要更改的值
     * @return self New configuration instance / 新的配置实例
     */
    public function withChanges(array $changes): self;
}
