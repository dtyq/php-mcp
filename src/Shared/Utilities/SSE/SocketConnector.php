<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Utilities\SSE;

/** 隔离 SSE 底层连接，使安全策略可以使用 Mock 验证实际连接参数。 */
class SocketConnector
{
    /**
     * 使用已确定的地址与 TLS 上下文建立连接。
     *
     * @param array<string, array<string, mixed>> $options
     * @return false|resource
     */
    public function connect(string $address, int $timeout, array $options, ?int &$errno, ?string &$errstr)
    {
        return stream_socket_client($address, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, stream_context_create($options));
    }
}
