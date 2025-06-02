<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Types\Core;

use Dtyq\PhpMcp\Types\Core\JsonRpcRequest;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class JsonRpcRequestTest extends TestCase
{
    public function testConstructorWithBasicParameters(): void
    {
        $request = new JsonRpcRequest('test.method', ['param1' => 'value1'], 'test-id');

        $this->assertEquals('test.method', $request->getMethod());
        $this->assertEquals(['param1' => 'value1'], $request->getParams());
        $this->assertEquals('test-id', $request->getId());
    }

    public function testConstructorWithNullParams(): void
    {
        $request = new JsonRpcRequest('test.method');

        $this->assertEquals('test.method', $request->getMethod());
        $this->assertNull($request->getParams());
        $this->assertNull($request->getId()); // Default ID is null
    }

    public function testConstructorWithEmptyMethodThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Method cannot be empty');

        new JsonRpcRequest('');
    }

    public function testFromArrayWithValidData(): void
    {
        $data = [
            'jsonrpc' => '2.0',
            'id' => 'test-id',
            'method' => 'test.method',
            'params' => ['key' => 'value'],
        ];

        $request = JsonRpcRequest::fromArray($data);

        $this->assertEquals('test.method', $request->getMethod());
        $this->assertEquals(['key' => 'value'], $request->getParams());
        $this->assertEquals('test-id', $request->getId());
    }

    public function testFromArrayWithoutJsonRpcThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON-RPC version');

        JsonRpcRequest::fromArray([
            'id' => 'test-id',
            'method' => 'test.method',
        ]);
    }

    public function testFromArrayWithInvalidJsonRpcVersionThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON-RPC version');

        JsonRpcRequest::fromArray([
            'jsonrpc' => '1.0',
            'id' => 'test-id',
            'method' => 'test.method',
        ]);
    }

    public function testFromArrayWithoutMethodThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Method is required');

        JsonRpcRequest::fromArray([
            'jsonrpc' => '2.0',
            'id' => 'test-id',
        ]);
    }

    public function testFromArrayWithoutIdThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ID is required for requests');

        JsonRpcRequest::fromArray([
            'jsonrpc' => '2.0',
            'method' => 'test.method',
        ]);
    }

    public function testSetMethodWithValidValue(): void
    {
        $request = new JsonRpcRequest('initial.method');
        $request->setMethod('new.method');

        $this->assertEquals('new.method', $request->getMethod());
    }

    public function testSetMethodWithEmptyStringThrowsException(): void
    {
        $request = new JsonRpcRequest('initial.method');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Method cannot be empty');

        $request->setMethod('');
    }

    public function testSetParamsWithValidValue(): void
    {
        $request = new JsonRpcRequest('test.method');
        $params = ['new' => 'params'];

        $request->setParams($params);

        $this->assertEquals($params, $request->getParams());
    }

    public function testSetParamsWithNull(): void
    {
        $request = new JsonRpcRequest('test.method', ['old' => 'params']);
        $request->setParams(null);

        $this->assertNull($request->getParams());
    }

    public function testSetIdWithStringValue(): void
    {
        $request = new JsonRpcRequest('test.method');
        $request->setId('new-string-id');

        $this->assertEquals('new-string-id', $request->getId());
    }

    public function testSetIdWithIntegerValue(): void
    {
        $request = new JsonRpcRequest('test.method');
        $request->setId(12345);

        $this->assertEquals(12345, $request->getId());
    }

    public function testSetIdWithInvalidTypeThrowsException(): void
    {
        $request = new JsonRpcRequest('test.method');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ID must be string or integer');

        $request->setId(123.45);
    }

    public function testProgressTokenHandling(): void
    {
        $request = new JsonRpcRequest('test.method');

        $this->assertFalse($request->hasProgressToken());
        $this->assertNull($request->getProgressToken());

        $request->setProgressToken('progress-123');

        $this->assertTrue($request->hasProgressToken());
        $this->assertEquals('progress-123', $request->getProgressToken());
    }

    public function testProgressTokenWithIntegerValue(): void
    {
        $request = new JsonRpcRequest('test.method');
        $request->setProgressToken(12345);

        $this->assertTrue($request->hasProgressToken());
        $this->assertEquals(12345, $request->getProgressToken());
    }

    public function testProgressTokenWithNullValue(): void
    {
        $request = new JsonRpcRequest('test.method');
        $request->setProgressToken('initial-token');
        $request->setProgressToken(null);

        $this->assertFalse($request->hasProgressToken());
        $this->assertNull($request->getProgressToken());
    }

    public function testSetProgressTokenWithInvalidTypeThrowsException(): void
    {
        $request = new JsonRpcRequest('test.method');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Progress token must be string, integer, or null');

        $request->setProgressToken(123.45);
    }

    public function testProgressTokenFromParamsMeta(): void
    {
        $params = [
            '_meta' => [
                'progressToken' => 'extracted-token',
            ],
            'data' => 'value',
        ];

        $request = new JsonRpcRequest('test.method', $params);

        $this->assertTrue($request->hasProgressToken());
        $this->assertEquals('extracted-token', $request->getProgressToken());
    }

    public function testSetProgressTokenUpdatesParams(): void
    {
        $request = new JsonRpcRequest('test.method');
        $request->setProgressToken('new-token');

        $params = $request->getParams();
        $this->assertIsArray($params);
        $this->assertArrayHasKey('_meta', $params);
        $this->assertArrayHasKey('progressToken', $params['_meta']);
        $this->assertEquals('new-token', $params['_meta']['progressToken']);
    }

    public function testToJsonRpcFormat(): void
    {
        $request = new JsonRpcRequest('test.method', ['param' => 'value'], 'test-id');

        $jsonRpc = $request->toJsonRpc();

        $expected = [
            'jsonrpc' => '2.0',
            'method' => 'test.method',
            'id' => 0, // toJsonRpc() converts ID to int, so string 'test-id' becomes 0
            'params' => ['param' => 'value'],
        ];

        $this->assertEquals($expected, $jsonRpc);
    }

    public function testToJsonRpcFormatWithoutParams(): void
    {
        $request = new JsonRpcRequest('test.method', null, 123);

        $jsonRpc = $request->toJsonRpc();

        $expected = [
            'jsonrpc' => '2.0',
            'method' => 'test.method',
            'id' => 123, // Integer ID remains as integer
        ];

        $this->assertEquals($expected, $jsonRpc);
    }

    public function testToJsonString(): void
    {
        $request = new JsonRpcRequest('test.method', ['param' => 'value'], 123);

        $json = $request->toJson();

        $expected = '{"jsonrpc":"2.0","method":"test.method","id":123,"params":{"param":"value"}}';
        $this->assertEquals($expected, $json);
    }

    public function testGeneratedIdIsUnique(): void
    {
        $request1 = new JsonRpcRequest('test.method');
        $request2 = new JsonRpcRequest('test.method');

        // Both requests should have null as default ID since no ID is provided
        $this->assertNull($request1->getId());
        $this->assertNull($request2->getId());
        $this->assertEquals($request1->getId(), $request2->getId());
    }
}
