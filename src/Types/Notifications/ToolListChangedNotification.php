<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Notifications;

use Dtyq\PhpMcp\Types\Core\NotificationInterface;

/**
 * Notification that the list of available tools has changed.
 *
 * Sent by the server to inform clients that they should refresh
 * their tool list.
 */
class ToolListChangedNotification implements NotificationInterface
{
    private string $method = 'notifications/tools/list_changed';

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
        if ($this->meta !== null) {
            return ['_meta' => $this->meta];
        }
        return null;
    }

    /** @return array<string, mixed> */
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
     * Create from array data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['params']['_meta'] ?? null
        );
    }
}
