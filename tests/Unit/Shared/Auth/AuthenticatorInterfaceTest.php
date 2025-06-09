<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Shared\Auth;

use Dtyq\PhpMcp\Shared\Auth\AuthenticatorInterface;
use Dtyq\PhpMcp\Shared\Exceptions\AuthenticationError;
use Dtyq\PhpMcp\Types\Auth\AuthInfo;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class AuthenticatorInterfaceTest extends TestCase
{
    public function testAuthenticatorInterfaceContract(): void
    {
        $authenticator = $this->createMockAuthenticator();

        // Test that authenticate method exists and returns AuthInfo
        $authInfo = $authenticator->authenticate('test-server', '2024-11-05');
        $this->assertInstanceOf(AuthInfo::class, $authInfo);
    }

    public function testAuthenticatorCanThrowAuthenticationError(): void
    {
        $authenticator = $this->createFailingAuthenticator();

        $this->expectException(AuthenticationError::class);
        $authenticator->authenticate('test-server', '2024-11-05');
    }

    private function createMockAuthenticator(): AuthenticatorInterface
    {
        return new class implements AuthenticatorInterface {
            public function authenticate(string $server, string $version): AuthInfo
            {
                return AuthInfo::create('test-user', ['read'], ['type' => 'test']);
            }
        };
    }

    private function createFailingAuthenticator(): AuthenticatorInterface
    {
        return new class implements AuthenticatorInterface {
            public function authenticate(string $server, string $version): AuthInfo
            {
                throw new AuthenticationError('Authentication failed for testing');
            }
        };
    }
}
