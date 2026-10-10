<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Shared\Security;

use Dtyq\PhpMcp\Client\Configuration\HttpConfig;
use Dtyq\PhpMcp\Client\Transport\Http\HttpTransport;
use Dtyq\PhpMcp\Shared\Exceptions\SsrfException;
use Dtyq\PhpMcp\Shared\Kernel\Logger\LoggerProxy;
use Dtyq\PhpMcp\Shared\Security\SsrfConfig;
use Dtyq\PhpMcp\Shared\Security\UrlSecurityValidator;
use Dtyq\PhpMcp\Shared\Utilities\SSE\SocketConnector;
use Dtyq\PhpMcp\Shared\Utilities\SSE\SSEClient;
use Dtyq\PhpMcp\Tests\BaseTest;
use ReflectionProperty;

/**
 * 使用 Mock DNS、HTTP 和套接字验证防护，不访问真实网络。
 * @internal
 */
class SsrfProtectionTest extends BaseTest
{
    /** 验证默认兼容行为和配置序列化。 */
    public function testConfiguration(): void
    {
        $this->assertFalse((new SsrfConfig())->isEnabled());
        $this->assertFalse((new SsrfConfig(['enabled' => 'false']))->isEnabled());
        $this->assertTrue((new SsrfConfig(['enabled' => 'true']))->isEnabled());
        $config = HttpConfig::fromArray(['base_url' => 'https://mock.example', 'ssrf' => ['enabled' => true]]);
        $this->assertSame(['enabled' => true], $config->withChanges(['timeout' => 5.0])->getSsrf());
        $this->assertSame(['enabled' => true], $config->toArray()['ssrf']);
        $this->expectException(SsrfException::class);
        new SsrfConfig(['enabled' => 'mistyped']);
    }

    /**
     * 验证各类绕过地址与协议均被拦截。
     *
     * @dataProvider blockedUrlProvider
     */
    public function testBlockedUrls(string $url): void
    {
        $this->expectException(SsrfException::class);
        (new UrlSecurityValidator(new SsrfConfig(['enabled' => true])))->validate($url);
    }

    /**
     * 提供合成的危险目标。
     *
     * @return array<string, array{string}>
     */
    public function blockedUrlProvider(): array
    {
        return [
            'private' => ['https://10.0.0.1'],
            'loopback' => ['https://127.0.0.1'],
            'metadata' => ['https://169.254.169.254'],
            'aliyun metadata' => ['https://100.100.100.200'],
            'mapped loopback' => ['https://[::ffff:7f00:1]'],
            'mapped metadata' => ['https://[::ffff:6464:64c8]'],
            'ipv6 private' => ['https://[fd00::1]'],
            'ipv6 link local' => ['https://[fe80::1]'],
            'ipv6 multicast' => ['https://[ff02::1]'],
            'short ip' => ['https://127.1'],
            'integer ip' => ['https://2130706433'],
            'hex ip' => ['https://0x7f000001'],
            'userinfo' => ['https://user@mock.example'],
            'scheme' => ['file:///etc/passwd'],
            'strict http' => ['http://8.8.8.8'],
            'strict port' => ['https://8.8.8.8:8443'],
            'backslash' => ['https://mock.example\@127.0.0.1'],
        ];
    }

    /** 验证等级、IP 开关和额外 IPv6 网段边界。 */
    public function testLevelsAndIpOverrides(): void
    {
        $standard = new UrlSecurityValidator(new SsrfConfig(['level' => 'standard']));
        $this->assertSame(80, $standard->validate('http://8.8.8.8')['port']);
        $compatible = new UrlSecurityValidator(new SsrfConfig(['level' => 'compatible', 'ip_protection' => ['block_private_ip' => false]]));
        $this->assertSame(8080, $compatible->validate('http://10.0.0.1:8080')['port']);
        $validator = new UrlSecurityValidator(new SsrfConfig(['ip_protection' => ['extra_blocked_cidrs' => ['2606:4700::/32']]]));
        $this->expectException(SsrfException::class);
        $validator->validate('https://[2606:4700:4700::1111]');
    }

    /** 验证全部 DNS 地址都必须安全，不能只校验首个地址。 */
    public function testMixedDnsIsBlocked(): void
    {
        $validator = $this->validator(['8.8.8.8', '::1']);
        $this->expectException(SsrfException::class);
        $validator->validate('https://mock.example');
    }

    /** 验证固定连接 IP、关闭代理及重定向，同时保留默认关闭行为。 */
    public function testCurlOptions(): void
    {
        $config = HttpConfig::fromArray(['base_url' => 'https://mock.example', 'validate_ssl' => false, 'ssrf' => ['enabled' => true]]);
        $manager = $this->manager($config, $this->validator(['2606:4700:4700::1111']));
        $manager->sendGetRequest('https://mock.example', []);
        $this->assertFalse($manager->options[CURLOPT_FOLLOWLOCATION]);
        $this->assertSame(0, $manager->options[CURLOPT_MAXREDIRS]);
        $this->assertSame('', $manager->options[CURLOPT_PROXY]);
        $this->assertSame(['mock.example:443:[2606:4700:4700::1111]'], $manager->options[CURLOPT_RESOLVE]);
        $this->assertTrue($manager->options[CURLOPT_SSL_VERIFYPEER]);
        $disabled = $this->manager(new HttpConfig('http://127.0.0.1'), $this->validator([]));
        $disabled->sendGetRequest('http://127.0.0.1', []);
        $this->assertTrue($disabled->options[CURLOPT_FOLLOWLOCATION]);
        $this->assertArrayNotHasKey(CURLOPT_RESOLVE, $disabled->options);
    }

    /** 验证重定向拦截不重试，也不会发起第二次连接。 */
    public function testRedirectDoesNotRetry(): void
    {
        $manager = $this->manager(HttpConfig::fromArray(['base_url' => 'https://mock.example', 'ssrf' => ['enabled' => true]]), $this->validator(['8.8.8.8']));
        $manager->status = 302;
        try {
            $manager->sendPostRequest('https://mock.example', []);
            $this->fail('Redirect should be blocked');
        } catch (SsrfException $e) {
            $this->assertSame(1, $manager->calls);
        }
    }

    /** 验证配置中的 Host 头不能覆盖目标地址。 */
    public function testUnsafeHeaders(): void
    {
        $config = HttpConfig::fromArray(['base_url' => 'https://mock.example', 'ssrf' => ['enabled' => true], 'headers' => ['hOsT' => 'localhost']]);
        $manager = $this->manager($config, $this->validator(['8.8.8.8']));
        $this->expectException(SsrfException::class);
        $manager->sendGetRequest('https://mock.example', []);
    }

    /** 验证 Application 继承与连接级显式关闭互不干扰。 */
    public function testApplicationInheritance(): void
    {
        $app = $this->createApplication();
        $app->getConfig()->set('ssrf', ['enabled' => true]);
        $transport = new HttpTransport(new HttpConfig('https://mock.example'), $app);
        $property = new ReflectionProperty(HttpTransport::class, 'config');
        $property->setAccessible(true);
        $this->assertSame(['enabled' => true], $property->getValue($transport)->getSsrf());
        $disabled = new HttpTransport(HttpConfig::fromArray(['base_url' => 'https://mock.example', 'ssrf' => ['enabled' => false]]), $app);
        $this->assertSame(['enabled' => false], $property->getValue($disabled)->getSsrf());
    }

    /** 验证 SSE 连接使用固定 IP，但 TLS 校验仍使用原始域名。 */
    public function testSseSocketPinning(): void
    {
        $connector = $this->getMockBuilder(SocketConnector::class)->onlyMethods(['connect'])->getMock();
        [$socket, $peer] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        fwrite($peer, "HTTP/1.1 200 OK\r\nContent-Type: text/event-stream\r\n\r\ndata: mock\n\n");
        $connector->expects($this->once())->method('connect')->with('ssl://8.8.8.8:443', 10, $this->callback(static function (array $options): bool {
            return $options['ssl']['peer_name'] === 'mock.example' && $options['ssl']['verify_peer'] && $options['ssl']['verify_peer_name'];
        }))->willReturn($socket);
        $client = new SSEClient('https://mock.example', 10, [], new SsrfConfig(['enabled' => true]), $this->validator(['8.8.8.8']), $connector);
        $events = $client->getEvents();
        $events->rewind();
        $this->assertSame('mock', $events->current()->data);
        stream_set_blocking($peer, false);
        $this->assertStringContainsString('Host: mock.example', stream_get_contents($peer));
        fclose($peer);
    }

    /** 验证连接时 DNS 变为内网地址，后续请求会在发包前拒绝。 */
    public function testDnsChangeBeforeNextRequest(): void
    {
        $validator = $this->getMockBuilder(UrlSecurityValidator::class)->setConstructorArgs([new SsrfConfig(['enabled' => true])])->onlyMethods(['resolveHost'])->getMock();
        $validator->method('resolveHost')->willReturnOnConsecutiveCalls(['8.8.8.8'], ['10.0.0.1']);
        $manager = $this->manager(HttpConfig::fromArray(['base_url' => 'https://mock.example', 'ssrf' => ['enabled' => true]]), $validator);
        $manager->sendGetRequest('https://mock.example', []);
        try {
            $manager->sendPostRequest('https://mock.example/endpoint', []);
            $this->fail('Changed DNS should be blocked');
        } catch (SsrfException $e) {
            $this->assertSame(1, $manager->calls);
        }
    }

    /** 验证协议探测不能吞掉安全异常后回退到旧版 SSE。 */
    public function testProtocolDetectionPreservesSecurityFailure(): void
    {
        $transport = new class(new HttpConfig('https://mock.example'), $this->createApplication()) extends HttpTransport {
            /** 向测试开放协议探测。 */
            public function detect(): string
            {
                return $this->detectProtocolVersion();
            }

            /**
             * 使用安全拒绝替代真实网络请求。
             *
             * @return array<string, mixed>
             */
            protected function performProtocolDetectionRequest(): array
            {
                throw new SsrfException('Mock security failure');
            }
        };
        $this->expectException(SsrfException::class);
        $transport->detect();
    }

    /** 验证 SSE 的重定向直接失败，不建立第二个连接。 */
    public function testSseRedirectIsBlocked(): void
    {
        [$socket, $peer] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        fwrite($peer, "HTTP/1.1 302 Found\r\nLocation: http://127.0.0.1\r\n\r\n");
        $connector = $this->getMockBuilder(SocketConnector::class)->onlyMethods(['connect'])->getMock();
        $connector->expects($this->once())->method('connect')->willReturn($socket);
        $client = new SSEClient('https://mock.example', 10, [], new SsrfConfig(['enabled' => true]), $this->validator(['8.8.8.8']), $connector);
        $this->expectException(SsrfException::class);
        try {
            $client->getEvents()->rewind();
        } finally {
            fclose($peer);
        }
    }

    /** 验证 SSE 内网目标在建立套接字前拒绝。 */
    public function testSseTargetIsBlockedBeforeConnecting(): void
    {
        $connector = $this->getMockBuilder(SocketConnector::class)->onlyMethods(['connect'])->getMock();
        $connector->expects($this->never())->method('connect');
        $client = new SSEClient('https://mock.example', 10, [], new SsrfConfig(['enabled' => true]), $this->validator(['::1']), $connector);
        $this->expectException(SsrfException::class);
        $client->getEvents()->rewind();
    }

    /** 验证请求头换行注入被拒绝。 */
    public function testHeaderInjectionIsBlocked(): void
    {
        $validator = $this->validator([]);
        $this->expectException(SsrfException::class);
        $validator->validateHeaders(['X-Mock' => "mock\r\nHost: localhost"]);
    }

    /** 验证错误网段配置不会静默失效。 */
    public function testInvalidCidrIsRejected(): void
    {
        $this->expectException(SsrfException::class);
        new SsrfConfig(['ip_protection' => ['extra_blocked_cidrs' => ['10.0.0.0/33']]]);
    }

    /**
     * 创建固定解析结果的 DNS Mock。
     *
     * @param string[] $ips
     */
    private function validator(array $ips): UrlSecurityValidator
    {
        $validator = $this->getMockBuilder(UrlSecurityValidator::class)->setConstructorArgs([new SsrfConfig(['enabled' => true])])->onlyMethods(['resolveHost'])->getMock();
        $validator->method('resolveHost')->willReturn($ips);
        return $validator;
    }

    /** 创建不发起真实请求的连接管理器。 */
    private function manager(HttpConfig $config, UrlSecurityValidator $validator): MockConnectionManager
    {
        return new MockConnectionManager($config, new LoggerProxy('ssrf-test'), $validator);
    }
}
