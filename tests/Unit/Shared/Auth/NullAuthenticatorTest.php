<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Shared\Auth;

use Dtyq\PhpMcp\Shared\Auth\NullAuthenticator;
use Dtyq\PhpMcp\Types\Auth\AuthInfo;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class NullAuthenticatorTest extends TestCase
{
    private NullAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->authenticator = new NullAuthenticator();
    }

    public function testAuthenticateReturnsAnonymousAuthInfo(): void
    {
        $authInfo = $this->authenticator->authenticate();

        $this->assertInstanceOf(AuthInfo::class, $authInfo);
        $this->assertSame('anonymous', $authInfo->getSubject());
        $this->assertSame(['*'], $authInfo->getScopes());
        $this->assertSame(['type' => 'anonymous'], $authInfo->getMetadataAll());
        $this->assertNull($authInfo->getExpiresAt());
    }

    public function testAuthenticateReturnsUniversalAccess(): void
    {
        $authInfo = $this->authenticator->authenticate();

        // Test universal scope access
        $this->assertTrue($authInfo->hasScope('read'));
        $this->assertTrue($authInfo->hasScope('write'));
        $this->assertTrue($authInfo->hasScope('admin'));
        $this->assertTrue($authInfo->hasScope('any-custom-scope'));
    }

    public function testAuthenticateReturnsConsistentResults(): void
    {
        $authInfo1 = $this->authenticator->authenticate();
        $authInfo2 = $this->authenticator->authenticate();

        // Should return equivalent but not necessarily same instances
        $this->assertSame($authInfo1->getSubject(), $authInfo2->getSubject());
        $this->assertSame($authInfo1->getScopes(), $authInfo2->getScopes());
        $this->assertSame($authInfo1->getMetadataAll(), $authInfo2->getMetadataAll());
        $this->assertSame($authInfo1->getExpiresAt(), $authInfo2->getExpiresAt());
    }

    public function testAuthenticateNeverExpires(): void
    {
        $authInfo = $this->authenticator->authenticate();

        $this->assertFalse($authInfo->isExpired());
    }

    public function testAuthenticateSupportsAllScopeOperations(): void
    {
        $authInfo = $this->authenticator->authenticate();

        // Test hasAllScopes with various scope combinations
        $this->assertTrue($authInfo->hasAllScopes(['read']));
        $this->assertTrue($authInfo->hasAllScopes(['read', 'write']));
        $this->assertTrue($authInfo->hasAllScopes(['admin', 'delete', 'create']));
        $this->assertTrue($authInfo->hasAllScopes([])); // Empty array should always pass

        // Test hasAnyScope with various scope combinations
        $this->assertTrue($authInfo->hasAnyScope(['read']));
        $this->assertTrue($authInfo->hasAnyScope(['admin']));
        $this->assertTrue($authInfo->hasAnyScope(['nonexistent', 'admin']));
    }
}
