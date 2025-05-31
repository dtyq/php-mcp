<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client\Core;

use JsonSerializable;

/**
 * Client statistics tracker.
 *
 * Provides type-safe management of client runtime statistics
 * including connection metrics, session states, and performance data.
 */
class ClientStats implements JsonSerializable
{
    private float $createdAt;

    private ?float $connectedAt = null;

    private ?float $closedAt = null;

    private int $connectionAttempts = 0;

    private int $connectionErrors = 0;

    private int $closeErrors = 0;

    /** @var array<string, mixed> Session manager statistics */
    private array $sessionStats = [];

    public function __construct()
    {
        $this->createdAt = microtime(true);
    }

    /**
     * Record a connection attempt.
     */
    public function recordConnectionAttempt(): void
    {
        ++$this->connectionAttempts;
        $this->connectedAt = microtime(true);
    }

    /**
     * Record a connection error.
     */
    public function recordConnectionError(): void
    {
        ++$this->connectionErrors;
    }

    /**
     * Record a close error.
     */
    public function recordCloseError(): void
    {
        ++$this->closeErrors;
    }

    /**
     * Record client closure.
     */
    public function recordClosure(): void
    {
        $this->closedAt = microtime(true);
    }

    /**
     * Update session statistics.
     *
     * @param array<string, mixed> $sessionStats Session manager statistics
     */
    public function updateSessionStats(array $sessionStats): void
    {
        $this->sessionStats = $sessionStats;
    }

    /**
     * Get creation timestamp.
     */
    public function getCreatedAt(): float
    {
        return $this->createdAt;
    }

    /**
     * Get connection timestamp.
     */
    public function getConnectedAt(): ?float
    {
        return $this->connectedAt;
    }

    /**
     * Get closure timestamp.
     */
    public function getClosedAt(): ?float
    {
        return $this->closedAt;
    }

    /**
     * Get total connection attempts.
     */
    public function getConnectionAttempts(): int
    {
        return $this->connectionAttempts;
    }

    /**
     * Get connection error count.
     */
    public function getConnectionErrors(): int
    {
        return $this->connectionErrors;
    }

    /**
     * Get close error count.
     */
    public function getCloseErrors(): int
    {
        return $this->closeErrors;
    }

    /**
     * Get current uptime in seconds.
     */
    public function getUptime(): float
    {
        if ($this->connectedAt === null) {
            return 0.0;
        }

        $endTime = $this->closedAt ?? microtime(true);
        return $endTime - $this->connectedAt;
    }

    /**
     * Check if client has active session.
     */
    public function hasActiveSession(): bool
    {
        return ($this->sessionStats['totalSessions'] ?? 0) > 0;
    }

    /**
     * Check if session is initialized.
     */
    public function isSessionInitialized(): bool
    {
        return ($this->sessionStats['activeSessions'] ?? 0) > 0;
    }

    /**
     * Get connection success rate.
     */
    public function getConnectionSuccessRate(): float
    {
        if ($this->connectionAttempts === 0) {
            return 0.0;
        }

        $successfulConnections = $this->connectionAttempts - $this->connectionErrors;
        return $successfulConnections / $this->connectionAttempts;
    }

    /**
     * Check if client is currently connected.
     */
    public function isConnected(): bool
    {
        return $this->connectedAt !== null && $this->closedAt === null;
    }

    /**
     * Get current status summary.
     */
    public function getStatus(): string
    {
        if ($this->closedAt !== null) {
            return 'closed';
        }

        if ($this->connectedAt !== null) {
            return $this->hasActiveSession() ? 'connected' : 'connecting';
        }

        return 'created';
    }

    /**
     * Get all statistics as array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'createdAt' => $this->createdAt,
            'connectedAt' => $this->connectedAt,
            'closedAt' => $this->closedAt,
            'connectionAttempts' => $this->connectionAttempts,
            'connectionErrors' => $this->connectionErrors,
            'closeErrors' => $this->closeErrors,
            'uptime' => $this->getUptime(),
            'hasActiveSession' => $this->hasActiveSession(),
            'sessionInitialized' => $this->isSessionInitialized(),
            'connectionSuccessRate' => $this->getConnectionSuccessRate(),
            'status' => $this->getStatus(),
            'sessionManager' => $this->sessionStats,
        ];
    }

    /**
     * JSON serialization support.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Reset all statistics.
     */
    public function reset(): void
    {
        $this->createdAt = microtime(true);
        $this->connectedAt = null;
        $this->closedAt = null;
        $this->connectionAttempts = 0;
        $this->connectionErrors = 0;
        $this->closeErrors = 0;
        $this->sessionStats = [];
    }

    /**
     * Create from array data.
     *
     * @param array<string, mixed> $data Statistics data
     */
    public static function fromArray(array $data): self
    {
        $stats = new self();

        if (isset($data['createdAt'])) {
            $stats->createdAt = (float) $data['createdAt'];
        }

        if (isset($data['connectedAt'])) {
            $stats->connectedAt = (float) $data['connectedAt'];
        }

        if (isset($data['closedAt'])) {
            $stats->closedAt = (float) $data['closedAt'];
        }

        if (isset($data['connectionAttempts'])) {
            $stats->connectionAttempts = (int) $data['connectionAttempts'];
        }

        if (isset($data['connectionErrors'])) {
            $stats->connectionErrors = (int) $data['connectionErrors'];
        }

        if (isset($data['closeErrors'])) {
            $stats->closeErrors = (int) $data['closeErrors'];
        }

        if (isset($data['sessionManager']) && is_array($data['sessionManager'])) {
            $stats->sessionStats = $data['sessionManager'];
        }

        return $stats;
    }
}
