<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Framework\Hyperf\Collector\Annotations;

use Hyperf\Di\Annotation\AbstractAnnotation;

abstract class McpAnnotation extends AbstractAnnotation
{
    protected string $class;

    protected string $method;

    public function collectMethod(string $className, ?string $target): void
    {
        $this->class = $className;
        $this->method = $target;
        parent::collectMethod($className, $target);
    }
}
