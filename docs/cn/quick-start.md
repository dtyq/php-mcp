# 快速开始指南

只需5分钟即可开始使用 PHP MCP！本指南将引导您创建第一个 MCP 服务器和客户端。

## 前置要求

- PHP 7.4 或更高版本
- 已安装 Composer
- PHP 基础知识

## 安装

通过 Composer 安装 PHP MCP：

```bash
composer require dtyq/php-mcp
```

## 步骤 1：创建您的第一个服务器

创建一个名为 `my-server.php` 的文件：

```php
<?php
require_once 'vendor/autoload.php';

use Dtyq\PhpMcp\Server\McpServer;
use Dtyq\PhpMcp\Server\FastMcp\Tools\RegisteredTool;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Dtyq\PhpMcp\Types\Tools\Tool;

// 简单的 DI 容器
$container = new class implements \Psr\Container\ContainerInterface {
    private array $services = [];
    
    public function __construct() {
        $this->services[\Psr\Log\LoggerInterface::class] = new \Psr\Log\NullLogger();
        $this->services[\Psr\EventDispatcher\EventDispatcherInterface::class] = 
            new class implements \Psr\EventDispatcher\EventDispatcherInterface {
                public function dispatch(object $event): object { return $event; }
            };
    }
    
    public function get($id) { return $this->services[$id]; }
    public function has($id): bool { return isset($this->services[$id]); }
};

// 创建一个简单的问候工具
function createGreetingTool(): RegisteredTool {
    $tool = new Tool(
        'greet',
        [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string', 'description' => '要问候的姓名'],
            ],
            'required' => ['name'],
        ],
        '按姓名问候某人'
    );

    return new RegisteredTool($tool, function (array $args): string {
        $name = $args['name'] ?? '世界';
        return "你好，{$name}！欢迎使用 PHP MCP！";
    });
}

// 创建和配置服务器
$config = ['sdk_name' => 'my-first-server'];
$app = new Application($container, $config);
$server = new McpServer('my-first-server', '1.0.0', $app);

// 注册工具并启动服务器
$server
    ->registerTool(createGreetingTool())
    ->stdio();
```

## 步骤 2：创建您的第一个客户端

创建一个名为 `my-client.php` 的文件：

```php
<?php
require_once 'vendor/autoload.php';

use Dtyq\PhpMcp\Client\McpClient;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Dtyq\PhpMcp\Types\Content\TextContent;

// 简单的 DI 容器
$container = new class implements \Psr\Container\ContainerInterface {
    private array $services = [];
    
    public function __construct() {
        $this->services[\Psr\Log\LoggerInterface::class] = new \Psr\Log\NullLogger();
        $this->services[\Psr\EventDispatcher\EventDispatcherInterface::class] = 
            new class implements \Psr\EventDispatcher\EventDispatcherInterface {
                public function dispatch(object $event): object { return $event; }
            };
    }
    
    public function get($id) { return $this->services[$id]; }
    public function has($id): bool { return isset($this->services[$id]); }
};

echo "=== 我的第一个 MCP 客户端 ===\n";

try {
    // 创建客户端
    $app = new Application($container, ['sdk_name' => 'my-first-client']);
    $client = new McpClient('my-first-client', '1.0.0', $app);

    // 连接到我们的服务器
    echo "1. 连接到服务器...\n";
    $session = $client->connect('stdio', [
        'command' => 'php',
        'args' => [__DIR__ . '/my-server.php'],
    ]);

    $session->initialize();
    echo "   ✓ 连接成功！\n";

    // 列出可用工具
    echo "\n2. 可用工具：\n";
    $tools = $session->listTools();
    foreach ($tools->getTools() as $tool) {
        echo "   - {$tool->getName()}: {$tool->getDescription()}\n";
    }

    // 调用我们的问候工具
    echo "\n3. 调用问候工具：\n";
    $result = $session->callTool('greet', ['name' => 'PHP 开发者']);
    
    foreach ($result->getContent() as $content) {
        if ($content instanceof TextContent) {
            echo "   " . $content->getText() . "\n";
        }
    }

    echo "\n✅ 成功！您的第一次 MCP 交互已完成。\n";

} catch (Exception $e) {
    echo "\n❌ 错误：" . $e->getMessage() . "\n";
} finally {
    if (isset($client)) {
        $client->close();
    }
}
```

## 步骤 3：运行您的第一个 MCP 应用程序

1. **直接测试服务器：**
   ```bash
   php my-server.php
   ```
   服务器将启动并等待 JSON-RPC 输入。

2. **运行客户端与服务器交互：**
   ```bash
   php my-client.php
   ```

您应该看到如下输出：
```
=== 我的第一个 MCP 客户端 ===
1. 连接到服务器...
   ✓ 连接成功！

2. 可用工具：
   - greet: 按姓名问候某人

3. 调用问候工具：
   你好，PHP 开发者！欢迎使用 PHP MCP！

✅ 成功！您的第一次 MCP 交互已完成。
```

## 下一步做什么？

现在您有了一个可工作的 MCP 设置，这里有一些后续步骤：

### 1. 添加更多工具

用额外的工具扩展您的服务器：

```php
// 添加计算器工具
function createCalculatorTool(): RegisteredTool {
    $tool = new Tool(
        'add',
        [
            'type' => 'object',
            'properties' => [
                'a' => ['type' => 'number'],
                'b' => ['type' => 'number'],
            ],
            'required' => ['a', 'b'],
        ],
        '两个数字相加'
    );

    return new RegisteredTool($tool, function (array $args): array {
        return [
            'result' => ($args['a'] ?? 0) + ($args['b'] ?? 0),
            'operation' => '加法',
        ];
    });
}

// 在您的服务器中注册它
$server->registerTool(createCalculatorTool());
```

### 2. 添加资源

通过资源提供数据访问：

```php
use Dtyq\PhpMcp\Server\FastMcp\Resources\RegisteredResource;
use Dtyq\PhpMcp\Types\Resources\Resource;
use Dtyq\PhpMcp\Types\Resources\TextResourceContents;

function createTimeResource(): RegisteredResource {
    $resource = new Resource(
        'time://current',
        '当前时间',
        '获取当前时间',
        'text/plain'
    );

    return new RegisteredResource($resource, function (string $uri): TextResourceContents {
        return new TextResourceContents($uri, date('Y-m-d H:i:s'), 'text/plain');
    });
}

$server->registerResource(createTimeResource());
```

### 3. 尝试 HTTP 传输

对于 Web 应用程序，使用 HTTP 传输：

```php
// 在您的 HTTP 服务器端点中（例如，index.php）
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$headers = getallheaders() ?: [];
$body = file_get_contents('php://input');

$request = new GuzzleHttp\Psr7\Request($method, $path, $headers, $body);

// 处理并发送响应
$response = $server->http($request);
http_response_code($response->getStatusCode());
foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header("{$name}: {$value}");
    }
}
echo $response->getBody()->getContents();
```

### 4. 错误处理

添加适当的错误处理：

```php
try {
    $result = $session->callTool('greet', ['name' => '小明']);
} catch (\Dtyq\PhpMcp\Shared\Exceptions\McpError $e) {
    echo "MCP 错误：" . $e->getMessage() . "\n";
    echo "错误代码：" . $e->getCode() . "\n";
} catch (\Exception $e) {
    echo "一般错误：" . $e->getMessage() . "\n";
}
```

### 5. 生产配置

对于生产使用，添加适当的日志记录和配置：

```php
// 具有文件日志记录的增强容器
$container = new class implements \Psr\Container\ContainerInterface {
    private array $services = [];
    
    public function __construct() {
        $this->services[\Psr\Log\LoggerInterface::class] = new class extends \Psr\Log\AbstractLogger {
            public function log($level, $message, array $context = []): void {
                $timestamp = date('Y-m-d H:i:s');
                $contextStr = empty($context) ? '' : ' ' . json_encode($context);
                file_put_contents('app.log', "[{$timestamp}] {$level}: {$message}{$contextStr}\n", FILE_APPEND);
            }
        };
        // ... 其余容器设置
    }
    
    public function get($id) { return $this->services[$id]; }
    public function has($id): bool { return isset($this->services[$id]); }
};
```

## 常见问题和解决方案

### 问题："找不到命令"错误
**解决方案：** 确保 PHP 在您的 PATH 中，并且服务器文件路径正确。

### 问题："连接超时"错误
**解决方案：** 在您的客户端配置中增加超时时间：
```php
$session = $client->connect('stdio', [
    'command' => 'php',
    'args' => ['my-server.php'],
    'timeout' => 60, // 将超时时间增加到 60 秒
]);
```

### 问题："JSON-RPC 解析错误"
**解决方案：** 检查您的服务器格式是否正确，没有输出额外内容。

## 了解更多

- **[STDIO 服务端指南](./server/stdio-server.md)** - 详细的服务器文档
- **[STDIO 客户端指南](./client/stdio-client.md)** - 详细的客户端文档
- **[HTTP 服务端指南](./server/http-server.md)** - 基于 Web 的 MCP 服务器
- **[API 参考](./api-reference.md)** - 完整的 API 文档
- **[示例](./server/examples.md)** - 更全面的示例

## 社区和支持

- **GitHub 仓库：** [https://github.com/dtyq/php-mcp](https://github.com/dtyq/php-mcp)
- **问题：** [报告错误或请求功能](https://github.com/dtyq/php-mcp/issues)
- **讨论：** [提问和分享想法](https://github.com/dtyq/php-mcp/discussions)

祝您使用 PHP MCP 编程愉快！🚀 