<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client\Transport\Http;

use Dtyq\PhpMcp\Client\Configuration\HttpConfig;
use Dtyq\PhpMcp\Shared\Kernel\Logger\LoggerProxy;

/**
 * SSE stream handler for HTTP transport.
 *
 * This class handles Server-Sent Events connections and message parsing.
 * Currently a placeholder - will be implemented in P1-1.
 */
class SseStreamHandler
{
    /** @var HttpConfig Transport configuration */
    private HttpConfig $config;

    /** @var LoggerProxy Logger instance */
    private LoggerProxy $logger;

    /**
     * @param HttpConfig $config Transport configuration
     * @param LoggerProxy $logger Logger instance
     */
    public function __construct(HttpConfig $config, LoggerProxy $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Disconnect SSE stream.
     * TODO: Implement in P1-1.
     */
    public function disconnect(): void
    {
        // Placeholder
    }
}
