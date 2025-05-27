<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Exceptions;

class SystemException extends McpError
{
    public function __construct(string $message = 'System Error', int $code = 500)
    {
        parent::__construct(new ErrorData($code, $message, null));
    }
}
