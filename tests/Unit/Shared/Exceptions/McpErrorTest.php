<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Tests\Unit\Shared\Exceptions;

use Dtyq\PhpMcp\Shared\Exceptions\ErrorCodes;
use Dtyq\PhpMcp\Shared\Exceptions\ErrorData;
use Dtyq\PhpMcp\Shared\Exceptions\McpError;
use Exception;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * Unit tests for McpError class.
 * @internal
 */
class McpErrorTest extends TestCase
{
    public function testConstructorWithErrorData(): void
    {
        $errorData = new ErrorData(ErrorCodes::PROTOCOL_ERROR, 'Test error message', ['detail' => 'test']);
        $exception = new McpError($errorData);

        $this->assertSame('Test error message', $exception->getMessage());
        $this->assertSame($errorData, $exception->getError());
        $this->assertSame(ErrorCodes::PROTOCOL_ERROR, $exception->getErrorCode());
        $this->assertSame(['detail' => 'test'], $exception->getErrorData());
    }

    public function testConstructorWithoutData(): void
    {
        $errorData = new ErrorData(ErrorCodes::TRANSPORT_ERROR, 'Transport failed');
        $exception = new McpError($errorData);

        $this->assertSame('Transport failed', $exception->getMessage());
        $this->assertSame($errorData, $exception->getError());
        $this->assertSame(ErrorCodes::TRANSPORT_ERROR, $exception->getErrorCode());
        $this->assertNull($exception->getErrorData());
    }

    public function testGetError(): void
    {
        $errorData = new ErrorData(ErrorCodes::AUTHENTICATION_ERROR, 'Auth failed', ['reason' => 'invalid_token']);
        $exception = new McpError($errorData);

        $retrievedError = $exception->getError();
        $this->assertSame($errorData, $retrievedError);
        $this->assertSame(ErrorCodes::AUTHENTICATION_ERROR, $retrievedError->getCode());
        $this->assertSame('Auth failed', $retrievedError->getMessage());
        $this->assertSame(['reason' => 'invalid_token'], $retrievedError->getData());
    }

    public function testGetErrorCode(): void
    {
        $errorData = new ErrorData(ErrorCodes::VALIDATION_ERROR, 'Validation failed');
        $exception = new McpError($errorData);

        $this->assertSame(ErrorCodes::VALIDATION_ERROR, $exception->getErrorCode());
    }

    public function testGetErrorData(): void
    {
        $data = ['field' => 'username', 'reason' => 'required'];
        $errorData = new ErrorData(ErrorCodes::VALIDATION_ERROR, 'Field required', $data);
        $exception = new McpError($errorData);

        $this->assertSame($data, $exception->getErrorData());
    }

    public function testGetErrorDataWhenNull(): void
    {
        $errorData = new ErrorData(ErrorCodes::INTERNAL_ERROR, 'Internal error');
        $exception = new McpError($errorData);

        $this->assertNull($exception->getErrorData());
    }

    public function testToArray(): void
    {
        $data = ['session_id' => 'abc123', 'timeout' => 30];
        $errorData = new ErrorData(ErrorCodes::TRANSPORT_ERROR, 'Connection timeout', $data);
        $exception = new McpError($errorData);

        $expected = [
            'code' => ErrorCodes::TRANSPORT_ERROR,
            'message' => 'Connection timeout',
            'data' => $data,
        ];

        $this->assertSame($expected, $exception->toArray());
    }

    public function testToArrayWithoutData(): void
    {
        $errorData = new ErrorData(ErrorCodes::RESOURCE_NOT_FOUND, 'Resource not found');
        $exception = new McpError($errorData);

        $expected = [
            'code' => ErrorCodes::RESOURCE_NOT_FOUND,
            'message' => 'Resource not found',
        ];

        $this->assertSame($expected, $exception->toArray());
    }

    public function testInheritanceFromException(): void
    {
        $errorData = new ErrorData(ErrorCodes::PROTOCOL_ERROR, 'Protocol error');
        $exception = new McpError($errorData);

        $this->assertInstanceOf(Exception::class, $exception);
        $this->assertInstanceOf(Throwable::class, $exception);
    }

    public function testThrowAndCatch(): void
    {
        $errorData = new ErrorData(ErrorCodes::TOOL_NOT_FOUND, 'Tool not found', ['tool' => 'calculator']);

        try {
            throw new McpError($errorData);
            $this->fail('Exception should have been thrown');
        } catch (McpError $exception) {
            $this->assertSame('Tool not found', $exception->getMessage());
            $this->assertSame(ErrorCodes::TOOL_NOT_FOUND, $exception->getErrorCode());
            $this->assertSame(['tool' => 'calculator'], $exception->getErrorData());
        }
    }

    public function testWithComplexErrorData(): void
    {
        $complexData = [
            'validation_errors' => [
                ['field' => 'email', 'code' => 'INVALID_FORMAT'],
                ['field' => 'password', 'code' => 'TOO_SHORT'],
            ],
            'timestamp' => '2024-12-01T10:00:00Z',
            'request_id' => 'req_123456',
            'nested' => [
                'level1' => [
                    'level2' => 'deep_value',
                ],
            ],
        ];

        $errorData = new ErrorData(ErrorCodes::VALIDATION_ERROR, 'Multiple validation errors', $complexData);
        $exception = new McpError($errorData);

        $this->assertSame($complexData, $exception->getErrorData());
        $this->assertSame('req_123456', $exception->getErrorData()['request_id']);
        $this->assertSame('deep_value', $exception->getErrorData()['nested']['level1']['level2']);
    }

    public function testErrorDataImmutability(): void
    {
        $originalData = ['key' => 'value'];
        $errorData = new ErrorData(ErrorCodes::MCP_ERROR, 'Test error', $originalData);
        $exception = new McpError($errorData);

        // Modify the original array
        $originalData['key'] = 'modified';

        // The exception's error data should not be affected
        $this->assertSame('value', $exception->getErrorData()['key']);
    }

    public function testWithDifferentErrorCodes(): void
    {
        $testCases = [
            ErrorCodes::PARSE_ERROR,
            ErrorCodes::PROTOCOL_ERROR,
            ErrorCodes::TRANSPORT_ERROR,
            ErrorCodes::AUTHENTICATION_ERROR,
            ErrorCodes::OAUTH_INVALID_SCOPE,
            ErrorCodes::HTTP_NOT_FOUND,
            ErrorCodes::STREAMABLE_HTTP_SESSION_EXPIRED,
            ErrorCodes::CONNECTION_LOST,
        ];

        foreach ($testCases as $code) {
            $message = "Error with code {$code}";
            $errorData = new ErrorData($code, $message);
            $exception = new McpError($errorData);

            $this->assertSame($code, $exception->getErrorCode());
            $this->assertSame($message, $exception->getMessage());
        }
    }
}
