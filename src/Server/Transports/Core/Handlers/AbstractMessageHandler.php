<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Transports\Core\Handlers;

use Dtyq\PhpMcp\Shared\Kernel\Application;

abstract class AbstractMessageHandler implements MessageHandlerInterface
{
    protected Application $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }
}
