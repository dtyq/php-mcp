<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Transports\Http;

/**
 * Interface for managing MCP HTTP sessions.
 *
 * This interface defines the contract for session management in MCP HTTP transport.
 * Implementations can be in-memory for development, Redis for production, etc.
 */
interface SessionManagerInterface
{
    /**
     * Create a new session and return the session ID.
     *
     * @return string The generated session ID
     */
    public function createSession(): string;

    /**
     * Check if a session exists and is valid.
     *
     * @param string $sessionId The session ID to validate
     * @return bool True if session is valid, false otherwise
     */
    public function isValidSession(string $sessionId): bool;

    /**
     * Update the last activity timestamp for a session.
     *
     * @param string $sessionId The session ID to update
     * @return bool True if session was updated, false if session doesn't exist
     */
    public function updateSessionActivity(string $sessionId): bool;

    /**
     * Terminate a session.
     *
     * @param string $sessionId The session ID to terminate
     * @return bool True if session was terminated, false if session didn't exist
     */
    public function terminateSession(string $sessionId): bool;

    /**
     * Get all active session IDs.
     *
     * @return string[] Array of active session IDs
     */
    public function getActiveSessions(): array;

    /**
     * Clean up expired sessions (implementation-specific).
     *
     * @return int Number of sessions cleaned up
     */
    public function cleanupExpiredSessions(): int;

    /**
     * Set metadata for a session.
     *
     * @param string $sessionId The session ID
     * @param array<string, mixed> $metadata The metadata to store
     * @return bool True if metadata was set, false if session doesn't exist
     */
    public function setSessionMetadata(string $sessionId, array $metadata): bool;

    /**
     * Get metadata for a session.
     *
     * @param string $sessionId The session ID
     * @return null|array<string, mixed> The session metadata or null if session doesn't exist
     */
    public function getSessionMetadata(string $sessionId): ?array;
}
