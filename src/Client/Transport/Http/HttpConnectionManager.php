<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client\Transport\Http;

use Dtyq\PhpMcp\Client\Configuration\HttpConfig;
use Dtyq\PhpMcp\Shared\Kernel\Logger\LoggerProxy;

/**
 * HTTP connection manager for transport.
 *
 * This class manages HTTP connections and session state.
 * Currently a placeholder - will be implemented in P1-2.
 */
class HttpConnectionManager
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
     * Clear session data.
     * TODO: Implement in P1-2.
     */
    public function clearSession(): void
    {
        // Placeholder
    }
}
