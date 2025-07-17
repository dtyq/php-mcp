# HTTP 客户端指南

## 概述

HTTP 客户端提供了一种基于 Web 的通信方法来连接 PHP MCP 服务器。它支持带有 JSON-RPC 协议的标准 HTTP 请求，非常适合 Web 应用程序、微服务和分布式系统。

## 核心特性

- **HTTP/HTTPS 支持**：标准 Web 协议
- **JSON-RPC over HTTP**：标准 JSON-RPC 协议
- **生产环境就绪**：适合生产环境
- **认证支持**：内置认证机制
- **错误处理**：全面的错误处理和重试逻辑

## 快速开始

### 1. 基础 HTTP 客户端设置

```php
<?php
require_once 'vendor/autoload.php';

use Dtyq\PhpMcp\Client\McpClient;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use GuzzleHttp\Client as HttpClient;

// 简单的 DI 容器
$container = new class implements \Psr\Container\ContainerInterface {
    private array $services = [];

    public function __construct() {
        $this->services[\Psr\Log\LoggerInterface::class] = new class extends \Psr\Log\AbstractLogger {
            public function log($level, $message, array $context = []): void {
                $timestamp = date('Y-m-d H:i:s');
                $contextStr = empty($context) ? '' : ' ' . json_encode($context);
                echo "[{$timestamp}] {$level}: {$message}{$contextStr}\n";
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

// 配置
$config = ['sdk_name' => 'my-http-client'];

// 创建应用程序和客户端
$app = new Application($container, $config);
$client = new McpClient('my-http-client', '1.0.0', $app);

// ✅ 推荐：使用类型化配置的快捷方法
use Dtyq\PhpMcp\Client\Configuration\HttpConfig;

$config = new HttpConfig('https://your-mcp-server.com/mcp');
$config->setTimeout(30);
$config->setHeaders([
    'Authorization' => 'Bearer your-api-token',
    'Content-Type' => 'application/json',
]);
$session = $client->http($config);

// 初始化会话
$session->initialize();

echo "已连接到 HTTP MCP 服务器！\n";
```

### 2. 连接到不同的服务器

```php
// ✅ 推荐：使用类型化配置的快捷方法
use Dtyq\PhpMcp\Client\Configuration\HttpConfig;

// 连接到本地开发服务器
$localConfig = new HttpConfig('http://localhost:8000/mcp');
$localConfig->setTimeout(10);
$session = $client->http($localConfig);

// 带认证的连接
$authConfig = new HttpConfig('https://api.example.com/mcp');
$authConfig->setHeaders([
    'Authorization' => 'Bearer ' . $apiToken,
    'X-API-Key' => $apiKey,
]);
$authConfig->setTimeout(60);
$session = $client->http($authConfig);
```

**传统方法（已弃用）**：
```php
// ⚠️ 此方法已弃用，请使用 http() 快捷方法
$session = $client->connect('http', [
    'url' => 'http://localhost:8000/mcp',
    'timeout' => 10,
]);

// 使用自定义 HTTP 客户端配置连接
$httpClient = new HttpClient([
    'verify' => false, // 仅用于开发
    'proxy' => 'http://proxy.company.com:8080',
    'timeout' => 30,
]);

$session = $client->connect('http', [
    'url' => 'https://internal-server.company.com/mcp',
    'http_client' => $httpClient,
]);
```

### 3. 使用工具

```php
// 列出可用工具
$toolsResult = $session->listTools();
echo "可用工具：\n";
foreach ($toolsResult->getTools() as $tool) {
    echo "- {$tool->getName()}: {$tool->getDescription()}\n";
}

// 调用 Web 特定工具
try {
    $result = $session->callTool('web_scrape', [
        'url' => 'https://example.com',
        'selector' => 'h1',
    ]);
    
    echo "网页抓取结果：\n";
    foreach ($result->getContent() as $content) {
        if ($content instanceof \Dtyq\PhpMcp\Types\Content\TextContent) {
            echo $content->getText() . "\n";
        }
    }
} catch (Exception $e) {
    echo "工具调用失败：" . $e->getMessage() . "\n";
}

// 调用 API 集成工具
$apiResult = $session->callTool('api_request', [
    'method' => 'GET',
    'endpoint' => '/users/123',
    'headers' => ['Accept' => 'application/json'],
]);

$content = $apiResult->getContent();
if (!empty($content)) {
    $firstContent = $content[0];
    if ($firstContent instanceof \Dtyq\PhpMcp\Types\Content\TextContent) {
        $data = json_decode($firstContent->getText(), true);
        echo "API 响应：" . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    }
}
```

### 4. 使用资源

```php
use Dtyq\PhpMcp\Types\Resources\TextResourceContents;

// 列出可用资源
$resourcesResult = $session->listResources();
echo "可用资源：\n";
foreach ($resourcesResult->getResources() as $resource) {
    echo "- {$resource->getUri()}: {$resource->getName()}\n";
}

// 读取 Web 资源
try {
    $resourceResult = $session->readResource('web://config/database');
    foreach ($resourceResult->getContents() as $content) {
        if ($content instanceof TextResourceContents) {
            $text = $content->getText();
            if ($text !== null) {
                $config = json_decode($text, true);
                echo "数据库配置：\n";
                echo "- 主机：" . ($config['host'] ?? '未知') . "\n";
                echo "- 数据库：" . ($config['database'] ?? '未知') . "\n";
                echo "- 端口：" . ($config['port'] ?? '未知') . "\n";
            }
        }
    }
} catch (Exception $e) {
    echo "资源读取失败：" . $e->getMessage() . "\n";
}

// 使用参数读取动态资源
try {
    $userResource = $session->readResource('api://users/456/profile');
    foreach ($userResource->getContents() as $content) {
        if ($content instanceof TextResourceContents) {
            $text = $content->getText();
            if ($text !== null) {
                $profile = json_decode($text, true);
                echo "用户档案：\n";
                echo "- 姓名：" . ($profile['name'] ?? '未知') . "\n";
                echo "- 邮箱：" . ($profile['email'] ?? '未知') . "\n";
                echo "- 角色：" . ($profile['role'] ?? '未知') . "\n";
            }
        }
    }
} catch (Exception $e) {
    echo "用户档案读取失败：" . $e->getMessage() . "\n";
}
```

### 5. 使用提示

```php
use Dtyq\PhpMcp\Types\Content\TextContent;

// 列出可用提示
$promptsResult = $session->listPrompts();
echo "可用提示：\n";
foreach ($promptsResult->getPrompts() as $prompt) {
    echo "- {$prompt->getName()}: {$prompt->getDescription()}\n";
    
    // 显示提示参数
    foreach ($prompt->getArguments() as $arg) {
        $required = $arg->isRequired() ? '（必需）' : '（可选）';
        echo "  * {$arg->getName()}: {$arg->getDescription()}{$required}\n";
    }
}

// 获取动态提示
try {
    $promptResult = $session->getPrompt('email_template', [
        'type' => 'welcome',
        'user_name' => '张三',
        'company' => 'Acme 公司',
    ]);
    
    echo "邮件模板：" . ($promptResult->getDescription() ?? '无描述') . "\n";
    echo "生成的内容：\n";
    
    foreach ($promptResult->getMessages() as $message) {
        $content = $message->getContent();
        if ($content instanceof TextContent) {
            echo "- 角色：{$message->getRole()}\n";
            echo "  内容：{$content->getText()}\n";
        }
    }
} catch (Exception $e) {
    echo "提示失败：" . $e->getMessage() . "\n";
}
```

## 配置选项

### HTTP 传输配置

```php
$session = $client->connect('http', [
    'url' => 'https://api.example.com/mcp',  // 服务器端点
    'timeout' => 30,                         // 请求超时时间（秒）
    'retry_attempts' => 3,                   // 重试次数
    'retry_delay' => 1000,                   // 重试间隔（毫秒）
    'headers' => [                           // 自定义头信息
        'Authorization' => 'Bearer token',
        'X-API-Key' => 'api-key',
        'User-Agent' => 'MyApp/1.0',
    ],
    'verify_ssl' => true,                    // SSL 验证
    'proxy' => 'http://proxy.example.com',  // HTTP 代理
]);
```

### 客户端配置

```php
$config = [
    'sdk_name' => 'my-http-client',
    'timeout' => 30,                         // 默认超时时间
    'max_retries' => 3,                      // 最大重试次数
    'retry_delay' => 1000,                   // 重试延迟（毫秒）
    'logging' => [
        'level' => 'info',                   // 日志级别
        'enabled' => true,                   // 启用日志
    ],
    'http' => [
        'user_agent' => 'PHP-MCP-Client/1.0', // 默认 User-Agent
        'verify_ssl' => true,                // SSL 验证
        'follow_redirects' => true,          // 跟随 HTTP 重定向
    ],
];
```

## 高级用法

### 1. 身份验证

```php
// Bearer Token 认证
$session = $client->connect('http', [
    'url' => 'https://api.example.com/mcp',
    'headers' => [
        'Authorization' => 'Bearer ' . $accessToken,
    ],
]);

// API Key 认证
$session = $client->connect('http', [
    'url' => 'https://api.example.com/mcp',
    'headers' => [
        'X-API-Key' => $apiKey,
        'X-Client-ID' => $clientId,
    ],
]);

// 自定义认证
$session = $client->connect('http', [
    'url' => 'https://api.example.com/mcp',
    'headers' => [
        'Authorization' => 'Custom ' . base64_encode($credentials),
    ],
]);
```

### 2. 错误处理

```php
use Dtyq\PhpMcp\Shared\Exceptions\McpError;
use Dtyq\PhpMcp\Shared\Exceptions\TransportError;
use Dtyq\PhpMcp\Shared\Exceptions\ConnectionError;

try {
    $session = $client->connect('http', [
        'url' => 'https://invalid-server.example.com/mcp',
    ]);
    $session->initialize();
} catch (ConnectionError $e) {
    echo "连接失败：" . $e->getMessage() . "\n";
    echo "请检查服务器 URL 和网络连接\n";
} catch (TransportError $e) {
    echo "传输错误：" . $e->getMessage() . "\n";
    echo "HTTP 状态：" . $e->getCode() . "\n";
} catch (McpError $e) {
    echo "MCP 协议错误：" . $e->getMessage() . "\n";
    echo "错误代码：" . $e->getCode() . "\n";
}

// HTTP 特定错误处理
try {
    $result = $session->callTool('api_call', ['endpoint' => '/users']);
} catch (TransportError $e) {
    $statusCode = $e->getCode();
    switch ($statusCode) {
        case 401:
            echo "认证失败 - 请检查您的凭据\n";
            break;
        case 403:
            echo "访问被禁止 - 权限不足\n";
            break;
        case 404:
            echo "端点未找到\n";
            break;
        case 429:
            echo "速率限制超出 - 请稍等\n";
            break;
        case 500:
            echo "服务器错误 - 请稍后重试\n";
            break;
        default:
            echo "HTTP 错误 {$statusCode}：" . $e->getMessage() . "\n";
    }
}
```

### 3. 会话管理

```php
// 检查会话状态
if ($session->isConnected()) {
    echo "会话处于活动状态\n";
} else {
    echo "会话已断开\n";
}

// 获取会话信息
echo "会话 ID：" . $session->getSessionId() . "\n";

// 如果需要重新连接
if (!$session->isConnected()) {
    try {
        $session->reconnect();
        echo "重新连接成功\n";
    } catch (Exception $e) {
        echo "重新连接失败：" . $e->getMessage() . "\n";
    }
}

// 正确关闭会话
$client->close();
```

### 4. 批量操作

```php
// 高效执行多个操作
$operations = [
    ['type' => 'tool', 'name' => 'web_info', 'args' => ['url' => 'https://example.com']],
    ['type' => 'tool', 'name' => 'api_call', 'args' => ['endpoint' => '/status']],
    ['type' => 'resource', 'uri' => 'config://app/settings'],
];

$results = [];
foreach ($operations as $op) {
    try {
        if ($op['type'] === 'tool') {
            $result = $session->callTool($op['name'], $op['args']);
            $results[] = ['type' => 'tool', 'name' => $op['name'], 'result' => $result];
        } elseif ($op['type'] === 'resource') {
            $result = $session->readResource($op['uri']);
            $results[] = ['type' => 'resource', 'uri' => $op['uri'], 'result' => $result];
        }
    } catch (Exception $e) {
        $results[] = ['type' => 'error', 'operation' => $op, 'error' => $e->getMessage()];
    }
}

// 处理结果
foreach ($results as $result) {
    if ($result['type'] === 'error') {
        echo "操作错误：" . $result['error'] . "\n";
    } else {
        echo "成功：" . $result['type'] . "\n";
    }
}
```

## 完整示例

这是一个完整的 HTTP 客户端实现：

```php
<?php
declare(strict_types=1);

require_once 'vendor/autoload.php';

use Dtyq\PhpMcp\Client\McpClient;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Dtyq\PhpMcp\Types\Content\TextContent;
use Dtyq\PhpMcp\Types\Resources\TextResourceContents;

// 容器设置
$container = new class implements \Psr\Container\ContainerInterface {
    private array $services = [];

    public function __construct() {
        $this->services[\Psr\Log\LoggerInterface::class] = new class extends \Psr\Log\AbstractLogger {
            public function log($level, $message, array $context = []): void {
                $timestamp = date('Y-m-d H:i:s');
                $contextStr = empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_SLASHES);
                file_put_contents('http-client.log', "[{$timestamp}] {$level}: {$message}{$contextStr}\n", FILE_APPEND);
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

echo "=== PHP MCP HTTP 客户端演示 ===\n";

try {
    // 创建客户端
    $app = new Application($container, ['sdk_name' => 'demo-http-client']);
    $client = new McpClient('demo-http-client', '1.0.0', $app);

    // 连接到 HTTP 服务器
    echo "1. 连接到 HTTP 服务器...\n";
    $session = $client->connect('http', [
        'url' => 'https://your-mcp-server.com/mcp',
        'headers' => [
            'Authorization' => 'Bearer your-token-here',
            'Content-Type' => 'application/json',
        ],
        'timeout' => 30,
    ]);

    $session->initialize();
    echo "   ✓ 连接并初始化完成\n";

    // 测试工具
    echo "\n2. 测试工具...\n";
    $tools = $session->listTools();
    echo "   可用工具：" . count($tools->getTools()) . "\n";

    if (count($tools->getTools()) > 0) {
        $firstTool = $tools->getTools()[0];
        echo "   测试工具：" . $firstTool->getName() . "\n";
        
        // 调用 Web 特定工具
        if ($firstTool->getName() === 'web_info') {
            $result = $session->callTool('web_info', ['include_headers' => true]);
            $content = $result->getContent();
            if (!empty($content) && $content[0] instanceof TextContent) {
                $data = json_decode($content[0]->getText(), true);
                echo "   服务器信息：" . ($data['server_info']['software'] ?? '未知') . "\n";
            }
        }
    }

    // 测试资源
    echo "\n3. 测试资源...\n";
    $resources = $session->listResources();
    echo "   可用资源：" . count($resources->getResources()) . "\n";

    if (count($resources->getResources()) > 0) {
        $firstResource = $resources->getResources()[0];
        echo "   测试资源：" . $firstResource->getUri() . "\n";
        
        $resourceResult = $session->readResource($firstResource->getUri());
        foreach ($resourceResult->getContents() as $content) {
            if ($content instanceof TextResourceContents) {
                $text = $content->getText();
                if ($text !== null) {
                    echo "   资源类型：" . $content->getMimeType() . "\n";
                    echo "   内容预览：" . substr($text, 0, 100) . "...\n";
                }
            }
        }
    }

    // 显示统计信息
    echo "\n4. 会话统计信息...\n";
    $stats = $client->getStats();
    echo "   连接尝试次数：" . $stats->getConnectionAttempts() . "\n";
    echo "   连接错误次数：" . $stats->getConnectionErrors() . "\n";
    echo "   状态：" . $stats->getStatus() . "\n";

} catch (Exception $e) {
    echo "\n❌ 错误：" . $e->getMessage() . "\n";
    echo "堆栈跟踪：\n" . $e->getTraceAsString() . "\n";
} finally {
    // 始终关闭客户端
    if (isset($client)) {
        echo "\n5. 关闭客户端...\n";
        $client->close();
        echo "   ✓ 客户端已关闭\n";
    }
}

echo "\n=== 演示完成 ===\n";
```

## 测试 HTTP 服务器

### 1. cURL 测试

```bash
# 测试服务器可用性
curl -X POST https://your-server.com/mcp \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer your-token" \
  -d '{
    "jsonrpc": "2.0",
    "method": "tools/list",
    "id": 1
  }'

# 测试工具调用
curl -X POST https://your-server.com/mcp \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer your-token" \
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

### 2. Postman 集合

为测试您的 MCP 服务器创建 Postman 集合：

```json
{
  "info": {
    "name": "MCP 服务器测试",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "列出工具",
      "request": {
        "method": "POST",
        "header": [
          {
            "key": "Content-Type",
            "value": "application/json"
          },
          {
            "key": "Authorization",
            "value": "Bearer {{token}}"
          }
        ],
        "body": {
          "mode": "raw",
          "raw": "{\n  \"jsonrpc\": \"2.0\",\n  \"method\": \"tools/list\",\n  \"id\": 1\n}"
        },
        "url": {
          "raw": "{{base_url}}/mcp",
          "host": ["{{base_url}}"],
          "path": ["mcp"]
        }
      }
    }
  ]
}
```

## 最佳实践

1. **身份验证**：在生产环境中始终使用安全的身份验证方法
2. **错误处理**：为网络问题实施全面的错误处理
3. **超时设置**：为不同类型的操作设置适当的超时时间
4. **日志记录**：记录所有 HTTP 请求和响应以便调试
5. **速率限制**：尊重服务器速率限制并实施客户端节流
6. **SSL 验证**：在生产环境中始终验证 SSL 证书

## 下一步

- [STDIO 客户端指南](./stdio-client.md) - 了解 STDIO 传输
- [客户端示例](./examples.md) - 查看完整实现示例
- [服务端文档](../server/http-server.md) - 了解如何创建 HTTP 服务器 