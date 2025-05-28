<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Notifications;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Core\NotificationInterface;

/**
 * Progress notification for long-running requests.
 *
 * Used to inform the receiver of progress updates for operations
 * that were initiated with a progress token.
 */
class ProgressNotification implements NotificationInterface
{
    private string $method = 'notifications/progress';

    /** @var int|string */
    private $progressToken;

    private int $progress;

    private ?int $total = null;

    /** @var null|array<string, mixed> */
    private ?array $meta = null;

    /**
     * @param int|string $progressToken The progress token from the original request
     * @param int $progress Current progress value (should increase over time)
     * @param null|int $total Total expected progress (optional)
     * @param null|array<string, mixed> $meta Optional meta information
     */
    public function __construct($progressToken, int $progress, ?int $total = null, ?array $meta = null)
    {
        $this->setProgressToken($progressToken);
        $this->setProgress($progress);
        $this->total = $total;
        $this->meta = $meta;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    /** @return null|array<string, mixed> */
    public function getParams(): ?array
    {
        $params = [
            'progressToken' => $this->progressToken,
            'progress' => $this->progress,
        ];

        if ($this->total !== null) {
            $params['total'] = $this->total;
        }

        if ($this->meta !== null) {
            $params['_meta'] = $this->meta;
        }

        return $params;
    }

    /** @return array<string, mixed> */
    public function toJsonRpc(): array
    {
        return [
            'jsonrpc' => '2.0',
            'method' => $this->method,
            'params' => $this->getParams(),
        ];
    }

    public function hasMeta(): bool
    {
        return $this->meta !== null;
    }

    /** @return null|array<string, mixed> */
    public function getMeta(): ?array
    {
        return $this->meta;
    }

    /** @param null|array<string, mixed> $meta */
    public function setMeta(?array $meta): void
    {
        $this->meta = $meta;
    }

    /**
     * @return int|string
     */
    public function getProgressToken()
    {
        return $this->progressToken;
    }

    /**
     * @param int|string $progressToken
     * @throws ValidationError
     */
    public function setProgressToken($progressToken): void
    {
        if (! is_string($progressToken) && ! is_int($progressToken)) {
            throw ValidationError::invalidArgumentType('progressToken', 'string or integer', gettype($progressToken));
        }
        $this->progressToken = $progressToken;
    }

    public function getProgress(): int
    {
        return $this->progress;
    }

    public function setProgress(int $progress): void
    {
        if ($progress < 0) {
            throw ValidationError::invalidFieldValue('progress', 'must be non-negative');
        }
        $this->progress = $progress;
    }

    public function getTotal(): ?int
    {
        return $this->total;
    }

    public function setTotal(?int $total): void
    {
        if ($total !== null && $total < 0) {
            throw ValidationError::invalidFieldValue('total', 'must be non-negative');
        }
        $this->total = $total;
    }

    /**
     * Get progress as a percentage (0-100).
     * Returns null if total is not set.
     */
    public function getProgressPercentage(): ?float
    {
        if ($this->total === null || $this->total === 0) {
            return null;
        }
        return min(100.0, ($this->progress / $this->total) * 100);
    }

    /**
     * Check if the operation is complete.
     */
    public function isComplete(): bool
    {
        return $this->total !== null && $this->progress >= $this->total;
    }

    /**
     * Create from array data.
     *
     * @param array<string, mixed> $data
     * @throws ValidationError
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['params']['progressToken'])) {
            throw ValidationError::requiredFieldMissing('progressToken', 'ProgressNotification');
        }

        if (! isset($data['params']['progress'])) {
            throw ValidationError::requiredFieldMissing('progress', 'ProgressNotification');
        }

        if (! is_int($data['params']['progress'])) {
            throw ValidationError::invalidFieldType('progress', 'integer', gettype($data['params']['progress']));
        }

        return new self(
            $data['params']['progressToken'],
            $data['params']['progress'],
            $data['params']['total'] ?? null,
            $data['params']['_meta'] ?? null
        );
    }
}
