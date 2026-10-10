<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

return [
    'ssrf' => [
        // 默认关闭以兼容历史连接；开启后 HTTP 与 SSE 均禁止重定向。
        'enabled' => false,
        // strict: HTTPS/443；standard: HTTP(S)/80、443；compatible: HTTP(S)/任意端口。
        'level' => 'strict',
        'ip_protection' => [
            'block_private_ip' => true,
            'block_loopback_ip' => true,
            'block_link_local_ip' => true,
            'block_reserved_ip' => true,
            'block_cloud_metadata_ip' => true,
            'extra_blocked_ips' => [],
            'extra_blocked_cidrs' => [],
        ],
    ],
];
