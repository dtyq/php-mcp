<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Exceptions;

/** SSRF 拦截异常，禁止作为普通网络失败重试或回退。 */
class SsrfException extends TransportError
{
    /** 使用不包含 URL、请求头或 DNS 结果的原因生成异常。 */
    public function __construct(string $reason)
    {
        parent::__construct('MCP request blocked by SSRF protection: ' . $reason);
    }
}
