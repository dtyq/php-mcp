<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Types\Auth;

use Dtyq\PhpMcp\Types\Auth\AuthInfo;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class AuthInfoTest extends TestCase
{
    public function testConstructorWithBasicData(): void
    {
        $authInfo = new AuthInfo('user123', ['read', 'write'], ['role' => 'admin'], 1234567890);

        $this->assertSame('user123', $authInfo->getSubject());
        $this->assertSame(['read', 'write'], $authInfo->getScopes());
        $this->assertSame(['role' => 'admin'], $authInfo->getMetadataAll());
        $this->assertSame(1234567890, $authInfo->getExpiresAt());
    }

    public function testConstructorWithDefaults(): void
    {
        $authInfo = new AuthInfo('user123');

        $this->assertSame('user123', $authInfo->getSubject());
        $this->assertSame([], $authInfo->getScopes());
        $this->assertSame([], $authInfo->getMetadataAll());
        $this->assertNull($authInfo->getExpiresAt());
    }

    public function testHasScopeWithSpecificScope(): void
    {
        $authInfo = new AuthInfo('user123', ['read', 'write']);

        $this->assertTrue($authInfo->hasScope('read'));
        $this->assertTrue($authInfo->hasScope('write'));
        $this->assertFalse($authInfo->hasScope('delete'));
    }

    public function testHasScopeWithWildcardScope(): void
    {
        $authInfo = new AuthInfo('user123', ['*']);

        $this->assertTrue($authInfo->hasScope('read'));
        $this->assertTrue($authInfo->hasScope('write'));
        $this->assertTrue($authInfo->hasScope('delete'));
        $this->assertTrue($authInfo->hasScope('any-scope'));
    }

    public function testHasAllScopesWithSpecificScopes(): void
    {
        $authInfo = new AuthInfo('user123', ['read', 'write', 'delete']);

        $this->assertTrue($authInfo->hasAllScopes(['read']));
        $this->assertTrue($authInfo->hasAllScopes(['read', 'write']));
        $this->assertTrue($authInfo->hasAllScopes(['read', 'write', 'delete']));
        $this->assertFalse($authInfo->hasAllScopes(['read', 'admin']));
        $this->assertFalse($authInfo->hasAllScopes(['admin']));
    }

    public function testHasAllScopesWithWildcardScope(): void
    {
        $authInfo = new AuthInfo('user123', ['*']);

        $this->assertTrue($authInfo->hasAllScopes(['read']));
        $this->assertTrue($authInfo->hasAllScopes(['read', 'write', 'admin']));
        $this->assertTrue($authInfo->hasAllScopes(['any', 'scope', 'at', 'all']));
    }

    public function testHasAllScopesWithEmptyArray(): void
    {
        $authInfo = new AuthInfo('user123', ['read']);

        $this->assertTrue($authInfo->hasAllScopes([]));
    }

    public function testHasAnyScopeWithSpecificScopes(): void
    {
        $authInfo = new AuthInfo('user123', ['read', 'write']);

        $this->assertTrue($authInfo->hasAnyScope(['read']));
        $this->assertTrue($authInfo->hasAnyScope(['read', 'admin']));
        $this->assertTrue($authInfo->hasAnyScope(['admin', 'write']));
        $this->assertFalse($authInfo->hasAnyScope(['admin', 'delete']));
    }

    public function testHasAnyScopeWithWildcardScope(): void
    {
        $authInfo = new AuthInfo('user123', ['*']);

        $this->assertTrue($authInfo->hasAnyScope(['read']));
        $this->assertTrue($authInfo->hasAnyScope(['admin', 'delete']));
        $this->assertTrue($authInfo->hasAnyScope(['any', 'scope']));
    }

    public function testHasAnyScopeWithEmptyArray(): void
    {
        $authInfo = new AuthInfo('user123', ['read']);

        $this->assertFalse($authInfo->hasAnyScope([]));
    }

    public function testIsExpiredWithNullExpiresAt(): void
    {
        $authInfo = new AuthInfo('user123', [], [], null);

        $this->assertFalse($authInfo->isExpired());
    }

    public function testIsExpiredWithFutureTimestamp(): void
    {
        $futureTimestamp = time() + 3600; // 1 hour from now
        $authInfo = new AuthInfo('user123', [], [], $futureTimestamp);

        $this->assertFalse($authInfo->isExpired());
    }

    public function testIsExpiredWithPastTimestamp(): void
    {
        $pastTimestamp = time() - 3600; // 1 hour ago
        $authInfo = new AuthInfo('user123', [], [], $pastTimestamp);

        $this->assertTrue($authInfo->isExpired());
    }

    public function testGetMetadataWithExistingKey(): void
    {
        $authInfo = new AuthInfo('user123', [], ['role' => 'admin', 'department' => 'IT']);

        $this->assertSame('admin', $authInfo->getMetadata('role'));
        $this->assertSame('IT', $authInfo->getMetadata('department'));
    }

    public function testGetMetadataWithNonExistentKey(): void
    {
        $authInfo = new AuthInfo('user123', [], ['role' => 'admin']);

        $this->assertNull($authInfo->getMetadata('nonexistent'));
        $this->assertSame('default', $authInfo->getMetadata('nonexistent', 'default'));
    }

    public function testCreateStaticMethod(): void
    {
        $authInfo = AuthInfo::create('user123', ['read'], ['type' => 'api'], 1234567890);

        $this->assertSame('user123', $authInfo->getSubject());
        $this->assertSame(['read'], $authInfo->getScopes());
        $this->assertSame(['type' => 'api'], $authInfo->getMetadataAll());
        $this->assertSame(1234567890, $authInfo->getExpiresAt());
    }

    public function testCreateStaticMethodWithDefaults(): void
    {
        $authInfo = AuthInfo::create('user123');

        $this->assertSame('user123', $authInfo->getSubject());
        $this->assertSame([], $authInfo->getScopes());
        $this->assertSame([], $authInfo->getMetadataAll());
        $this->assertNull($authInfo->getExpiresAt());
    }

    public function testAnonymousStaticMethod(): void
    {
        $authInfo = AuthInfo::anonymous();

        $this->assertSame('anonymous', $authInfo->getSubject());
        $this->assertSame(['*'], $authInfo->getScopes());
        $this->assertSame(['type' => 'anonymous'], $authInfo->getMetadataAll());
        $this->assertNull($authInfo->getExpiresAt());
    }

    public function testAnonymousHasUniversalAccess(): void
    {
        $authInfo = AuthInfo::anonymous();

        $this->assertTrue($authInfo->hasScope('any-scope'));
        $this->assertTrue($authInfo->hasAllScopes(['read', 'write', 'admin']));
        $this->assertTrue($authInfo->hasAnyScope(['read']));
    }
}
