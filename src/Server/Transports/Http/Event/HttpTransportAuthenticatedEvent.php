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
    private AuthInfo $authInfo;

    private TransportMetadata $transportMetadata;

    public function __construct(
        AuthInfo $authInfo,
        TransportMetadata $transportMetadata
    ) {
        $this->authInfo = $authInfo;
        $this->transportMetadata = $transportMetadata;
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
