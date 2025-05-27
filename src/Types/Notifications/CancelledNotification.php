<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Notifications;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Core\NotificationInterface;

/**
 * Notification to cancel a previously-issued request.
 *
 * Can be sent by either side to indicate that a request should be cancelled.
 */
class CancelledNotification implements NotificationInterface
{
    private string $method = 'notifications/cancelled';

    private $requestId;

    private ?string $reason = null;

    private ?array $meta = null;

    /**
     * @param int|string $requestId The ID of the request to cancel
     * @param null|string $reason Optional reason for cancellation
     * @param null|array<string, mixed> $meta Optional meta information
     */
    public function __construct($requestId, ?string $reason = null, ?array $meta = null)
    {
        $this->setRequestId($requestId);
        $this->reason = $reason;
        $this->meta = $meta;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getParams(): ?array
    {
        $params = [
            'requestId' => $this->requestId,
        ];

        if ($this->reason !== null) {
            $params['reason'] = $this->reason;
        }

        if ($this->meta !== null) {
            $params['_meta'] = $this->meta;
        }

        return $params;
    }

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

    public function getMeta(): ?array
    {
        return $this->meta;
    }

    public function setMeta(?array $meta): void
    {
        $this->meta = $meta;
    }

    /**
     * @return int|string
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * @param int|string $requestId
     * @throws ValidationError
     */
    public function setRequestId($requestId): void
    {
        if (! is_string($requestId) && ! is_int($requestId)) {
            throw ValidationError::invalidArgumentType('requestId', 'string or integer', gettype($requestId));
        }
        $this->requestId = $requestId;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): void
    {
        $this->reason = $reason;
    }

    /**
     * Create from array data.
     *
     * @param array<string, mixed> $data
     * @throws ValidationError
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['params']['requestId'])) {
            throw ValidationError::requiredFieldMissing('requestId', 'CancelledNotification');
        }

        return new self(
            $data['params']['requestId'],
            $data['params']['reason'] ?? null,
            $data['params']['_meta'] ?? null
        );
    }
}
