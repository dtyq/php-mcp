<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Shared\Exceptions;

use Dtyq\PhpMcp\Shared\Exceptions\AuthenticationError;
use Dtyq\PhpMcp\Shared\Exceptions\ErrorCodes;
use PHPUnit\Framework\TestCase;

/**
 * Test AuthenticationError exception class.
 * @internal
 */
class AuthenticationErrorTest extends TestCase
{
    public function testInvalidCredentials(): void
    {
        $error = AuthenticationError::invalidCredentials('Invalid username or password');

        $this->assertInstanceOf(AuthenticationError::class, $error);
        $this->assertSame(ErrorCodes::AUTHENTICATION_ERROR, $error->getErrorCode());
        $this->assertSame('Invalid username or password', $error->getMessage());
    }

    public function testExpiredCredentials(): void
    {
        $error = AuthenticationError::expiredCredentials('access token');

        $this->assertInstanceOf(AuthenticationError::class, $error);
        $this->assertSame(ErrorCodes::AUTHENTICATION_ERROR, $error->getErrorCode());
        $this->assertStringContainsString('access token credentials have expired', $error->getMessage());
    }

    public function testInsufficientPermissions(): void
    {
        $error = AuthenticationError::insufficientPermissions('delete_resource');

        $this->assertInstanceOf(AuthenticationError::class, $error);
        $this->assertSame(ErrorCodes::AUTHENTICATION_ERROR, $error->getErrorCode());
        $this->assertStringContainsString('Insufficient permissions for operation \'delete_resource\'', $error->getMessage());
    }

    public function testInvalidScope(): void
    {
        $error = AuthenticationError::invalidScope('read:admin', ['read:user', 'write:user']);

        $this->assertInstanceOf(AuthenticationError::class, $error);
        $this->assertSame(ErrorCodes::AUTHENTICATION_ERROR, $error->getErrorCode());
        $this->assertStringContainsString('Invalid OAuth scope: read:admin', $error->getMessage());
    }

    public function testInvalidRedirectUri(): void
    {
        $error = AuthenticationError::invalidRedirectUri('https://malicious.com/callback');

        $this->assertInstanceOf(AuthenticationError::class, $error);
        $this->assertSame(ErrorCodes::AUTHENTICATION_ERROR, $error->getErrorCode());
        $this->assertStringContainsString('Invalid OAuth redirect URI: https://malicious.com/callback', $error->getMessage());
    }

    public function testInvalidClient(): void
    {
        $error = AuthenticationError::invalidClient('client123');

        $this->assertInstanceOf(AuthenticationError::class, $error);
        $this->assertSame(ErrorCodes::AUTHENTICATION_ERROR, $error->getErrorCode());
        $this->assertStringContainsString('Invalid OAuth client: client123', $error->getMessage());
    }

    public function testInvalidGrant(): void
    {
        $error = AuthenticationError::invalidGrant('authorization_code');

        $this->assertInstanceOf(AuthenticationError::class, $error);
        $this->assertSame(ErrorCodes::AUTHENTICATION_ERROR, $error->getErrorCode());
        $this->assertStringContainsString('Invalid OAuth grant type: authorization_code', $error->getMessage());
    }

    public function testUnauthorizedClient(): void
    {
        $error = AuthenticationError::unauthorizedClient('client456', 'read_resources');

        $this->assertInstanceOf(AuthenticationError::class, $error);
        $this->assertSame(ErrorCodes::AUTHENTICATION_ERROR, $error->getErrorCode());
        $this->assertStringContainsString('Unauthorized OAuth client \'client456\' for operation \'read_resources\'', $error->getMessage());
    }

    public function testUnsupportedGrantType(): void
    {
        $error = AuthenticationError::unsupportedGrantType('implicit');

        $this->assertInstanceOf(AuthenticationError::class, $error);
        $this->assertSame(ErrorCodes::AUTHENTICATION_ERROR, $error->getErrorCode());
        $this->assertStringContainsString('Unsupported OAuth grant type: implicit', $error->getMessage());
    }

    public function testInvalidRequest(): void
    {
        $error = AuthenticationError::invalidRequest('Missing required parameter: client_id');

        $this->assertInstanceOf(AuthenticationError::class, $error);
        $this->assertSame(ErrorCodes::AUTHENTICATION_ERROR, $error->getErrorCode());
        $this->assertStringContainsString('Invalid OAuth request: Missing required parameter: client_id', $error->getMessage());
    }

    public function testAccessDenied(): void
    {
        $error = AuthenticationError::accessDenied('User denied authorization');

        $this->assertInstanceOf(AuthenticationError::class, $error);
        $this->assertSame(ErrorCodes::AUTHENTICATION_ERROR, $error->getErrorCode());
        $this->assertStringContainsString('OAuth access denied: User denied authorization', $error->getMessage());
    }

    public function testUnsupportedResponseType(): void
    {
        $error = AuthenticationError::unsupportedResponseType('token');

        $this->assertInstanceOf(AuthenticationError::class, $error);
        $this->assertSame(ErrorCodes::AUTHENTICATION_ERROR, $error->getErrorCode());
        $this->assertStringContainsString('Unsupported OAuth response type: token', $error->getMessage());
    }

    public function testServerError(): void
    {
        $error = AuthenticationError::serverError('Database connection failed');

        $this->assertInstanceOf(AuthenticationError::class, $error);
        $this->assertSame(ErrorCodes::AUTHENTICATION_ERROR, $error->getErrorCode());
        $this->assertStringContainsString('OAuth server error: Database connection failed', $error->getMessage());
    }

    public function testTemporarilyUnavailable(): void
    {
        $error = AuthenticationError::temporarilyUnavailable('Service maintenance in progress');

        $this->assertInstanceOf(AuthenticationError::class, $error);
        $this->assertSame(ErrorCodes::AUTHENTICATION_ERROR, $error->getErrorCode());
        $this->assertStringContainsString('OAuth service temporarily unavailable: Service maintenance in progress', $error->getMessage());
    }

    public function testConstructorWithData(): void
    {
        $data = ['client_id' => 'test123', 'scope' => 'read'];
        $error = new AuthenticationError('Test auth error', $data);

        $this->assertInstanceOf(AuthenticationError::class, $error);
        $this->assertSame(ErrorCodes::AUTHENTICATION_ERROR, $error->getErrorCode());
        $this->assertSame('Test auth error', $error->getMessage());

        $errorData = $error->getErrorData();
        $this->assertSame($data, $errorData);
    }

    public function testFactoryMethodsWithData(): void
    {
        $data = ['custom' => 'data'];

        $error1 = AuthenticationError::invalidCredentials('test', $data);
        $this->assertSame($data, $error1->getErrorData());

        $error2 = AuthenticationError::expiredCredentials('token', $data);
        $this->assertSame($data, $error2->getErrorData());

        $error3 = AuthenticationError::insufficientPermissions('action', [], $data);
        $this->assertSame($data, $error3->getErrorData());
    }
}
