<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Shared\Utilities;

use Dtyq\PhpMcp\Shared\Utilities\HttpUtils;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for HttpUtils class.
 * @internal
 */
class HttpUtilsTest extends TestCase
{
    public function testCreateHttpContextDefaults(): void
    {
        $context = HttpUtils::createHttpContext();

        $this->assertIsArray($context);
        $this->assertArrayHasKey('http', $context);

        $httpOptions = $context['http'];
        $this->assertEquals('GET', $httpOptions['method']);
        $this->assertEquals(30.0, $httpOptions['timeout']);
        $this->assertTrue($httpOptions['follow_location']);
        $this->assertEquals(5, $httpOptions['max_redirects']);
    }

    public function testCreateHttpContextWithParameters(): void
    {
        $headers = ['Authorization' => 'Bearer token'];
        $content = 'test content';
        $timeout = 60.0;

        $context = HttpUtils::createHttpContext('POST', $headers, $content, $timeout);

        $httpOptions = $context['http'];
        $this->assertEquals('POST', $httpOptions['method']);
        $this->assertEquals($timeout, $httpOptions['timeout']);
        $this->assertEquals($content, $httpOptions['content']);
        $this->assertStringContainsString('Authorization: Bearer token', $httpOptions['header']);
    }

    public function testCreateJsonContext(): void
    {
        $data = ['key' => 'value'];
        $context = HttpUtils::createJsonContext('POST', $data);

        $httpOptions = $context['http'];
        $this->assertEquals('POST', $httpOptions['method']);
        $this->assertStringContainsString('Content-Type: application/json', $httpOptions['header']);
        $this->assertEquals('{"key":"value"}', $httpOptions['content']);
    }

    public function testCreateJsonContextWithNullData(): void
    {
        $context = HttpUtils::createJsonContext('GET', null);

        $httpOptions = $context['http'];
        $this->assertEquals('GET', $httpOptions['method']);
        $this->assertArrayNotHasKey('content', $httpOptions);
    }

    public function testCreateFormContext(): void
    {
        $data = ['username' => 'test', 'password' => 'secret'];
        $context = HttpUtils::createFormContext('POST', $data);

        $httpOptions = $context['http'];
        $this->assertEquals('POST', $httpOptions['method']);
        $this->assertStringContainsString('Content-Type: application/x-www-form-urlencoded', $httpOptions['header']);
        $this->assertEquals('username=test&password=secret', $httpOptions['content']);
    }

    public function testCreateSseContext(): void
    {
        $context = HttpUtils::createSseContext();

        $httpOptions = $context['http'];
        $this->assertEquals('GET', $httpOptions['method']);
        $this->assertStringContainsString('Accept: text/event-stream', $httpOptions['header']);
        $this->assertStringContainsString('Cache-Control: no-cache', $httpOptions['header']);
    }

    public function testCreateStreamableHttpContext(): void
    {
        $data = ['method' => 'ping'];
        $context = HttpUtils::createStreamableHttpContext('POST', $data);

        $httpOptions = $context['http'];
        $this->assertEquals('POST', $httpOptions['method']);
        $this->assertStringContainsString('Content-Type: application/json', $httpOptions['header']);
        $this->assertStringContainsString('Accept: text/event-stream, application/json', $httpOptions['header']);
        $this->assertEquals('{"method":"ping"}', $httpOptions['content']);
    }

    public function testIsValidUrl(): void
    {
        $this->assertTrue(HttpUtils::isValidUrl('https://example.com'));
        $this->assertTrue(HttpUtils::isValidUrl('http://localhost:8080'));
        $this->assertTrue(HttpUtils::isValidUrl('ftp://files.example.com'));

        $this->assertFalse(HttpUtils::isValidUrl('not-a-url'));
        $this->assertFalse(HttpUtils::isValidUrl('http://'));
        $this->assertFalse(HttpUtils::isValidUrl(''));
    }

    public function testRemoveRequestParams(): void
    {
        $this->assertEquals(
            'https://example.com/path',
            HttpUtils::removeRequestParams('https://example.com/path?param=value')
        );

        $this->assertEquals(
            'http://localhost:8080/api',
            HttpUtils::removeRequestParams('http://localhost:8080/api?q=test&limit=10')
        );

        $this->assertEquals(
            'https://example.com/path',
            HttpUtils::removeRequestParams('https://example.com/path')
        );

        $this->assertEquals(
            'https://example.com:8443/path',
            HttpUtils::removeRequestParams('https://example.com:8443/path?param=value')
        );
    }

    public function testBuildUrl(): void
    {
        $this->assertEquals(
            'https://example.com/api',
            HttpUtils::buildUrl('https://example.com/api', [])
        );

        $this->assertEquals(
            'https://example.com/api?key=value',
            HttpUtils::buildUrl('https://example.com/api', ['key' => 'value'])
        );

        $this->assertEquals(
            'https://example.com/api?existing=param&new=value',
            HttpUtils::buildUrl('https://example.com/api?existing=param', ['new' => 'value'])
        );

        $this->assertEquals(
            'https://example.com/api?foo=bar&baz=qux',
            HttpUtils::buildUrl('https://example.com/api', ['foo' => 'bar', 'baz' => 'qux'])
        );
    }

    public function testParseHttpResponse(): void
    {
        $response = "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\nAuthorization: Bearer token\r\n\r\n{\"key\":\"value\"}";

        $parsed = HttpUtils::parseHttpResponse($response);

        $this->assertArrayHasKey('headers', $parsed);
        $this->assertArrayHasKey('body', $parsed);
        $this->assertEquals('application/json', $parsed['headers']['Content-Type']);
        $this->assertEquals('Bearer token', $parsed['headers']['Authorization']);
        $this->assertEquals('{"key":"value"}', $parsed['body']);
    }

    public function testGetBearerAuthHeader(): void
    {
        $header = HttpUtils::getBearerAuthHeader('test-token');

        $this->assertEquals(['Authorization' => 'Bearer test-token'], $header);
    }

    public function testGetBasicAuthHeader(): void
    {
        $header = HttpUtils::getBasicAuthHeader('user', 'pass');

        $expectedCredentials = base64_encode('user:pass');
        $this->assertEquals(['Authorization' => "Basic {$expectedCredentials}"], $header);
    }

    public function testIsSuccessStatusCode(): void
    {
        $this->assertTrue(HttpUtils::isSuccessStatusCode(200));
        $this->assertTrue(HttpUtils::isSuccessStatusCode(201));
        $this->assertTrue(HttpUtils::isSuccessStatusCode(299));

        $this->assertFalse(HttpUtils::isSuccessStatusCode(199));
        $this->assertFalse(HttpUtils::isSuccessStatusCode(300));
        $this->assertFalse(HttpUtils::isSuccessStatusCode(400));
        $this->assertFalse(HttpUtils::isSuccessStatusCode(500));
    }

    public function testIsClientErrorStatusCode(): void
    {
        $this->assertTrue(HttpUtils::isClientErrorStatusCode(400));
        $this->assertTrue(HttpUtils::isClientErrorStatusCode(404));
        $this->assertTrue(HttpUtils::isClientErrorStatusCode(499));

        $this->assertFalse(HttpUtils::isClientErrorStatusCode(399));
        $this->assertFalse(HttpUtils::isClientErrorStatusCode(500));
        $this->assertFalse(HttpUtils::isClientErrorStatusCode(200));
    }

    public function testIsServerErrorStatusCode(): void
    {
        $this->assertTrue(HttpUtils::isServerErrorStatusCode(500));
        $this->assertTrue(HttpUtils::isServerErrorStatusCode(503));
        $this->assertTrue(HttpUtils::isServerErrorStatusCode(599));

        $this->assertFalse(HttpUtils::isServerErrorStatusCode(499));
        $this->assertFalse(HttpUtils::isServerErrorStatusCode(600));
        $this->assertFalse(HttpUtils::isServerErrorStatusCode(200));
    }

    public function testHttpMethodConstants(): void
    {
        $this->assertEquals('GET', HttpUtils::METHOD_GET);
        $this->assertEquals('POST', HttpUtils::METHOD_POST);
        $this->assertEquals('PUT', HttpUtils::METHOD_PUT);
        $this->assertEquals('DELETE', HttpUtils::METHOD_DELETE);
        $this->assertEquals('PATCH', HttpUtils::METHOD_PATCH);
        $this->assertEquals('HEAD', HttpUtils::METHOD_HEAD);
        $this->assertEquals('OPTIONS', HttpUtils::METHOD_OPTIONS);
    }

    public function testHttpHeaderConstants(): void
    {
        $this->assertEquals(['Content-Type' => 'application/json'], HttpUtils::HEADERS_JSON);
        $this->assertEquals(['Content-Type' => 'application/x-www-form-urlencoded'], HttpUtils::HEADERS_FORM);

        $sseHeaders = HttpUtils::HEADERS_SSE;
        $this->assertEquals('text/event-stream', $sseHeaders['Accept']);
        $this->assertEquals('no-cache', $sseHeaders['Cache-Control']);

        $streamableHeaders = HttpUtils::HEADERS_STREAMABLE_HTTP;
        $this->assertEquals('application/json', $streamableHeaders['Content-Type']);
        $this->assertEquals('text/event-stream, application/json', $streamableHeaders['Accept']);
    }
}
