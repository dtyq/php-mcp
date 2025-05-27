<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Core;

use Dtyq\PhpMcp\Types\Core\JsonRpcRequest;
use Dtyq\PhpMcp\Types\Core\JsonRpcResponse;

/**
 * Interface for middleware implementations.
 */
interface MiddlewareInterface
{
    /**
     * Process request through middleware.
     *
     * @param JsonRpcRequest $request The request to process
     * @return null|JsonRpcResponse The response or null to continue processing
     */
    public function process(JsonRpcRequest $request): ?JsonRpcResponse;
}
