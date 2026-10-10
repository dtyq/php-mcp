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
            'publish' => [[
                'id' => 'php-mcp-config',
                'description' => 'MCP 配置',
                'source' => __DIR__ . '/publish/php-mcp.php',
                'destination' => BASE_PATH . '/config/autoload/php-mcp.php',
            ]],
            'dependencies' => [
                PackerInterface::class => OpisClosurePacker::class,
                AuthenticatorInterface::class => NullAuthenticator::class,
                SessionManagerInterface::class => RedisSessionManager::class,
            ],
        ];
    }
}
