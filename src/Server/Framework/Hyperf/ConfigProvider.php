<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Framework\Hyperf;

use Dtyq\PhpMcp\Server\Transports\Http\SessionManagerInterface;
use Dtyq\PhpMcp\Shared\Auth\AuthenticatorInterface;
use Dtyq\PhpMcp\Shared\Auth\NullAuthenticator;
use Dtyq\PhpMcp\Shared\Kernel\Packer\OpisClosurePacker;
use Dtyq\PhpMcp\Shared\Kernel\Packer\PackerInterface;

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
                PackerInterface::class => OpisClosurePacker::class,
                AuthenticatorInterface::class => NullAuthenticator::class,
                SessionManagerInterface::class => RedisSessionManager::class,
            ],
        ];
    }
}
