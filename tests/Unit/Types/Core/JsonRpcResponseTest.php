<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Types\Core;

use Dtyq\PhpMcp\Shared\Exceptions\ProtocolError;
use Dtyq\PhpMcp\Types\Core\JsonRpcResponse;
use Dtyq\PhpMcp\Types\Core\ResultInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class JsonRpcResponseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testConstructorWithBasicParameters(): void
    {
        $result = ['data' => 'test', 'status' => 'success'];
        $response = new JsonRpcResponse('test-id', $result);

        $this->assertEquals('test-id', $response->getId());
        $this->assertEquals($result, $response->getResult());
    }

    public function testConstructorWithIntegerId(): void
    {
        $result = ['value' => 42];
        $response = new JsonRpcResponse(12345, $result);

        $this->assertEquals(12345, $response->getId());
        $this->assertEquals($result, $response->getResult());
    }

    public function testFromArrayWithValidData(): void
    {
        $data = [
            'jsonrpc' => '2.0',
            'id' => 'response-id',
            'result' => ['key' => 'value', 'count' => 5],
        ];

        $response = JsonRpcResponse::fromArray($data);

        $this->assertEquals('response-id', $response->getId());
        $this->assertEquals(['key' => 'value', 'count' => 5], $response->getResult());
    }

    public function testFromArrayWithoutJsonRpcThrowsException(): void
    {
        $this->expectException(ProtocolError::class);
        $this->expectExceptionMessage('Invalid message format: Invalid JSON-RPC version');

        JsonRpcResponse::fromArray([
            'id' => 'test-id',
            'result' => ['data' => 'test'],
        ]);
    }

    public function testFromArrayWithInvalidJsonRpcVersionThrowsException(): void
    {
        $this->expectException(ProtocolError::class);
        $this->expectExceptionMessage('Invalid message format: Invalid JSON-RPC version');

        JsonRpcResponse::fromArray([
            'jsonrpc' => '1.0',
            'id' => 'test-id',
            'result' => ['data' => 'test'],
        ]);
    }

    public function testFromArrayWithoutIdThrowsException(): void
    {
        $this->expectException(ProtocolError::class);
        $this->expectExceptionMessage('Missing required fields: id');

        JsonRpcResponse::fromArray([
            'jsonrpc' => '2.0',
            'result' => ['data' => 'test'],
        ]);
    }

    public function testFromArrayWithoutResultThrowsException(): void
    {
        $this->expectException(ProtocolError::class);
        $this->expectExceptionMessage('Missing required fields: result');

        JsonRpcResponse::fromArray([
            'jsonrpc' => '2.0',
            'id' => 'test-id',
        ]);
    }

    public function testFromArrayWithNonArrayResultThrowsException(): void
    {
        $this->expectException(ProtocolError::class);
        $this->expectExceptionMessage('Invalid message format: Result must be an array');

        JsonRpcResponse::fromArray([
            'jsonrpc' => '2.0',
            'id' => 'test-id',
            'result' => 'string-result',
        ]);
    }

    public function testSuccessStaticMethod(): void
    {
        $mockResult = Mockery::mock(ResultInterface::class);
        $mockResult->shouldReceive('toArray')
            ->once()
            ->andReturn(['success' => true, 'data' => 'test']);

        $response = JsonRpcResponse::success('success-id', $mockResult);

        $this->assertEquals('success-id', $response->getId());
        $this->assertEquals(['success' => true, 'data' => 'test'], $response->getResult());
    }

    public function testSetIdWithStringValue(): void
    {
        $response = new JsonRpcResponse('initial-id', []);
        $response->setId('new-string-id');

        $this->assertEquals('new-string-id', $response->getId());
    }

    public function testSetIdWithIntegerValue(): void
    {
        $response = new JsonRpcResponse('initial-id', []);
        $response->setId(54321);

        $this->assertEquals(54321, $response->getId());
    }

    public function testSetIdWithInvalidTypeThrowsException(): void
    {
        $response = new JsonRpcResponse('initial-id', []);

        $this->expectException(ProtocolError::class);
        $this->expectExceptionMessage('Invalid message format: ID must be string or integer');

        $response->setId(123.45);
    }

    public function testSetResult(): void
    {
        $response = new JsonRpcResponse('test-id', ['old' => 'data']);
        $newResult = ['new' => 'data', 'updated' => true];

        $response->setResult($newResult);

        $this->assertEquals($newResult, $response->getResult());
    }

    public function testToJsonRpcFormat(): void
    {
        $result = ['message' => 'success', 'code' => 200];
        $response = new JsonRpcResponse('format-id', $result);

        $jsonRpc = $response->toJsonRpc();

        $expected = [
            'jsonrpc' => '2.0',
            'id' => 'format-id',
            'result' => $result,
        ];

        $this->assertEquals($expected, $jsonRpc);
    }

    public function testToJsonString(): void
    {
        $result = ['status' => 'ok'];
        $response = new JsonRpcResponse('json-id', $result);

        $json = $response->toJson();

        $expected = '{"jsonrpc":"2.0","id":"json-id","result":{"status":"ok"}}';
        $this->assertEquals($expected, $json);
    }

    public function testMatchesRequestWithMatchingId(): void
    {
        $response = new JsonRpcResponse('matching-id', []);

        $this->assertTrue($response->matchesRequest('matching-id'));
    }

    public function testMatchesRequestWithIntegerId(): void
    {
        $response = new JsonRpcResponse(12345, []);

        $this->assertTrue($response->matchesRequest(12345));
    }

    public function testMatchesRequestWithNonMatchingId(): void
    {
        $response = new JsonRpcResponse('response-id', []);

        $this->assertFalse($response->matchesRequest('different-id'));
        $this->assertFalse($response->matchesRequest(12345));
    }

    public function testMatchesRequestWithMixedTypes(): void
    {
        $stringResponse = new JsonRpcResponse('123', []);
        $intResponse = new JsonRpcResponse(123, []);

        // String '123' should not match integer 123
        $this->assertFalse($stringResponse->matchesRequest(123));
        $this->assertFalse($intResponse->matchesRequest('123'));
    }

    public function testComplexResultData(): void
    {
        $complexResult = [
            'nested' => [
                'array' => [1, 2, 3],
                'object' => ['key' => 'value'],
            ],
            'boolean' => true,
            'null' => null,
            'number' => 42.5,
        ];

        $response = new JsonRpcResponse('complex-id', $complexResult);

        $this->assertEquals($complexResult, $response->getResult());

        $jsonRpc = $response->toJsonRpc();
        $this->assertEquals($complexResult, $jsonRpc['result']);
    }
}
