<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Transports\Http;

use Dtyq\PhpMcp\Shared\Kernel\Packer\PackerInterface;
use Dtyq\PhpMcp\Shared\Utilities\JsonUtils;

/**
 * File-based session manager implementation.
 *
 * This implementation stores sessions in a file and is suitable for:
 * - Development and testing with persistence
 * - Single-process servers with session persistence
 * - Simple deployment scenarios
 *
 * For production use with multiple processes or high-performance requirements,
 * consider using a Redis-based implementation.
 */
class FileSessionManager implements SessionManagerInterface
{
    private string $sessionFile;

    private PackerInterface $packer;

    public function __construct(PackerInterface $packer, ?string $runtimePath = null)
    {
        $this->packer = $packer;

        // Default to runtime directory in project root
        $runtimePath = $runtimePath ?? dirname(__DIR__, 4) . '/runtime';

        // Ensure runtime directory exists
        if (! is_dir($runtimePath)) {
            mkdir($runtimePath, 0755, true);
        }

        $this->sessionFile = $runtimePath . '/sessions.json';
    }

    /**
     * Create a new session and return the session ID.
     */
    public function createSession(): string
    {
        $sessionId = $this->generateSessionId();
        $sessions = $this->loadSessions();

        $sessions[$sessionId] = [
            'created_at' => time(),
            'last_activity' => time(),
        ];

        $this->saveSessions($sessions);
        return $sessionId;
    }

    /**
     * Check if a session exists and is valid.
     */
    public function isValidSession(string $sessionId): bool
    {
        $sessions = $this->loadSessions();
        return isset($sessions[$sessionId]);
    }

    /**
     * Update the last activity timestamp for a session.
     */
    public function updateSessionActivity(string $sessionId): bool
    {
        $sessions = $this->loadSessions();

        if (! isset($sessions[$sessionId])) {
            return false;
        }

        $sessions[$sessionId]['last_activity'] = time();
        $this->saveSessions($sessions);
        return true;
    }

    /**
     * Terminate a session.
     */
    public function terminateSession(string $sessionId): bool
    {
        $sessions = $this->loadSessions();

        if (! isset($sessions[$sessionId])) {
            return false;
        }

        unset($sessions[$sessionId]);
        $this->saveSessions($sessions);
        return true;
    }

    /**
     * Get all active session IDs.
     */
    public function getActiveSessions(): array
    {
        $sessions = $this->loadSessions();
        return array_keys($sessions);
    }

    /**
     * Clean up expired sessions.
     *
     * Note: This file-based implementation doesn't enforce automatic TTL,
     * but provides this method for manual cleanup if needed.
     */
    public function cleanupExpiredSessions(): int
    {
        // File-based sessions don't have automatic TTL cleanup
        // This method can be extended to implement TTL-based cleanup if needed
        return 0;
    }

    /**
     * Get session details (for debugging/monitoring).
     *
     * @param string $sessionId The session ID
     * @return null|array<string, int> Session details or null if not found
     */
    public function getSessionDetails(string $sessionId): ?array
    {
        $sessions = $this->loadSessions();
        return $sessions[$sessionId] ?? null;
    }

    /**
     * Get session count.
     *
     * @return int Number of active sessions
     */
    public function getSessionCount(): int
    {
        $sessions = $this->loadSessions();
        return count($sessions);
    }

    /**
     * Set metadata for a session.
     */
    public function setSessionMetadata(string $sessionId, array $metadata): bool
    {
        $sessions = $this->loadSessions();

        if (! isset($sessions[$sessionId])) {
            return false;
        }

        $sessions[$sessionId]['metadata'] = $this->packer->pack($metadata);
        $sessions[$sessionId]['last_activity'] = time();

        $this->saveSessions($sessions);
        return true;
    }

    /**
     * Get metadata for a session.
     *
     * @return null|array<string, mixed>
     */
    public function getSessionMetadata(string $sessionId): ?array
    {
        $sessions = $this->loadSessions();

        if (! isset($sessions[$sessionId])) {
            return null;
        }
        return $this->packer->unpack($sessions[$sessionId]['metadata'] ?? '');
    }

    /**
     * Load sessions from file.
     *
     * @return array<string, array<string, int>>
     */
    private function loadSessions(): array
    {
        if (! file_exists($this->sessionFile)) {
            return [];
        }

        $content = file_get_contents($this->sessionFile);
        if ($content === false) {
            return [];
        }

        $sessions = JsonUtils::decode($content, true);
        return is_array($sessions) ? $sessions : [];
    }

    /**
     * Save sessions to file.
     *
     * @param array<string, array<string, int>> $sessions Sessions data to save
     */
    private function saveSessions(array $sessions): void
    {
        $content = JsonUtils::encode($sessions, JsonUtils::PRETTY_PRINT_FLAGS);
        file_put_contents($this->sessionFile, $content, LOCK_EX);
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
