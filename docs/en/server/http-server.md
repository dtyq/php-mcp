# HTTP Server Guide

## Overview

The HTTP transport provides a web-based communication method for PHP MCP servers. It supports traditional request-response patterns with plans for Server-Sent Events (SSE) and real-time communication in future releases. This transport is ideal for web applications, APIs, and production deployments.

## Key Features

- **HTTP/HTTPS Support**: Standard web protocols
- **JSON-RPC over HTTP**: Standard JSON-RPC protocol
- **Production Ready**: Suitable for production environments
- **Web Integration**: Easy integration with web frameworks
- **Planned**: Server-Sent Events (SSE) support coming soon

## Quick Start

### 1. Basic HTTP Server Setup

```php
<?php
declare(strict_types=1);

require_once 'vendor/autoload.php';

use Dtyq\PhpMcp\Server\McpServer;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use GuzzleHttp\Psr7\Request;

// Get HTTP request information
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$headers = getallheaders() ?: [];
$body = file_get_contents('php://input');

$request = new Request($method, $path, $headers, $body);

// Route to MCP endpoint
if ($path !== '/mcp') {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not Found']);
    exit;
}

// Only allow POST method for JSON-RPC
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

// Create server
$container = createContainer();
$app = new Application($container, getConfig());
$server = new McpServer('http-server', '1.0.0', $app);

// Register your tools, resources, and prompts
$server
    ->registerTool(createEchoTool())
    ->registerResource(createSystemInfoResource());

// Handle HTTP request and send response
$response = $server->http($request);

// Send HTTP response
http_response_code($response->getStatusCode());
foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header("{$name}: {$value}");
    }
}
echo $response->getBody()->getContents();
```

### 2. Container and Configuration

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

### 3. Adding Tools for HTTP

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
                    'description' => 'Include HTTP headers in response'
                ],
            ],
            'required' => [],
        ],
        'Get web server information and HTTP request details'
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

### 4. File Upload Tool

```php
function createFileUploadTool(): RegisteredTool {
    $tool = new Tool(
        'file_upload',
        [
            'type' => 'object',
            'properties' => [
                'filename' => ['type' => 'string', 'description' => 'Name of the file'],
                'content' => ['type' => 'string', 'description' => 'Base64 encoded file content'],
                'directory' => ['type' => 'string', 'description' => 'Target directory'],
            ],
            'required' => ['filename', 'content'],
        ],
        'Upload a file to the server'
    );

    return new RegisteredTool($tool, function (array $args): array {
        $filename = $args['filename'] ?? 'unnamed_file';
        $content = $args['content'] ?? '';
        $directory = $args['directory'] ?? 'uploads';
        
        // Validate filename
        $filename = basename($filename);
        if (empty($filename)) {
            throw new InvalidArgumentException('Invalid filename');
        }
        
        // Create directory if it doesn't exist
        $uploadDir = __DIR__ . '/' . trim($directory, '/');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Decode and save file
        $decodedContent = base64_decode($content);
        if ($decodedContent === false) {
            throw new InvalidArgumentException('Invalid base64 content');
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

## Planned Features

### Server-Sent Events (SSE) - Coming Soon

We are developing SSE support for real-time communication. This will enable:

- Real-time server status updates
- Live progress reporting for long-running operations
- Event-driven communication patterns

**Note**: SSE implementation is currently under development. For now, use polling with regular HTTP requests for real-time-like behavior.

### Streamable HTTP Transport - Future Release

According to the MCP 2025-03-26 specification, Streamable HTTP transport combines HTTP POST with Server-Sent Events to provide:

- Efficient request/response handling with HTTP POST
- Real-time streaming capabilities with SSE
- Optimal performance for both batch and streaming operations
- Full compliance with MCP specification

## Production Deployment

### 1. Nginx Configuration

```nginx
server {
    listen 80;
    server_name your-mcp-server.com;
    root /var/www/mcp-server/public;
    index index.php;

    # MCP endpoint
    location /mcp {
        try_files $uri $uri/ /mcp.php?$query_string;
    }

    # SSE endpoint
    location /sse {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Connection '';
        proxy_http_version 1.1;
        chunked_transfer_encoding off;
        proxy_buffering off;
        proxy_cache off;
    }

    # PHP processing
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 2. Apache Configuration

```apache
<VirtualHost *:80>
    ServerName your-mcp-server.com
    DocumentRoot /var/www/mcp-server/public
    
    # Enable rewrite engine
    RewriteEngine On
    
    # Route MCP requests
    RewriteRule ^/mcp$ /mcp.php [L]
    
    # PHP handler
    <FilesMatch \.php$>
        SetHandler application/x-httpd-php
    </FilesMatch>
    
    # Security headers
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
</VirtualHost>
```

### 3. Docker Deployment

```dockerfile
FROM php:8.1-fpm-alpine

# Install dependencies
RUN apk add --no-cache nginx supervisor

# Copy application
COPY . /var/www/html
WORKDIR /var/www/html

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Configure nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Configure supervisor
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
```

## Security Considerations

### 1. Authentication

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

// Use in your server
if (!authenticateRequest($request)) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
```

### 2. Rate Limiting

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

// Usage
$rateLimiter = new RateLimiter($redis);
$clientId = $_SERVER['REMOTE_ADDR'];

if (!$rateLimiter->isAllowed($clientId)) {
    http_response_code(429);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Rate limit exceeded']);
    exit;
}
```

### 3. Input Validation

```php
function validateJsonRpc($data): bool {
    if (!is_array($data)) return false;
    if (($data['jsonrpc'] ?? '') !== '2.0') return false;
    if (!isset($data['method']) || !is_string($data['method'])) return false;
    if (isset($data['params']) && !is_array($data['params']) && !is_object($data['params'])) return false;
    return true;
}

// Validate request
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

## Performance Optimization

### 1. Caching

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

### 2. Connection Pooling

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

## Testing HTTP Server

### 1. cURL Testing

```bash
# Test basic endpoint
curl -X POST http://localhost/mcp \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "method": "tools/list",
    "id": 1
  }'

# Test tool call
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

### 2. PHP Testing Script

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

## Best Practices

1. **Error Handling**: Always return proper JSON-RPC error responses
2. **Logging**: Log all requests and responses for debugging
3. **Security**: Implement authentication and rate limiting
4. **Performance**: Use caching and connection pooling
5. **Monitoring**: Monitor server health and performance metrics

## Next Steps

- [STDIO Server Guide](./stdio-server.md) - Learn about STDIO transport
- [Server Examples](./examples.md) - See complete implementation examples
- [Client Documentation](../client/http-client.md) - Learn how to create HTTP clients 