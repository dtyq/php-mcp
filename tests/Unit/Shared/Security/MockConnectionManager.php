<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Shared\Security;

use Dtyq\PhpMcp\Client\Transport\Http\HttpConnectionManager;

/** 用合成响应替代底层 HTTP 请求。 */
class MockConnectionManager extends HttpConnectionManager
{
    /** @var array<int, mixed> */
    public array $options = [];

    public int $calls = 0;

    public int $status = 200;

    /**
     * 记录实际请求选项并返回 Mock 响应。
     *
     * @param array<int, mixed> $curlOptions
     * @return array{string, int, array<string, mixed>}
     */
    protected function performRequest(array $curlOptions): array
    {
        ++$this->calls;
        $this->options = $curlOptions;
        return ['{}', $this->status, []];
    }
}
