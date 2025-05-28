<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Auth;

use Dtyq\PhpMcp\Types\Auth\AuthInfo;

/**
 * Null Authenticator - Default implementation with no authentication.
 *
 * Used when no authenticator is configured - allows anyone to access
 */
final class NullAuthenticator implements AuthenticatorInterface
{
    public function authenticate(): AuthInfo
    {
        // Return "any" user - allows access to everything
        return AuthInfo::anonymous();
    }
}
