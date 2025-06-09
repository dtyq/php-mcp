<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Transports\Http\Event;

use Dtyq\PhpMcp\Server\Transports\Core\TransportMetadata;
use Dtyq\PhpMcp\Types\Auth\AuthInfo;

class HttpTransportAuthenticatedEvent
{
    private string $server;

    private string $version;

    private AuthInfo $authInfo;

    private TransportMetadata $transportMetadata;

    public function __construct(
        string $server,
        string $version,
        AuthInfo $authInfo,
        TransportMetadata $transportMetadata
    ) {
        $this->server = $server;
        $this->version = $version;
        $this->authInfo = $authInfo;
        $this->transportMetadata = $transportMetadata;
    }

    public function getServer(): string
    {
        return $this->server;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getAuthInfo(): AuthInfo
    {
        return $this->authInfo;
    }

    public function getTransportMetadata(): TransportMetadata
    {
        return $this->transportMetadata;
    }
}
