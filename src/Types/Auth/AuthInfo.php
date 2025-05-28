<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Auth;

/**
 * Authentication information for MCP operations.
 *
 * Simple data container for authentication context
 */
class AuthInfo
{
    /**
     * @var string 用户或客户端标识
     */
    private string $subject;

    /**
     * @var string[] 权限范围
     */
    private array $scopes;

    /**
     * @var array<string, mixed> 额外元数据
     */
    private array $metadata;

    /**
     * @var null|int 过期时间戳
     */
    private ?int $expiresAt;

    /**
     * @param string[] $scopes
     * @param array<string, mixed> $metadata
     */
    public function __construct(string $subject, array $scopes = [], array $metadata = [], ?int $expiresAt = null)
    {
        $this->subject = $subject;
        $this->scopes = $scopes;
        $this->metadata = $metadata;
        $this->expiresAt = $expiresAt;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @return string[]
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadataAll(): array
    {
        return $this->metadata;
    }

    public function getExpiresAt(): ?int
    {
        return $this->expiresAt;
    }

    /**
     * Check if has specific scope.
     */
    public function hasScope(string $scope): bool
    {
        return in_array('*', $this->scopes, true) || in_array($scope, $this->scopes, true);
    }

    /**
     * Check if has all required scopes.
     *
     * @param string[] $scopes
     */
    public function hasAllScopes(array $scopes): bool
    {
        return in_array('*', $this->scopes, true) || empty(array_diff($scopes, $this->scopes));
    }

    /**
     * Check if has any of the specified scopes.
     *
     * @param string[] $scopes
     */
    public function hasAnyScope(array $scopes): bool
    {
        return in_array('*', $this->scopes, true) || ! empty(array_intersect($scopes, $this->scopes));
    }

    /**
     * Check if authentication is expired.
     */
    public function isExpired(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt < time();
    }

    /**
     * Get metadata value.
     *
     * @param mixed $default
     * @return mixed
     */
    public function getMetadata(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Create authentication info with minimal data.
     *
     * @param string[] $scopes
     * @param array<string, mixed> $metadata
     */
    public static function create(
        string $subject,
        array $scopes = [],
        array $metadata = [],
        ?int $expiresAt = null
    ): self {
        return new self($subject, $scopes, $metadata, $expiresAt);
    }

    /**
     * Create anonymous auth info (for testing or fallback).
     */
    public static function anonymous(): self
    {
        return new self('anonymous', ['*'], ['type' => 'anonymous']);
    }
}
