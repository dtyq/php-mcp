<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Utilities\SSE;

/**
 * Represents a Server-Sent Event (SSE) with its properties.
 */
final class SSEEvent
{
    /**
     * @var string The event ID
     */
    public string $id;

    /**
     * @var string The event type. Defaults to 'message'
     */
    public string $event;

    /**
     * @var string The data payload of the event
     */
    public string $data;

    /**
     * @var int The reconnection time in milliseconds
     */
    public int $retry;

    /**
     * SSEEvent constructor.
     *
     * @param string $id The event ID
     * @param string $event The event type. Defaults to 'message'.
     * @param string $data The data payload of the event
     * @param int $retry The reconnection time in milliseconds
     */
    public function __construct(
        string $id = '',
        string $event = 'message',
        string $data = '',
        int $retry = 0
    ) {
        $this->id = $id;
        $this->event = $event;
        $this->data = $data;
        $this->retry = $retry;
    }
}
