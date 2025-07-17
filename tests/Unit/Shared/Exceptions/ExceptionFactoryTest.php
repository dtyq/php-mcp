<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Shared\Exceptions;

use Dtyq\PhpMcp\Shared\Exceptions\AuthenticationError;
use Dtyq\PhpMcp\Shared\Exceptions\ErrorCodes;
use Dtyq\PhpMcp\Shared\Exceptions\ProtocolError;
use Dtyq\PhpMcp\Shared\Exceptions\TransportError;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Exception;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * Unit tests for exception factory methods.
 * @internal
 */
class ExceptionFactoryTest extends TestCase
{
    public function testProtocolErrorFactoryMethods(): void
    {
        $error = ProtocolError::invalidMethod('test.method');
        $this->assertInstanceOf(ProtocolError::class, $error);
        $this->assertStringContainsString('test.method', $error->getMessage());
        $this->assertSame(ErrorCodes::PROTOCOL_ERROR, $error->getErrorCode());

        $error = ProtocolError::invalidParams('test.method', 'missing parameter');
        $this->assertInstanceOf(ProtocolError::class, $error);
        $this->assertStringContainsString('test.method', $error->getMessage());
        $this->assertStringContainsString('missing parameter', $error->getMessage());

        $error = ProtocolError::versionMismatch('1.0', '2.0');
        $this->assertInstanceOf(ProtocolError::class, $error);
        $this->assertStringContainsString('1.0', $error->getMessage());
        $this->assertStringContainsString('2.0', $error->getMessage());
    }

    public function testTransportErrorFactoryMethods(): void
    {
        $error = TransportError::connectionTimeout('HTTP', 30);
        $this->assertInstanceOf(TransportError::class, $error);
        $this->assertStringContainsString('HTTP', $error->getMessage());
        $this->assertStringContainsString('30', $error->getMessage());
        $this->assertSame(ErrorCodes::TRANSPORT_ERROR, $error->getErrorCode());

        $error = TransportError::connectionRefused('HTTP', 'https://localhost:8080');
        $this->assertInstanceOf(TransportError::class, $error);
        $this->assertStringContainsString('HTTP', $error->getMessage());
        $this->assertStringContainsString('https://localhost:8080', $error->getMessage());

        $error = TransportError::httpError(404, 'Not Found');
        $this->assertInstanceOf(TransportError::class, $error);
        $this->assertStringContainsString('404', $error->getMessage());
        $this->assertStringContainsString('Not Found', $error->getMessage());
    }

    public function testAuthenticationErrorFactoryMethods(): void
    {
        $error = AuthenticationError::invalidCredentials('Invalid username or password');
        $this->assertInstanceOf(AuthenticationError::class, $error);
        $this->assertStringContainsString('Invalid username or password', $error->getMessage());
        $this->assertSame(ErrorCodes::AUTHENTICATION_ERROR, $error->getErrorCode());

        $error = AuthenticationError::missingCredentials('OAuth token');
        $this->assertInstanceOf(AuthenticationError::class, $error);
        $this->assertStringContainsString('OAuth token', $error->getMessage());

        $error = AuthenticationError::invalidScope('read:sensitive', ['read:basic', 'write:basic']);
        $this->assertInstanceOf(AuthenticationError::class, $error);
        $this->assertStringContainsString('read:sensitive', $error->getMessage());
        $this->assertStringContainsString('read:basic', $error->getMessage());

        $error = AuthenticationError::accessDenied('Insufficient permissions');
        $this->assertInstanceOf(AuthenticationError::class, $error);
        $this->assertStringContainsString('Insufficient permissions', $error->getMessage());
    }

    public function testValidationErrorFactoryMethods(): void
    {
        $error = ValidationError::requiredFieldMissing('username', 'login request');
        $this->assertInstanceOf(ValidationError::class, $error);
        $this->assertStringContainsString('username', $error->getMessage());
        $this->assertStringContainsString('login request', $error->getMessage());
        $this->assertSame(ErrorCodes::VALIDATION_ERROR, $error->getErrorCode());

        $error = ValidationError::invalidFieldType('age', 'integer', 'string');
        $this->assertInstanceOf(ValidationError::class, $error);
        $this->assertStringContainsString('age', $error->getMessage());
        $this->assertStringContainsString('integer', $error->getMessage());
        $this->assertStringContainsString('string', $error->getMessage());
    }

    public function testExceptionHierarchy(): void
    {
        $protocolError = ProtocolError::invalidMethod('test');
        $this->assertInstanceOf(Exception::class, $protocolError);
        $this->assertInstanceOf(Throwable::class, $protocolError);

        $transportError = TransportError::connectionTimeout('HTTP', 30);
        $this->assertInstanceOf(Exception::class, $transportError);
        $this->assertInstanceOf(Throwable::class, $transportError);

        $authError = AuthenticationError::invalidCredentials();
        $this->assertInstanceOf(Exception::class, $authError);
        $this->assertInstanceOf(Throwable::class, $authError);

        $validationError = ValidationError::requiredFieldMissing('field');
        $this->assertInstanceOf(Exception::class, $validationError);
        $this->assertInstanceOf(Throwable::class, $validationError);
    }

    public function testFactoryMethodsWithData(): void
    {
        $additionalData = ['context' => 'test', 'timestamp' => time()];

        $error = ProtocolError::invalidMethod('test.method', $additionalData);
        $this->assertSame($additionalData, $error->getErrorData());

        $error = TransportError::connectionTimeout('HTTP', 30, $additionalData);
        $this->assertSame($additionalData, $error->getErrorData());

        $error = AuthenticationError::invalidCredentials('Invalid', $additionalData);
        $this->assertSame($additionalData, $error->getErrorData());

        $error = ValidationError::requiredFieldMissing('field', 'context', $additionalData);
        $this->assertSame($additionalData, $error->getErrorData());
    }

    public function testExceptionMessagesAreInformative(): void
    {
        $protocolError = ProtocolError::invalidMethod('nonexistent.method');
        $this->assertStringContainsString('nonexistent.method', $protocolError->getMessage());
        $this->assertStringContainsString('Invalid', $protocolError->getMessage());

        $transportError = TransportError::connectionTimeout('HTTPS', 30);
        $this->assertStringContainsString('HTTPS', $transportError->getMessage());
        $this->assertStringContainsString('30', $transportError->getMessage());
        $this->assertStringContainsString('timeout', $transportError->getMessage());

        $authError = AuthenticationError::tokenExpired('access token');
        $this->assertStringContainsString('access token', $authError->getMessage());
        $this->assertStringContainsString('expired', $authError->getMessage());

        $validationError = ValidationError::invalidFieldType('email', 'string', 'integer');
        $this->assertStringContainsString('email', $validationError->getMessage());
        $this->assertStringContainsString('string', $validationError->getMessage());
        $this->assertStringContainsString('integer', $validationError->getMessage());
    }
}
