<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Framework\Hyperf;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'publish' => [
            ],
            'dependencies' => [
            ],
        ];
    }
}
