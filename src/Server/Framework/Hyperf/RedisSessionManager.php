<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Framework\Hyperf;

use Dtyq\PhpMcp\Server\Transports\Http\SessionManagerInterface;
use Dtyq\PhpMcp\Shared\Kernel\Packer\PackerInterface;
use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;
use Psr\Container\ContainerInterface;

/**
 * Redis-based session manager implementation.
 *
 * This implementation stores sessions in Redis and is suitable for:
 * - Production environments with high performance requirements
 * - Multi-process applications with shared session state
 * - Distributed systems requiring centralized session storage
 *
 * Features:
 * - Automatic session expiration with TTL
 * - High performance with Redis
 * - Concurrent access support
 */
class RedisSessionManager implements SessionManagerInterface
{
    protected RedisProxy $redisProxy;

    // Session TTL in seconds (default: 30 minutes)
    private int $sessionTtl = 1800;

    // Redis key prefix for sessions
    private string $keyPrefix = 'mcp:session:';

    private PackerInterface $packer;

    public function __construct(
        ContainerInterface $container,
        RedisFactory $redisFactory,
        ?int $sessionTtl = null
    ) {
        $this->packer = $container->get(PackerInterface::class);
        $this->redisProxy = $redisFactory->get('default');
        if ($sessionTtl !== null) {
            $this->sessionTtl = $sessionTtl;
        }
    }

    /**
     * Create a new session and return the session ID.
     */
    public function createSession(): string
    {
        $sessionId = $this->generateSessionId();
        $sessionKey = $this->getSessionKey($sessionId);

        $sessionData = [
            'created_at' => time(),
            'last_activity' => time(),
        ];

        // Store session data with TTL
        $this->redisProxy->hMSet($sessionKey, $sessionData);
        $this->redisProxy->expire($sessionKey, $this->sessionTtl);

        return $sessionId;
    }

    /**
     * Check if a session exists and is valid.
     */
    public function isValidSession(string $sessionId): bool
    {
        $sessionKey = $this->getSessionKey($sessionId);
        return $this->redisProxy->exists($sessionKey) > 0;
    }

    /**
     * Update the last activity timestamp for a session.
     */
    public function updateSessionActivity(string $sessionId): bool
    {
        $sessionKey = $this->getSessionKey($sessionId);

        if (! $this->isValidSession($sessionId)) {
            return false;
        }

        // Update last activity and refresh TTL
        $this->redisProxy->hSet($sessionKey, 'last_activity', time());
        $this->redisProxy->expire($sessionKey, $this->sessionTtl);

        return true;
    }

    /**
     * Terminate a session.
     */
    public function terminateSession(string $sessionId): bool
    {
        $sessionKey = $this->getSessionKey($sessionId);

        if (! $this->isValidSession($sessionId)) {
            return false;
        }

        return $this->redisProxy->del($sessionKey) > 0;
    }

    /**
     * Get all active session IDs.
     */
    public function getActiveSessions(): array
    {
        $pattern = $this->keyPrefix . '*';
        $keys = $this->redisProxy->keys($pattern);

        // Extract session IDs from keys
        return array_map(function ($key) {
            return substr($key, strlen($this->keyPrefix));
        }, $keys);
    }

    /**
     * Clean up expired sessions.
     *
     * Note: Redis automatically handles TTL expiration, but this method
     * can be used to manually clean up sessions if needed.
     */
    public function cleanupExpiredSessions(): int
    {
        // Redis handles TTL automatically, but we can scan for any orphaned sessions
        $pattern = $this->keyPrefix . '*';
        $keys = $this->redisProxy->keys($pattern);
        $cleanedCount = 0;

        foreach ($keys as $key) {
            $ttl = $this->redisProxy->ttl($key);
            // If TTL is -1 (no expiration set) or expired, clean it up
            if ($ttl === -1 || $ttl === -2) {
                $this->redisProxy->del($key);
                ++$cleanedCount;
            }
        }

        return $cleanedCount;
    }

    /**
     * Get session details (for debugging/monitoring).
     *
     * @param string $sessionId The session ID
     * @return null|array<string, int> Session details or null if not found
     */
    public function getSessionDetails(string $sessionId): ?array
    {
        $sessionKey = $this->getSessionKey($sessionId);

        if (! $this->isValidSession($sessionId)) {
            return null;
        }

        $sessionData = $this->redisProxy->hGetAll($sessionKey);

        if (empty($sessionData)) {
            return null;
        }

        // Convert string values back to integers
        return [
            'created_at' => (int) $sessionData['created_at'],
            'last_activity' => (int) $sessionData['last_activity'],
            'ttl' => $this->redisProxy->ttl($sessionKey),
        ];
    }

    /**
     * Get session count.
     *
     * @return int Number of active sessions
     */
    public function getSessionCount(): int
    {
        $pattern = $this->keyPrefix . '*';
        $keys = $this->redisProxy->keys($pattern);
        return count($keys);
    }

    /**
     * Set metadata for a session.
     */
    public function setSessionMetadata(string $sessionId, array $metadata): bool
    {
        $sessionKey = $this->getSessionKey($sessionId);

        if (! $this->isValidSession($sessionId)) {
            return false;
        }

        // Store metadata as JSON string in Redis hash
        $this->redisProxy->hSet($sessionKey, 'metadata', $this->packer->pack($metadata));

        // Update last activity and refresh TTL
        $this->redisProxy->hSet($sessionKey, 'last_activity', time());
        $this->redisProxy->expire($sessionKey, $this->sessionTtl);

        return true;
    }

    /**
     * Get metadata for a session.
     */
    public function getSessionMetadata(string $sessionId): ?array
    {
        $sessionKey = $this->getSessionKey($sessionId);

        if (! $this->isValidSession($sessionId)) {
            return null;
        }

        $metadata = $this->redisProxy->hGet($sessionKey, 'metadata');

        if ($metadata === false || $metadata === null) {
            return [];
        }

        return $this->packer->unpack($metadata);
    }

    /**
     * Get Redis key for session.
     *
     * @param string $sessionId The session ID
     * @return string Redis key for the session
     */
    private function getSessionKey(string $sessionId): string
    {
        return $this->keyPrefix . $sessionId;
    }

    /**
     * Generate a cryptographically secure session ID.
     *
     * @return string UUID v4 format session ID
     */
    private function generateSessionId(): string
    {
        // Generate UUID v4 format session ID (globally unique and cryptographically secure)
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0F | 0x40); // Version 4
        $data[8] = chr(ord($data[8]) & 0x3F | 0x80); // Variant bits

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
