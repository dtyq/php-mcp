<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Shared\Utilities;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Shared\Utilities\JsonUtils;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for JsonUtils class.
 * @internal
 */
class JsonUtilsTest extends TestCase
{
    public function testEncode(): void
    {
        $data = ['key' => 'value', 'number' => 42];
        $json = JsonUtils::encode($data);

        $this->assertIsString($json);
        $this->assertEquals('{"key":"value","number":42}', $json);
    }

    public function testEncodePretty(): void
    {
        $data = ['key' => 'value'];
        $json = JsonUtils::encodePretty($data);

        $this->assertIsString($json);
        $this->assertStringContainsString("\n", $json); // Should have line breaks
        $this->assertStringContainsString('    ', $json); // Should have indentation
    }

    public function testEncodeInvalidData(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('JSON encoding failed');

        // Create an invalid data structure (circular reference)
        $data = [];
        $data['self'] = &$data;

        JsonUtils::encode($data);
    }

    public function testDecode(): void
    {
        $json = '{"key":"value","number":42}';
        $data = JsonUtils::decode($json);

        $this->assertIsArray($data);
        $this->assertEquals('value', $data['key']);
        $this->assertEquals(42, $data['number']);
    }

    public function testDecodeAsObject(): void
    {
        $json = '{"key":"value"}';
        $data = JsonUtils::decode($json, false);

        $this->assertIsObject($data);
        $this->assertEquals('value', $data->key);
    }

    public function testDecodeInvalidJson(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('JSON decoding failed');

        JsonUtils::decode('invalid json');
    }

    public function testSafeDecode(): void
    {
        $validJson = '{"key":"value"}';
        $result = JsonUtils::safeDecode($validJson);

        $this->assertTrue($result['success']);
        $this->assertEquals(['key' => 'value'], $result['data']);
    }

    public function testSafeDecodeInvalid(): void
    {
        $invalidJson = 'invalid json';
        $result = JsonUtils::safeDecode($invalidJson);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('JSON decoding failed', $result['error']);
    }

    public function testIsValid(): void
    {
        $this->assertTrue(JsonUtils::isValid('{"key":"value"}'));
        $this->assertTrue(JsonUtils::isValid('[]'));
        $this->assertTrue(JsonUtils::isValid('null'));
        $this->assertTrue(JsonUtils::isValid('true'));
        $this->assertTrue(JsonUtils::isValid('42'));

        $this->assertFalse(JsonUtils::isValid('invalid json'));
        $this->assertFalse(JsonUtils::isValid('{key:"value"}'));
    }

    public function testGetValidationError(): void
    {
        $validJson = '{"key":"value"}';
        $this->assertNull(JsonUtils::getValidationError($validJson));

        $invalidJson = 'invalid json';
        $error = JsonUtils::getValidationError($invalidJson);
        $this->assertIsString($error);
        $this->assertStringContainsString('JSON decoding failed', $error);
    }

    public function testMerge(): void
    {
        $json1 = '{"a":1,"b":2}';
        $json2 = '{"b":3,"c":4}';

        $merged = JsonUtils::merge([$json1, $json2], false);
        $data = JsonUtils::decode($merged);

        $this->assertEquals(1, $data['a']);
        $this->assertEquals(3, $data['b']); // Should be overwritten
        $this->assertEquals(4, $data['c']);
    }

    public function testMergeRecursive(): void
    {
        $json1 = '{"nested":{"a":1,"b":2}}';
        $json2 = '{"nested":{"b":3,"c":4}}';

        $merged = JsonUtils::merge([$json1, $json2], true);
        $data = JsonUtils::decode($merged);

        $this->assertEquals(1, $data['nested']['a']);
        $this->assertEquals([2, 3], $data['nested']['b']); // Should be merged as array
        $this->assertEquals(4, $data['nested']['c']);
    }

    public function testMergeEmpty(): void
    {
        $merged = JsonUtils::merge([]);
        $this->assertEquals('[]', $merged);
    }

    public function testMergeNonObjects(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Can only merge JSON objects/arrays');

        JsonUtils::merge(['"string"', '42']);
    }

    public function testExtractFields(): void
    {
        $json = '{"a":1,"b":2,"c":3}';
        $extracted = JsonUtils::extractFields($json, ['a', 'c']);
        $data = JsonUtils::decode($extracted);

        $this->assertEquals(['a' => 1, 'c' => 3], $data);
    }

    public function testExtractFieldsNotObject(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('JSON must be an object to extract fields');

        JsonUtils::extractFields('"string"', ['field']);
    }

    public function testRemoveFields(): void
    {
        $json = '{"a":1,"b":2,"c":3}';
        $filtered = JsonUtils::removeFields($json, ['b']);
        $data = JsonUtils::decode($filtered);

        $this->assertEquals(['a' => 1, 'c' => 3], $data);
    }

    public function testRemoveFieldsNotObject(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('JSON must be an object to remove fields');

        JsonUtils::removeFields('"string"', ['field']);
    }

    public function testHasRequiredFields(): void
    {
        $json = '{"a":1,"b":2,"c":3}';

        $this->assertTrue(JsonUtils::hasRequiredFields($json, ['a', 'b']));
        $this->assertTrue(JsonUtils::hasRequiredFields($json, []));
        $this->assertFalse(JsonUtils::hasRequiredFields($json, ['a', 'd']));
    }

    public function testHasRequiredFieldsNotObject(): void
    {
        $this->assertFalse(JsonUtils::hasRequiredFields('"string"', ['field']));
    }

    public function testGetMissingFields(): void
    {
        $json = '{"a":1,"b":2}';

        $missing = JsonUtils::getMissingFields($json, ['a', 'b', 'c', 'd']);
        $this->assertEquals(['c', 'd'], $missing);

        $missing = JsonUtils::getMissingFields($json, ['a', 'b']);
        $this->assertEquals([], $missing);
    }

    public function testGetMissingFieldsNotObject(): void
    {
        $required = ['field1', 'field2'];
        $missing = JsonUtils::getMissingFields('"string"', $required);
        $this->assertEquals($required, $missing);
    }

    public function testNormalize(): void
    {
        $messyJson = ' { "key" : "value" , "number" : 42 } ';
        $normalized = JsonUtils::normalize($messyJson);

        $this->assertEquals('{"key":"value","number":42}', $normalized);
    }

    public function testGetSize(): void
    {
        $json = '{"key":"value"}';
        $size = JsonUtils::getSize($json);

        $this->assertEquals(strlen($json), $size);
    }

    public function testExceedsSize(): void
    {
        $json = '{"key":"value"}';

        $this->assertFalse(JsonUtils::exceedsSize($json, 100));
        $this->assertTrue(JsonUtils::exceedsSize($json, 5));
    }

    public function testIsValidJsonRpcMessage(): void
    {
        // Valid request
        $request = ['jsonrpc' => '2.0', 'method' => 'test', 'params' => [], 'id' => 1];
        $this->assertTrue(JsonUtils::isValidJsonRpcMessage($request));

        // Valid notification
        $notification = ['jsonrpc' => '2.0', 'method' => 'test', 'params' => []];
        $this->assertTrue(JsonUtils::isValidJsonRpcMessage($notification));

        // Valid response
        $response = ['jsonrpc' => '2.0', 'result' => 'success', 'id' => 1];
        $this->assertTrue(JsonUtils::isValidJsonRpcMessage($response));

        // Valid error
        $error = ['jsonrpc' => '2.0', 'error' => ['code' => -1, 'message' => 'Error'], 'id' => 1];
        $this->assertTrue(JsonUtils::isValidJsonRpcMessage($error));

        // Invalid: wrong version
        $wrongVersion = ['jsonrpc' => '1.0', 'method' => 'test', 'id' => 1];
        $this->assertFalse(JsonUtils::isValidJsonRpcMessage($wrongVersion));

        // Invalid: missing jsonrpc
        $missingVersion = ['method' => 'test', 'id' => 1];
        $this->assertFalse(JsonUtils::isValidJsonRpcMessage($missingVersion));

        // Invalid: response with both result and error
        $invalidResponse = ['jsonrpc' => '2.0', 'result' => 'success', 'error' => ['code' => -1, 'message' => 'Error'], 'id' => 1];
        $this->assertFalse(JsonUtils::isValidJsonRpcMessage($invalidResponse));

        // Invalid: empty array
        $this->assertFalse(JsonUtils::isValidJsonRpcMessage([]));
    }

    public function testConstants(): void
    {
        $this->assertEquals(JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES, JsonUtils::DEFAULT_ENCODE_FLAGS);
        $this->assertEquals(JSON_THROW_ON_ERROR, JsonUtils::DEFAULT_DECODE_FLAGS);
        $this->assertEquals(JsonUtils::DEFAULT_ENCODE_FLAGS | JSON_PRETTY_PRINT, JsonUtils::PRETTY_PRINT_FLAGS);
        $this->assertEquals(512, JsonUtils::MAX_DEPTH);
    }
}
