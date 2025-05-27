<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Notifications;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Core\NotificationInterface;

/**
 * Notification that a specific resource has been updated.
 *
 * Sent by the server to inform clients that a subscribed resource
 * has changed and should be re-read.
 */
class ResourceUpdatedNotification implements NotificationInterface
{
    private string $method = 'notifications/resources/updated';

    private string $uri;

    /** @var null|array<string, mixed> */
    private ?array $meta = null;

    /**
     * @param string $uri The URI of the updated resource
     * @param null|array<string, mixed> $meta Optional meta information
     */
    public function __construct(string $uri, ?array $meta = null)
    {
        $this->setUri($uri);
        $this->meta = $meta;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    /** @return array<string, mixed> */
    public function getParams(): array
    {
        $params = [
            'uri' => $this->uri,
        ];

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

    public function getUri(): string
    {
        return $this->uri;
    }

    public function setUri(string $uri): void
    {
        if (empty($uri)) {
            throw ValidationError::emptyField('uri');
        }
        $this->uri = $uri;
    }

    /**
     * Create from array data.
     *
     * @param array<string, mixed> $data
     * @throws ValidationError
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['params']['uri'])) {
            throw ValidationError::requiredFieldMissing('uri', 'ResourceUpdatedNotification');
        }

        return new self(
            $data['params']['uri'],
            $data['params']['_meta'] ?? null
        );
    }
}
