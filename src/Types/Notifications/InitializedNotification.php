<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Notifications;

use Dtyq\PhpMcp\Types\Core\NotificationInterface;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;

/**
 * Notification sent from client to server after initialization has finished.
 *
 * This notification indicates that the client has completed its initialization
 * process and is ready to begin normal operation.
 */
class InitializedNotification implements NotificationInterface
{
    private string $method = ProtocolConstants::NOTIFICATION_INITIALIZED;

    /** @var null|array<string, mixed> */
    private ?array $meta = null;

    /**
     * @param null|array<string, mixed> $meta Optional meta information
     */
    public function __construct(?array $meta = null)
    {
        $this->meta = $meta;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    /** @return null|array<string, mixed> */
    public function getParams(): ?array
    {
        $params = [];

        if ($this->meta !== null) {
            $params['_meta'] = $this->meta;
        }

        return empty($params) ? null : $params;
    }

    /** @return array<string, mixed> */
    public function toJsonRpc(): array
    {
        $data = [
            'jsonrpc' => ProtocolConstants::JSONRPC_VERSION,
            'method' => $this->method,
        ];

        $params = $this->getParams();
        if ($params !== null) {
            $data['params'] = $params;
        }

        return $data;
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

    /**
     * Set meta information.
     *
     * @param null|array<string, mixed> $meta
     */
    public function setMeta(?array $meta): void
    {
        $this->meta = $meta;
    }

    /**
     * Create from array data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $meta = $data['params']['_meta'] ?? null;
        return new self($meta);
    }
}
