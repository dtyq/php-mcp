<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Framework\Hyperf;

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
            ],
        ];
    }
}
