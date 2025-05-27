<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Server\Core;

use Dtyq\PhpMcp\Server\Core\ErrorHandler;
use Dtyq\PhpMcp\Shared\Exceptions\ErrorCodes;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Core\JsonRpcError;
use Dtyq\PhpMcp\Types\Core\JsonRpcRequest;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Test cases for ErrorHandler.
 * @internal
 */
class ErrorHandlerTest extends TestCase
{
    private ErrorHandler $errorHandler;

    /** @var LoggerInterface&MockObject */
    private MockObject $mockLogger;

    protected function setUp(): void
    {
        $this->mockLogger = $this->createMock(LoggerInterface::class);
        $this->errorHandler = new ErrorHandler($this->mockLogger);
    }

    public function testConstructorSetsDefaults(): void
    {
        $stats = $this->errorHandler->getErrorStats();

        $this->assertSame(0, $stats['request_errors']);
        $this->assertSame(0, $stats['transport_errors']);
        $this->assertSame(0, $stats['system_errors']);
        $this->assertSame(3, $stats['max_recovery_attempts']);
        $this->assertTrue($stats['error_reporting_enabled']);
    }

    public function testHandleRequestErrorReturnsJsonRpcError(): void
    {
        $error = new RuntimeException('Test error');
        /** @var JsonRpcRequest&MockObject $request */
        $request = $this->createMock(JsonRpcRequest::class);
        $request->method('getId')->willReturn('test-id');
        $request->method('getMethod')->willReturn('test/method');

        $result = $this->errorHandler->handleRequestError($error, $request);

        $this->assertInstanceOf(JsonRpcError::class, $result);
        $this->assertSame('test-id', $result->getId());
        $this->assertSame(ErrorCodes::INTERNAL_ERROR, $result->getCode());
        $this->assertSame('Test error', $result->getMessage());

        $stats = $this->errorHandler->getErrorStats();
        $this->assertSame(1, $stats['request_errors']);
    }

    public function testHandleRequestErrorWithoutRequest(): void
    {
        $error = new ValidationError('Validation failed');

        $result = $this->errorHandler->handleRequestError($error);

        $this->assertInstanceOf(JsonRpcError::class, $result);
        $this->assertSame('unknown', $result->getId());
        $this->assertSame(ErrorCodes::VALIDATION_ERROR, $result->getCode());
    }

    public function testHandleTransportError(): void
    {
        $error = new RuntimeException('Transport error');
        $context = ['connection' => 'lost'];

        $this->errorHandler->handleTransportError($error, $context);

        $stats = $this->errorHandler->getErrorStats();
        $this->assertSame(1, $stats['transport_errors']);
        $this->assertArrayHasKey(RuntimeException::class, $stats['error_types']);
    }

    public function testHandleSystemError(): void
    {
        $error = new RuntimeException('System error');
        $context = ['component' => 'database'];

        $this->errorHandler->handleSystemError($error, $context);

        $stats = $this->errorHandler->getErrorStats();
        $this->assertSame(1, $stats['system_errors']);
    }

    public function testRegisterRecoveryStrategy(): void
    {
        $strategyCalled = false;
        $strategy = function () use (&$strategyCalled) {
            $strategyCalled = true;
            return true;
        };

        $this->errorHandler->registerRecoveryStrategy(RuntimeException::class, $strategy);

        $error = new RuntimeException('Test error');
        $result = $this->errorHandler->attemptRecovery($error);

        $this->assertTrue($result);
        $this->assertTrue($strategyCalled);
    }

    public function testRecoveryStrategyPriority(): void
    {
        $executionOrder = [];

        $strategy1 = function () use (&$executionOrder) {
            $executionOrder[] = 'strategy1';
            return false;
        };

        $strategy2 = function () use (&$executionOrder) {
            $executionOrder[] = 'strategy2';
            return false;
        };

        $strategy3 = function () use (&$executionOrder) {
            $executionOrder[] = 'strategy3';
            return true;
        };

        // Register with different priorities
        $this->errorHandler->registerRecoveryStrategy(RuntimeException::class, $strategy1, 10);
        $this->errorHandler->registerRecoveryStrategy(RuntimeException::class, $strategy2, 20);
        $this->errorHandler->registerRecoveryStrategy(RuntimeException::class, $strategy3, 15);

        $error = new RuntimeException('Test error');
        $result = $this->errorHandler->attemptRecovery($error);

        $this->assertTrue($result);
        // Higher priority should execute first
        $this->assertSame(['strategy2', 'strategy3'], $executionOrder);
    }

    public function testMaxRecoveryAttempts(): void
    {
        $this->errorHandler->setMaxRecoveryAttempts(2);

        $error = new RuntimeException('Test error');

        // First two attempts should work
        $this->assertFalse($this->errorHandler->attemptRecovery($error));
        $this->assertFalse($this->errorHandler->attemptRecovery($error));

        // Third attempt should be blocked
        $this->assertFalse($this->errorHandler->attemptRecovery($error));

        $stats = $this->errorHandler->getErrorStats();
        $this->assertSame(2, $stats['recovery_attempts']);
        $this->assertSame(2, $stats['failed_recoveries']);
    }

    public function testSetMaxRecoveryAttemptsValidation(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid value for field \'maxAttempts\'');

        $this->errorHandler->setMaxRecoveryAttempts(-1);
    }

    public function testErrorReporting(): void
    {
        // Initially enabled
        $this->assertTrue($this->errorHandler->getErrorStats()['error_reporting_enabled']);

        // Disable reporting
        $this->errorHandler->setErrorReporting(false);
        $this->assertFalse($this->errorHandler->getErrorStats()['error_reporting_enabled']);

        // Re-enable reporting
        $this->errorHandler->setErrorReporting(true);
        $this->assertTrue($this->errorHandler->getErrorStats()['error_reporting_enabled']);
    }

    public function testConvertToErrorDataWithMcpError(): void
    {
        $originalError = ValidationError::requiredFieldMissing('test_field');
        $errorData = $this->errorHandler->convertToErrorData($originalError);

        $this->assertSame(ErrorCodes::VALIDATION_ERROR, $errorData->getCode());
        $this->assertStringContainsString('test_field', $errorData->getMessage());
    }

    public function testConvertToErrorDataWithGenericException(): void
    {
        $error = new RuntimeException('Generic error');
        $errorData = $this->errorHandler->convertToErrorData($error);

        $this->assertSame(ErrorCodes::INTERNAL_ERROR, $errorData->getCode());
        $this->assertSame('Generic error', $errorData->getMessage());
        $this->assertArrayHasKey('type', $errorData->getData());
        $this->assertSame(RuntimeException::class, $errorData->getData()['type']);
    }

    public function testErrorStatsTracking(): void
    {
        $error1 = new RuntimeException('Error 1');
        $error2 = new ValidationError('Error 2');

        // Create mock request with proper ID
        /** @var JsonRpcRequest&MockObject $mockRequest */
        $mockRequest = $this->createMock(JsonRpcRequest::class);
        $mockRequest->method('getId')->willReturn('test-id');

        $this->errorHandler->handleRequestError($error1, $mockRequest);
        $this->errorHandler->handleTransportError($error2);
        $this->errorHandler->handleSystemError($error1);

        $stats = $this->errorHandler->getErrorStats();

        $this->assertSame(1, $stats['request_errors']);
        $this->assertSame(1, $stats['transport_errors']);
        $this->assertSame(1, $stats['system_errors']);
        $this->assertNotNull($stats['last_error_time']);
        $this->assertArrayHasKey(RuntimeException::class, $stats['error_types']);
        $this->assertArrayHasKey(ValidationError::class, $stats['error_types']);
        $this->assertSame(2, $stats['error_types'][RuntimeException::class]);
        $this->assertSame(1, $stats['error_types'][ValidationError::class]);
    }

    public function testRecoverySuccessRate(): void
    {
        // Register a strategy that succeeds 50% of the time
        $callCount = 0;
        $strategy = function () use (&$callCount) {
            ++$callCount;
            return $callCount % 2 === 0; // Succeed on even calls
        };

        $this->errorHandler->registerRecoveryStrategy(RuntimeException::class, $strategy);

        $error = new RuntimeException('Test error');

        // Make 4 recovery attempts
        $this->errorHandler->attemptRecovery($error);
        $this->errorHandler->attemptRecovery($error);
        $this->errorHandler->attemptRecovery($error);
        $this->errorHandler->attemptRecovery($error);

        $stats = $this->errorHandler->getErrorStats();
        $this->assertSame(0.5, $stats['recovery_success_rate']);
        $this->assertSame(2, $stats['successful_recoveries']);
        $this->assertSame(2, $stats['failed_recoveries']);
    }

    public function testGenericRecoveryStrategy(): void
    {
        $strategyCalled = false;
        $strategy = function () use (&$strategyCalled) {
            $strategyCalled = true;
            return true;
        };

        // Register for all error types
        $this->errorHandler->registerRecoveryStrategy('*', $strategy);

        // Should work for any error type
        $error = new LogicException('Logic error');
        $result = $this->errorHandler->attemptRecovery($error);

        $this->assertTrue($result);
        $this->assertTrue($strategyCalled);
    }
}
