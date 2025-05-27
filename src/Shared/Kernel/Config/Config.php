<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Kernel\Config;

use Adbar\Dot;

class Config extends Dot
{
    public function __construct(array $items = [])
    {
        $items['sdk_name'] = $this->getSdkName();
        parent::__construct($items);
    }

    public function getSdkName(): string
    {
        return $this->get('sdk_name', 'php-mcp');
    }
}
