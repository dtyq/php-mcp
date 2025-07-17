# HTTP 服务端指南

## 概述

HTTP 传输为 PHP MCP 服务器提供了基于 Web 的通信方法。它支持传统的请求-响应模式，并计划在未来版本中支持 Server-Sent Events (SSE) 和实时通信。此传输方式非常适合 Web 应用程序、API 和生产部署。

## 核心特性

- **HTTP/HTTPS 支持**：标准 Web 协议
- **JSON-RPC over HTTP**：标准 JSON-RPC 协议
- **生产环境就绪**：适合生产环境
- **Web 集成**：易于与 Web 框架集成
- **计划中**：即将支持 Server-Sent Events (SSE)

## 快速开始

### 1. 基础 HTTP 服务器设置

```php
<?php
declare(strict_types=1);

require_once 'vendor/autoload.php';

use Dtyq\PhpMcp\Server\McpServer;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use GuzzleHttp\Psr7\Request;

// 获取 HTTP 请求信息
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$headers = getallheaders() ?: [];
$body = file_get_contents('php://input');

$request = new Request($method, $path, $headers, $body);

// 路由到 MCP 端点
if ($path !== '/mcp') {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not Found']);
    exit;
}

// 仅允许 POST 方法处理 JSON-RPC
if ($method !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode([
        'jsonrpc' => '2.0',
        'error' => ['code' => -32601, 'message' => 'Method not allowed'],
        'id' => null,
    ]);
    exit;
}

// 创建服务器
$container = createContainer();
$app = new Application($container, getConfig());
$server = new McpServer('http-server', '1.0.0', $app);

// 注册您的工具、资源和提示
$server
    ->registerTool(createEchoTool())
    ->registerResource(createSystemInfoResource());

// 处理 HTTP 请求并发送响应
$response = $server->http($request);

// 发送 HTTP 响应
http_response_code($response->getStatusCode());
foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header("{$name}: {$value}");
    }
}
echo $response->getBody()->getContents();
```

### 2. 容器和配置

```php
function createContainer(): \Psr\Container\ContainerInterface {
    return new class implements \Psr\Container\ContainerInterface {
        private array $services = [];

        public function __construct() {
            $this->services[\Psr\Log\LoggerInterface::class] = new class extends \Psr\Log\AbstractLogger {
                public function log($level, $message, array $context = []): void {
                    $timestamp = date('Y-m-d H:i:s');
                    $contextStr = empty($context) ? '' : ' ' . json_encode($context);
                    file_put_contents('server.log', "[{$timestamp}] {$level}: {$message}{$contextStr}\n", FILE_APPEND);
                }
            };

            $this->services[\Psr\EventDispatcher\EventDispatcherInterface::class] = 
                new class implements \Psr\EventDispatcher\EventDispatcherInterface {
                    public function dispatch(object $event): object { return $event; }
                };
        }

        public function get($id) { return $this->services[$id]; }
        public function has($id): bool { return isset($this->services[$id]); }
    };
}

function getConfig(): array {
    return [
        'sdk_name' => 'php-mcp-http-server',
        'logging' => ['level' => 'info'],
        'transports' => [
            'http' => [
                'enabled' => true,
                'timeout' => 30,
                'max_request_size' => '10M',
            ],
        ],
    ];
}
```

### 3. 为 HTTP 添加工具

```php
use Dtyq\PhpMcp\Server\FastMcp\Tools\RegisteredTool;
use Dtyq\PhpMcp\Types\Tools\Tool;

function createWebInfoTool(): RegisteredTool {
    $tool = new Tool(
        'web_info',
        [
            'type' => 'object',
            'properties' => [
                'include_headers' => [
                    'type' => 'boolean', 
                    'description' => '在响应中包含 HTTP 头信息'
                ],
            ],
            'required' => [],
        ],
        '获取 Web 服务器信息和 HTTP 请求详情'
    );

    return new RegisteredTool($tool, function (array $args): array {
        $includeHeaders = $args['include_headers'] ?? false;
        
        $info = [
            'server_info' => [
                'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'protocol' => $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1',
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
                'uri' => $_SERVER['REQUEST_URI'] ?? '/',
                'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ],
            'php_info' => [
                'version' => PHP_VERSION,
                'sapi' => PHP_SAPI,
                'memory_usage' => memory_get_usage(true),
            ],
            'timestamp' => date('c'),
        ];
        
        if ($includeHeaders) {
            $info['headers'] = getallheaders() ?: [];
        }
        
        return $info;
    });
}
```

### 4. 文件上传工具

```php
function createFileUploadTool(): RegisteredTool {
    $tool = new Tool(
        'file_upload',
        [
            'type' => 'object',
            'properties' => [
                'filename' => ['type' => 'string', 'description' => '文件名'],
                'content' => ['type' => 'string', 'description' => 'Base64 编码的文件内容'],
                'directory' => ['type' => 'string', 'description' => '目标目录'],
            ],
            'required' => ['filename', 'content'],
        ],
        '上传文件到服务器'
    );

    return new RegisteredTool($tool, function (array $args): array {
        $filename = $args['filename'] ?? 'unnamed_file';
        $content = $args['content'] ?? '';
        $directory = $args['directory'] ?? 'uploads';
        
        // 验证文件名
        $filename = basename($filename);
        if (empty($filename)) {
                            throw new \Dtyq\PhpMcp\Shared\Exceptions\ValidationError('无效的文件名');
        }
        
        // 如果目录不存在则创建
        $uploadDir = __DIR__ . '/' . trim($directory, '/');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // 解码并保存文件
        $decodedContent = base64_decode($content);
        if ($decodedContent === false) {
                                throw new \Dtyq\PhpMcp\Shared\Exceptions\ValidationError('无效的 base64 内容');
        }
        
        $filepath = $uploadDir . '/' . $filename;
        $bytesWritten = file_put_contents($filepath, $decodedContent);
        
        return [
            'success' => true,
            'filename' => $filename,
            'path' => $filepath,
            'size' => $bytesWritten,
            'upload_time' => date('c'),
        ];
    });
}
```

## 计划中的功能

### Server-Sent Events (SSE) - 即将推出

我们正在开发 SSE 支持以实现实时通信。这将启用：

- 实时服务器状态更新
- 长时间运行操作的实时进度报告
- 事件驱动的通信模式

**注意**：SSE 实现目前正在开发中。现在，请使用常规 HTTP 请求的轮询来实现类似实时的行为。

### 流式 HTTP 传输 - 未来版本

根据 MCP 2025-03-26 规范，流式 HTTP 传输结合了 HTTP POST 和 Server-Sent Events，提供：

- 使用 HTTP POST 进行高效的请求/响应处理
- 使用 SSE 实现实时流传输功能
- 批处理和流操作的最佳性能
- 完全符合 MCP 规范

## 生产部署

### 1. Nginx 配置

```nginx
server {
    listen 80;
    server_name your-mcp-server.com;
    root /var/www/mcp-server/public;
    index index.php;

    # MCP 端点
    location /mcp {
        try_files $uri $uri/ /mcp.php?$query_string;
    }

    # 计划中的 SSE 端点
    location /sse {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Connection '';
        proxy_http_version 1.1;
        chunked_transfer_encoding off;
        proxy_buffering off;
        proxy_cache off;
    }

    # PHP 处理
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 2. Apache 配置

```apache
<VirtualHost *:80>
    ServerName your-mcp-server.com
    DocumentRoot /var/www/mcp-server/public
    
    # 启用重写引擎
    RewriteEngine On
    
    # 路由 MCP 请求
    RewriteRule ^/mcp$ /mcp.php [L]
    
    # PHP 处理器
    <FilesMatch \.php$>
        SetHandler application/x-httpd-php
    </FilesMatch>
    
    # 安全头
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
</VirtualHost>
```

### 3. Docker 部署

```dockerfile
FROM php:8.1-fpm-alpine

# 安装依赖
RUN apk add --no-cache nginx supervisor

# 复制应用程序
COPY . /var/www/html
WORKDIR /var/www/html

# 安装 PHP 依赖
RUN composer install --no-dev --optimize-autoloader

# 配置 nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf

# 配置 supervisor
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
```

## 安全考虑

### 1. 身份验证

```php
function authenticateRequest($request): bool {
    $headers = $request->getHeaders();
    $authHeader = $headers['Authorization'][0] ?? '';
    
    if (!str_starts_with($authHeader, 'Bearer ')) {
        return false;
    }
    
    $token = substr($authHeader, 7);
    return validateToken($token);
}

// 在您的服务器中使用
if (!authenticateRequest($request)) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
```

### 2. 速率限制

```php
class RateLimiter {
    private $redis;
    
    public function __construct($redis) {
        $this->redis = $redis;
    }
    
    public function isAllowed($clientId, $limit = 100, $window = 3600): bool {
        $key = "rate_limit:{$clientId}";
        $current = $this->redis->incr($key);
        
        if ($current === 1) {
            $this->redis->expire($key, $window);
        }
        
        return $current <= $limit;
    }
}

// 使用方法
$rateLimiter = new RateLimiter($redis);
$clientId = $_SERVER['REMOTE_ADDR'];

if (!$rateLimiter->isAllowed($clientId)) {
    http_response_code(429);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Rate limit exceeded']);
    exit;
}
```

### 3. 输入验证

```php
function validateJsonRpc($data): bool {
    if (!is_array($data)) return false;
    if (($data['jsonrpc'] ?? '') !== '2.0') return false;
    if (!isset($data['method']) || !is_string($data['method'])) return false;
    if (isset($data['params']) && !is_array($data['params']) && !is_object($data['params'])) return false;
    return true;
}

// 验证请求
$requestData = json_decode($body, true);
if (!validateJsonRpc($requestData)) {
    http_response_code(400);
    echo json_encode([
        'jsonrpc' => '2.0',
        'error' => ['code' => -32600, 'message' => 'Invalid Request'],
        'id' => null,
    ]);
    exit;
}
```

## 性能优化

### 1. 缓存

```php
use Psr\SimpleCache\CacheInterface;

class CachedResourceServer {
    private $cache;
    private $ttl;
    
    public function __construct(CacheInterface $cache, int $ttl = 3600) {
        $this->cache = $cache;
        $this->ttl = $ttl;
    }
    
    public function getResource($uri) {
        $cacheKey = 'resource:' . md5($uri);
        
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        $resource = $this->generateResource($uri);
        $this->cache->set($cacheKey, $resource, $this->ttl);
        
        return $resource;
    }
}
```

### 2. 连接池

```php
class DatabasePool {
    private $connections = [];
    private $maxConnections = 10;
    
    public function getConnection() {
        if (count($this->connections) < $this->maxConnections) {
            $this->connections[] = new PDO($dsn, $user, $pass);
        }
        
        return array_pop($this->connections);
    }
    
    public function releaseConnection($connection) {
        $this->connections[] = $connection;
    }
}
```

## 测试 HTTP 服务器

### 1. cURL 测试

```bash
# 测试基本端点
curl -X POST http://localhost/mcp \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "method": "tools/list",
    "id": 1
  }'

# 测试工具调用
curl -X POST http://localhost/mcp \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "method": "tools/call",
    "params": {
      "name": "web_info",
      "arguments": {"include_headers": true}
    },
    "id": 2
  }'
```

### 2. PHP 测试脚本

```php
<?php
$client = new GuzzleHttp\Client();

$response = $client->post('http://localhost/mcp', [
    'json' => [
        'jsonrpc' => '2.0',
        'method' => 'tools/list',
        'id' => 1,
    ],
]);

$data = json_decode($response->getBody(), true);
var_dump($data);
```

## 最佳实践

1. **错误处理**：始终返回适当的 JSON-RPC 错误响应
2. **日志记录**：记录所有请求和响应以便调试
3. **安全性**：实施身份验证和速率限制
4. **性能**：使用缓存和连接池
5. **监控**：监控服务器健康状况和性能指标

## 下一步

- [STDIO 服务端指南](./stdio-server.md) - 了解 STDIO 传输
- [服务端示例](./examples.md) - 查看完整实现示例
- [客户端文档](../client/stdio-client.md) - 了解如何创建客户端 