<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Shared\Kernel;

use Dtyq\PhpMcp\Shared\Auth\AuthenticatorInterface;
use Dtyq\PhpMcp\Shared\Auth\NullAuthenticator;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Dtyq\PhpMcp\Types\Auth\AuthInfo;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @internal
 */
class ApplicationAuthTest extends TestCase
{
    private Application $application;

    protected function setUp(): void
    {
        /** @var ContainerInterface $container */
        $container = $this->createMock(ContainerInterface::class);
        $this->application = new Application($container, []);
    }

    public function testWithAuthenticatorSetsCustomAuthenticator(): void
    {
        $customAuthenticator = $this->createCustomAuthenticator();

        $result = $this->application->withAuthenticator($customAuthenticator);

        // Should return the same instance (fluent interface)
        $this->assertSame($this->application, $result);
        $this->assertInstanceOf(Application::class, $result);
    }

    public function testWithAuthenticatorReturnsSameInstanceWhenCalledMultipleTimes(): void
    {
        $authenticator1 = $this->createCustomAuthenticator();
        $authenticator2 = $this->createAnotherCustomAuthenticator();

        $app1 = $this->application->withAuthenticator($authenticator1);
        $app2 = $app1->withAuthenticator($authenticator2);

        $this->assertSame($this->application, $app1);
        $this->assertSame($app1, $app2);
        $this->assertInstanceOf(Application::class, $app1);
        $this->assertInstanceOf(Application::class, $app2);
    }

    public function testWithNullAuthenticator(): void
    {
        $nullAuthenticator = new NullAuthenticator();

        $result = $this->application->withAuthenticator($nullAuthenticator);

        $this->assertInstanceOf(Application::class, $result);
        $this->assertSame($this->application, $result);
    }

    public function testApplicationFluentInterface(): void
    {
        $authenticator = $this->createCustomAuthenticator();
        $originalApp = $this->application;

        $result = $this->application->withAuthenticator($authenticator);

        // Should return the same instance for fluent interface
        $this->assertSame($originalApp, $result);

        // Multiple calls should return the same instance
        $anotherResult = $result->withAuthenticator(new NullAuthenticator());
        $this->assertSame($result, $anotherResult);
        $this->assertSame($originalApp, $anotherResult);
    }

    public function testGetAuthenticatorReturnsSetAuthenticator(): void
    {
        $customAuthenticator = $this->createCustomAuthenticator();

        $this->application->withAuthenticator($customAuthenticator);
        $retrievedAuthenticator = $this->application->getAuthenticator();

        $this->assertSame($customAuthenticator, $retrievedAuthenticator);
    }

    public function testGetAuthenticatorReturnsNullAuthenticatorByDefault(): void
    {
        $authenticator = $this->application->getAuthenticator();

        $this->assertInstanceOf(NullAuthenticator::class, $authenticator);
    }

    private function createCustomAuthenticator(): AuthenticatorInterface
    {
        return new class implements AuthenticatorInterface {
            public function authenticate(string $server, string $version): AuthInfo
            {
                return AuthInfo::create('custom-user', ['read', 'write'], ['type' => 'custom']);
            }
        };
    }

    private function createAnotherCustomAuthenticator(): AuthenticatorInterface
    {
        return new class implements AuthenticatorInterface {
            public function authenticate(string $server, string $version): AuthInfo
            {
                return AuthInfo::create('another-user', ['admin'], ['type' => 'another']);
            }
        };
    }
}
