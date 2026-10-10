<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Security;

use Dtyq\PhpMcp\Shared\Exceptions\SsrfException;

/**
 * 按连接保存 SSRF 配置，保持与 cloudfile 的等级和 IP 开关语义一致。
 */
class SsrfConfig
{
    private bool $enabled;

    private string $level;

    /** @var array<string, mixed> */
    private array $ipProtection;

    /**
     * 解析配置；错误的开关值不能静默关闭防护。
     *
     * @param array<string, mixed> $options
     */
    public function __construct(array $options = [])
    {
        $this->enabled = $this->parseBoolean(array_key_exists('enabled', $options) ? $options['enabled'] : false);
        $level = $options['level'] ?? 'strict';
        if (! is_string($level) || ! in_array($level, ['strict', 'standard', 'compatible'], true)) {
            throw new SsrfException('Invalid security level');
        }
        $this->level = $level;
        $ipProtection = $options['ip_protection'] ?? [];
        if (! is_array($ipProtection)) {
            throw new SsrfException('Invalid IP protection configuration');
        }
        $this->ipProtection = $ipProtection;
        foreach (['private', 'loopback', 'link_local', 'reserved', 'cloud_metadata'] as $type) {
            $key = 'block_' . $type . '_ip';
            $this->ipProtection[$key] = $this->parseBoolean($this->ipProtection[$key] ?? true);
        }
        foreach (['extra_blocked_ips', 'extra_blocked_cidrs'] as $key) {
            $values = $this->ipProtection[$key] ?? [];
            if (! is_array($values)) {
                throw new SsrfException('IP exclusions must be arrays');
            }
            foreach ($values as $value) {
                if (! is_string($value)) {
                    throw new SsrfException('Invalid IP exclusion');
                }
                $parts = explode('/', $value);
                $packed = @inet_pton($parts[0]);
                if ($packed === false || ($key === 'extra_blocked_ips' && count($parts) !== 1)
                    || ($key === 'extra_blocked_cidrs' && (count($parts) !== 2 || ! ctype_digit($parts[1]) || (int) $parts[1] > strlen($packed) * 8))) {
                    throw new SsrfException('Invalid IP exclusion');
                }
            }
            $this->ipProtection[$key] = array_values($values);
        }
    }

    /** 获取防护开关。 */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /** 获取 URL 协议与端口限制等级。 */
    public function getLevel(): string
    {
        return $this->level;
    }

    /** 获取指定 IP 分类的拦截开关。 */
    public function blocks(string $type): bool
    {
        return $this->ipProtection['block_' . $type . '_ip'];
    }

    /**
     * 获取额外拦截的地址或网段。
     *
     * @return string[]
     */
    public function getExclusions(string $key): array
    {
        return $this->ipProtection[$key];
    }

    /**
     * 兼容布尔值与环境变量中的布尔字符串。
     *
     * @param mixed $value
     */
    private function parseBoolean($value): bool
    {
        if (! is_bool($value) && ! is_string($value) && ! is_int($value)) {
            throw new SsrfException('Invalid security switch');
        }
        $result = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($result === null) {
            throw new SsrfException('Invalid security switch');
        }
        return $result;
    }
}
