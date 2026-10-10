<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Security;

use Dtyq\PhpMcp\Shared\Exceptions\SsrfException;

/** 校验所有解析地址并返回实际连接的固定 IP，避免检查与连接之间重新解析。 */
class UrlSecurityValidator
{
    private SsrfConfig $config;

    /** 注入当前连接的防护配置。 */
    public function __construct(SsrfConfig $config)
    {
        $this->config = $config;
    }

    /**
     * 校验请求地址；调用方必须使用返回 IP 建立连接。
     *
     * @return array{host: string, port: int, ip: string}
     */
    public function validate(string $url): array
    {
        $parts = parse_url($url);
        if ($parts === false || ! isset($parts['scheme'], $parts['host'])
            || isset($parts['user']) || isset($parts['pass']) || preg_match('/[\x00-\x20\x7f\\\]/', $url)) {
            throw new SsrfException('Invalid URL');
        }
        $scheme = strtolower($parts['scheme']);
        $host = trim($parts['host'], '[]');
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);
        $level = $this->config->getLevel();
        if (! in_array($scheme, ['https', 'http'], true) || ($level === 'strict' && ($scheme !== 'https' || $port !== 443))
            || ($level === 'standard' && ! in_array($port, [80, 443], true))) {
            throw new SsrfException('Scheme or port is not allowed');
        }
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            $ips = [$host];
        } else {
            if (filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false || preg_match('/^[0-9.]+$/', $host)
                || preg_match('/^(?:0x[0-9a-f]+|[0-9]+)(?:\.(?:0x[0-9a-f]+|[0-9]+))*\.?$/i', $host)) {
                throw new SsrfException('Invalid hostname');
            }
            $ips = $this->resolveHost($host);
        }
        if ($ips === []) {
            throw new SsrfException('DNS resolution failed');
        }
        foreach ($ips as $ip) {
            $this->validateIp($ip);
        }
        return ['host' => $host, 'port' => $port, 'ip' => $ips[0]];
    }

    /**
     * 禁止自定义 Host 与请求头注入绕过目标约束。
     *
     * @param array<string, string> $headers
     */
    public function validateHeaders(array $headers): void
    {
        foreach ($headers as $name => $value) {
            if (strcasecmp($name, 'Host') === 0 || ! preg_match('/^[!#$%&\'*+.^_`|~0-9A-Za-z-]+$/D', $name)
                || preg_match('/[\r\n\x00]/', $value)) {
                throw new SsrfException('Unsafe request header');
            }
        }
    }

    /**
     * 同时查询 A 和 AAAA 记录，便于使用 Mock 覆盖 DNS 行为。
     *
     * @return string[]
     */
    protected function resolveHost(string $host): array
    {
        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        if ($records === false) {
            throw new SsrfException('DNS resolution failed');
        }
        $ips = [];
        foreach ($records as $record) {
            if (isset($record['ip'])) {
                $ips[] = $record['ip'];
            } elseif (isset($record['ipv6'])) {
                $ips[] = $record['ipv6'];
            }
        }
        return array_values(array_unique($ips));
    }

    /** 对原始地址和 IPv4 映射地址应用全部分类与额外拦截规则。 */
    private function validateIp(string $ip): void
    {
        $packed = @inet_pton($ip);
        if ($packed === false) {
            throw new SsrfException('Invalid resolved IP');
        }
        $addresses = [$ip];
        if (strlen($packed) === 16 && substr($packed, 0, 12) === str_repeat("\0", 10) . "\xff\xff") {
            $addresses[] = (string) inet_ntop(substr($packed, 12));
        }
        $groups = [
            'private' => ['10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16', 'fc00::/7'],
            'loopback' => ['127.0.0.0/8', '::1/128'],
            'link_local' => ['169.254.0.0/16', 'fe80::/10'],
            'cloud_metadata' => ['169.254.169.254/32', '100.100.100.200/32', '100.96.0.96/32'],
            'reserved' => ['0.0.0.0/8', '100.64.0.0/10', '192.0.0.0/24', '192.0.2.0/24', '198.18.0.0/15', '198.51.100.0/24', '203.0.113.0/24', '224.0.0.0/4', '240.0.0.0/4', '::/128', '::/96', '100::/64', '64:ff9b::/96', '64:ff9b:1::/48', '2001::/23', '2001:db8::/32', '2002::/16', '3fff::/20', 'fec0::/10', 'ff00::/8'],
        ];
        foreach ($addresses as $address) {
            foreach ($groups as $type => $cidrs) {
                if ($this->config->blocks($type)) {
                    foreach ($cidrs as $cidr) {
                        if ($this->inCidr($address, $cidr)) {
                            throw new SsrfException('Blocked ' . $type . ' IP');
                        }
                    }
                }
            }
            foreach ($this->config->getExclusions('extra_blocked_ips') as $blocked) {
                if (inet_pton($address) === inet_pton($blocked)) {
                    throw new SsrfException('Excluded IP');
                }
            }
            foreach ($this->config->getExclusions('extra_blocked_cidrs') as $cidr) {
                if ($this->inCidr($address, $cidr)) {
                    throw new SsrfException('Excluded IP range');
                }
            }
        }
    }

    /** 使用二进制比较 IPv4 与 IPv6 网段。 */
    private function inCidr(string $ip, string $cidr): bool
    {
        [$network, $bits] = explode('/', $cidr);
        $address = inet_pton($ip);
        $prefix = inet_pton($network);
        if ($address === false || $prefix === false || strlen($address) !== strlen($prefix)) {
            return false;
        }
        $bytes = intdiv((int) $bits, 8);
        $remaining = (int) $bits % 8;
        return substr($address, 0, $bytes) === substr($prefix, 0, $bytes)
            && ($remaining === 0 || ((ord($address[$bytes]) ^ ord($prefix[$bytes])) & (0xFF << (8 - $remaining))) === 0);
    }
}
