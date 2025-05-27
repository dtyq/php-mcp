<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Notifications;

use Dtyq\PhpMcp\Types\Core\NotificationInterface;

/**
 * Notification sent from client to server after initialization has finished.
 *
 * This notification indicates that the client has completed its initialization
 * process and is ready to begin normal operation.
 */
class InitializedNotification implements NotificationInterface
{
    private string $method = 'notifications/initialized';

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

    public function getParams(): ?array
    {
        $params = [];

        if ($this->meta !== null) {
            $params['_meta'] = $this->meta;
        }

        return empty($params) ? null : $params;
    }

    public function toJsonRpc(): array
    {
        $data = [
            'jsonrpc' => '2.0',
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
