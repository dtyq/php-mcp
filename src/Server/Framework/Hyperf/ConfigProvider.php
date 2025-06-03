<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Framework\Hyperf;

use Dtyq\PhpMcp\Server\Transports\Http\SessionManagerInterface;

class ConfigProvider
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function __invoke(): array
    {
        return [
            'publish' => [
            ],
            'dependencies' => [
                SessionManagerInterface::class => RedisSessionManager::class,
            ],
        ];
    }
}
