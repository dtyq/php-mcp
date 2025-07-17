# STDIO 服务端指南

## 概述

STDIO（标准输入输出）传输是 PHP MCP 支持的主要通信方式之一。它允许 MCP 服务器通过标准输入和输出流与客户端通信，非常适合命令行工具、进程生成和开发环境。

## 核心特性

- **简单设置**：需要最少的配置
- **进程通信**：通过 stdin/stdout 直接通信
- **开发友好**：易于调试和测试
- **跨平台**：在所有支持 PHP 的平台上工作

## 快速开始

### 1. 基础服务器设置

```php
<?php
require_once 'vendor/autoload.php';

use Dtyq\PhpMcp\Server\McpServer;
use Dtyq\PhpMcp\Shared\Kernel\Application;

// 创建简单的 DI 容器
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

// 配置
$config = [
    'sdk_name' => 'my-mcp-server',
    'transports' => [
        'stdio' => [
            'enabled' => true,
            'buffer_size' => 8192,
            'timeout' => 30,
            'validate_messages' => true,
        ],
    ],
];

// 创建应用程序和服务器
$app = new Application($container, $config);
$server = new McpServer('my-server', '1.0.0', $app);

// 启动 STDIO 传输
$server->stdio();
```

### 2. 添加工具

工具是客户端可以调用来执行操作的函数：

```php
use Dtyq\PhpMcp\Server\FastMcp\Tools\RegisteredTool;
use Dtyq\PhpMcp\Types\Tools\Tool;

function createEchoTool(): RegisteredTool {
    $tool = new Tool(
        'echo',
        [
            'type' => 'object',
            'properties' => [
                'message' => ['type' => 'string', 'description' => '要回显的消息'],
            ],
            'required' => ['message'],
        ],
        '回显提供的消息'
    );

    return new RegisteredTool($tool, function (array $args): string {
        return 'Echo: ' . ($args['message'] ?? '');
    });
}

// 注册工具
$server->registerTool(createEchoTool());
```

### 3. 添加资源

资源提供对数据或内容的访问：

```php
use Dtyq\PhpMcp\Server\FastMcp\Resources\RegisteredResource;
use Dtyq\PhpMcp\Types\Resources\Resource;
use Dtyq\PhpMcp\Types\Resources\TextResourceContents;

function createSystemInfoResource(): RegisteredResource {
    $resource = new Resource(
        'system://info',
        '系统信息',
        '当前系统信息',
        'application/json'
    );

    return new RegisteredResource($resource, function (string $uri): TextResourceContents {
        $info = [
            'php_version' => PHP_VERSION,
            'os' => PHP_OS,
            'memory_usage' => memory_get_usage(true),
            'timestamp' => date('c'),
        ];

        return new TextResourceContents($uri, json_encode($info, JSON_PRETTY_PRINT), 'application/json');
    });
}

// 注册资源
$server->registerResource(createSystemInfoResource());
```

### 4. 添加提示

提示提供模板化的对话启动器：

```php
use Dtyq\PhpMcp\Server\FastMcp\Prompts\RegisteredPrompt;
use Dtyq\PhpMcp\Types\Prompts\Prompt;
use Dtyq\PhpMcp\Types\Prompts\PromptArgument;
use Dtyq\PhpMcp\Types\Prompts\GetPromptResult;
use Dtyq\PhpMcp\Types\Prompts\PromptMessage;
use Dtyq\PhpMcp\Types\Content\TextContent;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;

function createGreetingPrompt(): RegisteredPrompt {
    $prompt = new Prompt(
        'greeting',
        '生成个性化问候语',
        [
            new PromptArgument('name', '人员姓名', true),
            new PromptArgument('language', '问候语言', false),
        ]
    );

    return new RegisteredPrompt($prompt, function (array $args): GetPromptResult {
        $name = $args['name'] ?? '世界';
        $language = $args['language'] ?? 'chinese';

        $greetings = [
            'chinese' => "你好，{$name}！今天过得怎么样？",
            'english' => "Hello, {$name}! How are you today?",
            'spanish' => "¡Hola, {$name}! ¿Cómo estás hoy?",
        ];

        $greeting = $greetings[$language] ?? $greetings['chinese'];
        $message = new PromptMessage(ProtocolConstants::ROLE_USER, new TextContent($greeting));

        return new GetPromptResult("给 {$name} 的问候", [$message]);
    });
}

// 注册提示
$server->registerPrompt(createGreetingPrompt());
```

## 配置选项

### STDIO 传输配置

```php
$config = [
    'transports' => [
        'stdio' => [
            'enabled' => true,              // 启用 STDIO 传输
            'buffer_size' => 8192,          // 读写缓冲区大小
            'timeout' => 30,                // 超时时间（秒）
            'validate_messages' => true,    // 验证 JSON-RPC 消息
            'encoding' => 'utf-8',          // 字符编码
        ],
    ],
];
```

### 日志配置

```php
$config = [
    'logging' => [
        'level' => 'info',              // 日志级别：debug, info, warning, error
        'handlers' => [
            'file' => [
                'enabled' => true,
                'path' => '/var/log/mcp-server.log',
            ],
        ],
    ],
];
```

## 完整示例

这是一个完整的 STDIO 服务器实现：

```php
<?php
declare(strict_types=1);

require_once 'vendor/autoload.php';

use Dtyq\PhpMcp\Server\McpServer;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Dtyq\PhpMcp\Server\FastMcp\Tools\RegisteredTool;
use Dtyq\PhpMcp\Types\Tools\Tool;

// 简单容器实现
$container = new class implements \Psr\Container\ContainerInterface {
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

// 配置
$config = [
    'sdk_name' => 'complete-stdio-server',
    'transports' => [
        'stdio' => [
            'enabled' => true,
            'buffer_size' => 8192,
            'timeout' => 30,
            'validate_messages' => true,
        ],
    ],
];

// 创建计算器工具
function createCalculatorTool(): RegisteredTool {
    $tool = new Tool(
        'calculate',
        [
            'type' => 'object',
            'properties' => [
                'operation' => ['type' => 'string', 'enum' => ['add', 'subtract', 'multiply', 'divide']],
                'a' => ['type' => 'number'],
                'b' => ['type' => 'number'],
            ],
            'required' => ['operation', 'a', 'b'],
        ],
        '执行数学运算'
    );

    return new RegisteredTool($tool, function (array $args): array {
        $a = $args['a'] ?? 0;
        $b = $args['b'] ?? 0;
        $operation = $args['operation'] ?? 'add';

        switch ($operation) {
            case 'add': $result = $a + $b; break;
            case 'subtract': $result = $a - $b; break;
            case 'multiply': $result = $a * $b; break;
            case 'divide':
                if ($b == 0) throw new \Dtyq\PhpMcp\Shared\Exceptions\ValidationError('除零错误');
                $result = $a / $b;
                break;
            default:
                throw new \Dtyq\PhpMcp\Shared\Exceptions\ValidationError('未知操作: ' . $operation);
        }

        return [
            'operation' => $operation,
            'operands' => [$a, $b],
            'result' => $result,
        ];
    });
}

// 创建应用程序和服务器
$app = new Application($container, $config);
$server = new McpServer('complete-stdio-server', '1.0.0', $app);

// 注册组件
$server
    ->registerTool(createCalculatorTool())
    ->stdio(); // 启动 STDIO 传输
```

## 运行服务器

### 命令行用法

将你的服务器代码保存到文件（例如 `server.php`）并运行：

```bash
php server.php
```

服务器将启动并在 stdin/stdout 上监听 JSON-RPC 消息。

### 使用客户端测试

你可以使用提供的客户端示例或任何兼容 MCP 的客户端来测试你的服务器：

```bash
# 在另一个终端中
php client.php
```

## 错误处理

### 常见问题

1. **无效的 JSON-RPC 格式**
   ```php
   // 服务器自动验证并使用适当的错误代码响应
   ```

2. **工具执行错误**
   ```php
   return new RegisteredTool($tool, function (array $args) {
       try {
           // 你的工具逻辑在这里
           return $result;
       } catch (Exception $e) {
           throw new \Dtyq\PhpMcp\Shared\Exceptions\McpError(
               '工具执行失败: ' . $e->getMessage(),
               -32000
           );
       }
   });
   ```

3. **资源访问错误**
   ```php
   return new RegisteredResource($resource, function (string $uri) {
       if (!$this->hasAccess($uri)) {
           throw new \Dtyq\PhpMcp\Shared\Exceptions\McpError(
               '拒绝访问资源: ' . $uri,
               -32001
           );
       }
       // 返回资源内容
   });
   ```

## 最佳实践

1. **验证输入**：始终验证工具参数和资源 URI
2. **处理错误**：提供有意义的错误消息
3. **资源管理**：正确清理资源
4. **日志记录**：记录重要事件以便调试
5. **测试**：使用不同的客户端测试你的服务器

## 下一步

- [HTTP 服务端指南](./http-server.md) - 了解 HTTP 传输
- [服务端示例](./examples.md) - 查看完整实现示例
- [客户端文档](../client/stdio-client.md) - 了解如何创建客户端 