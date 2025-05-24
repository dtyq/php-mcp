<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Tests\Unit\Shared\Exceptions;

use Dtyq\PhpMcp\Shared\Exceptions\ErrorCodes;
use Dtyq\PhpMcp\Shared\Exceptions\ErrorData;
use JsonException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ErrorData class.
 * @internal
 */
class ErrorDataTest extends TestCase
{
    public function testConstructorWithBasicData(): void
    {
        $code = ErrorCodes::PROTOCOL_ERROR;
        $message = 'Test error message';
        $data = ['detail' => 'test detail'];

        $errorData = new ErrorData($code, $message, $data);

        $this->assertSame($code, $errorData->getCode());
        $this->assertSame($message, $errorData->getMessage());
        $this->assertSame($data, $errorData->getData());
    }

    public function testConstructorWithoutData(): void
    {
        $code = ErrorCodes::PARSE_ERROR;
        $message = 'Parse error occurred';

        $errorData = new ErrorData($code, $message);

        $this->assertSame($code, $errorData->getCode());
        $this->assertSame($message, $errorData->getMessage());
        $this->assertNull($errorData->getData());
    }

    public function testToArrayWithData(): void
    {
        $code = ErrorCodes::TRANSPORT_ERROR;
        $message = 'Transport failed';
        $data = ['transport' => 'HTTP', 'timeout' => 30];

        $errorData = new ErrorData($code, $message, $data);
        $array = $errorData->toArray();

        $expected = [
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ];

        $this->assertSame($expected, $array);
    }

    public function testToArrayWithoutData(): void
    {
        $code = ErrorCodes::AUTHENTICATION_ERROR;
        $message = 'Authentication failed';

        $errorData = new ErrorData($code, $message);
        $array = $errorData->toArray();

        $expected = [
            'code' => $code,
            'message' => $message,
        ];

        $this->assertSame($expected, $array);
    }

    public function testFromArray(): void
    {
        $array = [
            'code' => ErrorCodes::VALIDATION_ERROR,
            'message' => 'Validation failed',
            'data' => ['field' => 'username'],
        ];

        $errorData = ErrorData::fromArray($array);

        $this->assertSame($array['code'], $errorData->getCode());
        $this->assertSame($array['message'], $errorData->getMessage());
        $this->assertSame($array['data'], $errorData->getData());
    }

    public function testFromArrayWithoutData(): void
    {
        $array = [
            'code' => ErrorCodes::INTERNAL_ERROR,
            'message' => 'Internal server error',
        ];

        $errorData = ErrorData::fromArray($array);

        $this->assertSame($array['code'], $errorData->getCode());
        $this->assertSame($array['message'], $errorData->getMessage());
        $this->assertNull($errorData->getData());
    }

    public function testToJson(): void
    {
        $code = ErrorCodes::RESOURCE_NOT_FOUND;
        $message = 'Resource not found';
        $data = ['uri' => '/test/resource'];

        $errorData = new ErrorData($code, $message, $data);
        $json = $errorData->toJson();

        $expected = json_encode([
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ], JSON_THROW_ON_ERROR);

        $this->assertSame($expected, $json);
    }

    public function testFromJson(): void
    {
        $originalData = [
            'code' => ErrorCodes::TOOL_NOT_FOUND,
            'message' => 'Tool not found',
            'data' => ['tool' => 'calculator'],
        ];

        $json = json_encode($originalData);
        $errorData = ErrorData::fromJson($json);

        $this->assertSame($originalData['code'], $errorData->getCode());
        $this->assertSame($originalData['message'], $errorData->getMessage());
        $this->assertSame($originalData['data'], $errorData->getData());
    }

    public function testFromJsonWithInvalidJson(): void
    {
        $this->expectException(JsonException::class);
        ErrorData::fromJson('invalid json');
    }

    public function testJsonRoundTrip(): void
    {
        $code = ErrorCodes::OAUTH_INVALID_SCOPE;
        $message = 'Invalid OAuth scope';
        $data = ['scope' => 'read:sensitive', 'valid_scopes' => ['read:basic', 'write:basic']];

        $originalErrorData = new ErrorData($code, $message, $data);
        $json = $originalErrorData->toJson();
        $recreatedErrorData = ErrorData::fromJson($json);

        $this->assertSame($originalErrorData->getCode(), $recreatedErrorData->getCode());
        $this->assertSame($originalErrorData->getMessage(), $recreatedErrorData->getMessage());
        $this->assertSame($originalErrorData->getData(), $recreatedErrorData->getData());
        $this->assertSame($originalErrorData->toArray(), $recreatedErrorData->toArray());
    }

    public function testWithComplexDataStructure(): void
    {
        $code = ErrorCodes::STREAMABLE_HTTP_RESUMPTION_ERROR;
        $message = 'Resumption failed';
        $data = [
            'session_id' => 'abc123',
            'error_details' => [
                'code' => 'INVALID_TOKEN',
                'timestamp' => '2024-12-01T10:00:00Z',
                'attempts' => 3,
            ],
            'nested_array' => [
                ['key1' => 'value1'],
                ['key2' => 'value2'],
            ],
        ];

        $errorData = new ErrorData($code, $message, $data);

        $this->assertSame($data, $errorData->getData());
        $this->assertSame($data['session_id'], $errorData->getData()['session_id']);
        $this->assertSame($data['error_details']['code'], $errorData->getData()['error_details']['code']);
    }
}
