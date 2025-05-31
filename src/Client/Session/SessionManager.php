<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client\Session;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Exception;

/**
 * Simple session container for managing multiple client sessions.
 *
 * This class provides basic session storage and retrieval functionality.
 * No complex logic, just a simple container.
 */
class SessionManager
{
    /** @var array<string, ClientSession> Active sessions by ID */
    private array $sessions = [];

    /**
     * Add a session to the manager.
     *
     * @param string $sessionId Unique session identifier
     * @param ClientSession $session The session to add
     * @throws ValidationError If session ID already exists
     */
    public function addSession(string $sessionId, ClientSession $session): void
    {
        if (isset($this->sessions[$sessionId])) {
            throw ValidationError::invalidFieldValue(
                'sessionId',
                'session ID already exists',
                ['sessionId' => $sessionId]
            );
        }

        $this->sessions[$sessionId] = $session;
    }

    /**
     * Remove a session from the manager.
     *
     * @param string $sessionId Session ID to remove
     * @return bool True if session was removed
     */
    public function removeSession(string $sessionId): bool
    {
        if (! isset($this->sessions[$sessionId])) {
            return false;
        }

        // Close the session
        try {
            $this->sessions[$sessionId]->close();
        } catch (Exception $e) {
            // Continue with removal even if close fails
        }

        // Remove from storage
        unset($this->sessions[$sessionId]);

        return true;
    }

    /**
     * Get a session by ID.
     *
     * @param string $sessionId Session ID
     * @return ClientSession The session
     * @throws ValidationError If session not found
     */
    public function getSession(string $sessionId): ClientSession
    {
        if (! isset($this->sessions[$sessionId])) {
            throw ValidationError::invalidFieldValue(
                'sessionId',
                'Session not found',
                ['sessionId' => $sessionId]
            );
        }

        return $this->sessions[$sessionId];
    }

    /**
     * Check if a session exists.
     *
     * @param string $sessionId Session ID
     * @return bool True if session exists
     */
    public function hasSession(string $sessionId): bool
    {
        return isset($this->sessions[$sessionId]);
    }

    /**
     * Get all session IDs.
     *
     * @return array<string> Array of session IDs
     */
    public function getSessionIds(): array
    {
        return array_keys($this->sessions);
    }

    /**
     * Get total number of sessions.
     *
     * @return int Number of sessions
     */
    public function getSessionCount(): int
    {
        return count($this->sessions);
    }

    /**
     * Close all sessions.
     */
    public function closeAll(): void
    {
        foreach ($this->sessions as $session) {
            try {
                $session->close();
            } catch (Exception $e) {
                // Continue closing other sessions even if one fails
            }
        }

        $this->sessions = [];
    }

    /**
     * Get basic statistics.
     *
     * @return array<string, mixed> Basic statistics
     */
    public function getStats(): array
    {
        return [
            'totalSessions' => count($this->sessions),
            'sessionIds' => array_keys($this->sessions),
        ];
    }
}
