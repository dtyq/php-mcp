<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Auth;

use Dtyq\PhpMcp\Shared\Exceptions\AuthenticationError;
use Dtyq\PhpMcp\Types\Auth\AuthInfo;

/**
 * Authenticator Interface.
 *
 * Shared interface for authentication providers.
 * Implementations handle the complete authentication flow including
 * extracting credentials from requests and validating them.
 */
interface AuthenticatorInterface
{
    /**
     * Perform authentication and return authentication information.
     *
     * The implementation should:
     * - Extract authentication credentials from the current context
     * - Validate the credentials
     * - Return AuthInfo if authentication succeeds
     * - Throw AuthenticationError if no credentials found or authentication fails
     *
     * @return AuthInfo Authentication information
     * @throws AuthenticationError If no credentials found or authentication fails
     */
    public function authenticate(string $server, string $version): AuthInfo;
}
