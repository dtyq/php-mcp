# STDIO 客户端指南

## 概述

STDIO（标准输入输出）客户端提供了一种简单的方式来使用标准输入和输出流与 MCP 服务器通信。这种传输方式非常适合命令行应用程序、进程自动化和开发测试。

在本指南中，我们将使用 **Sequential Thinking MCP 服务器** 作为主要示例。该服务器通过逐步推理提供结构化的问题解决方法，非常适合演示 MCP 功能。

## 核心特性

- **进程生成**：自动生成和管理服务器进程
- **简单通信**：通过 stdin/stdout 直接通信
- **开发友好**：易于调试和测试
- **跨平台**：在所有支持 PHP 的平台上工作

### Sequential Thinking MCP 服务器特性

我们在示例中使用的 Sequential Thinking 服务器提供：

- **结构化问题解决**：将复杂问题分解为可管理的步骤
- **思考修订**：随着理解的深入，完善和改进先前的思考
- **分支逻辑**：探索替代推理路径
- **动态调整**：根据需要修改思考步骤的总数
- **上下文保持**：在多个思考步骤中保持上下文

## 快速开始

### 1. 基础客户端设置

```php
<?php
require_once 'vendor/autoload.php';

use Dtyq\PhpMcp\Client\McpClient;
use Dtyq\PhpMcp\Shared\Kernel\Application;

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
$config = ['sdk_name' => 'my-mcp-client'];

// 创建应用程序和客户端
$app = new Application($container, $config);
$client = new McpClient('my-client', '1.0.0', $app);

// ✅ 推荐：使用类型化配置的快捷方法
use Dtyq\PhpMcp\Client\Configuration\StdioConfig;

// 连接到 Sequential Thinking MCP 服务器
$config = new StdioConfig('npx', ['-y', '@modelcontextprotocol/server-sequential-thinking']);
$session = $client->stdio($config);

// 初始化会话
$session->initialize();

echo "已连接到 Sequential Thinking MCP 服务器！\n";
```

### 2. 连接到不同的服务器

```php
// ✅ 推荐：使用类型化配置的快捷方法
use Dtyq\PhpMcp\Client\Configuration\StdioConfig;

// 连接到 Sequential Thinking MCP 服务器（推荐用于测试）
$sequentialConfig = new StdioConfig('npx', ['-y', '@modelcontextprotocol/server-sequential-thinking']);
$session = $client->stdio($sequentialConfig);

// 连接到其他 MCP 服务器
$nodeConfig = new StdioConfig('node', ['path/to/server.js']);
$session = $client->stdio($nodeConfig);

// 连接到 Python MCP 服务器
$pythonConfig = new StdioConfig('python', ['path/to/server.py']);
$session = $client->stdio($pythonConfig);

// 使用环境变量连接（禁用思考日志）
$customConfig = new StdioConfig('npx', ['-y', '@modelcontextprotocol/server-sequential-thinking']);
$customConfig->setEnv([
    'DISABLE_THOUGHT_LOGGING' => 'true',
]);
$session = $client->stdio($customConfig);
```

**传统方法（已弃用）**：
```php
// ⚠️ 此方法已弃用，请使用 stdio() 快捷方法
$session = $client->connect('stdio', [
    'command' => 'node',
    'args' => ['path/to/server.js'],
]);
```

### 3. 使用 Sequential Thinking 工具

```php
// 列出可用工具
$toolsResult = $session->listTools();
echo "可用工具：\n";
foreach ($toolsResult->getTools() as $tool) {
    echo "- {$tool->getName()}: {$tool->getDescription()}\n";
}

// 使用 Sequential Thinking 进行问题解决
try {
    // 开始第一个思考步骤
    $result = $session->callTool('sequential_thinking', [
        'thought' => '让我来分解这个复杂问题：如何构建一个可扩展的 Web 应用程序？',
        'nextThoughtNeeded' => true,
        'thoughtNumber' => 1,
        'totalThoughts' => 5,
    ]);
    
    echo "第一个思考步骤已处理：\n";
    foreach ($result->getContent() as $content) {
        if ($content instanceof \Dtyq\PhpMcp\Types\Content\TextContent) {
            echo $content->getText() . "\n";
        }
    }
    
    // 继续下一个思考步骤
    $result = $session->callTool('sequential_thinking', [
        'thought' => '我需要考虑架构：微服务 vs 单体应用架构。',
        'nextThoughtNeeded' => true,
        'thoughtNumber' => 2,
        'totalThoughts' => 5,
    ]);
    
    // 分支到替代思考路径
    $result = $session->callTool('sequential_thinking', [
        'thought' => '实际上，让我探索一个不同的方法 - 无服务器架构。',
        'nextThoughtNeeded' => true,
        'thoughtNumber' => 3,
        'totalThoughts' => 6,
        'branchFromThought' => 2,
        'branchId' => 'serverless-branch',
        'needsMoreThoughts' => true,
    ]);
    
    echo "顺序思考过程完成！\n";
    
} catch (Exception $e) {
    echo "顺序思考失败：" . $e->getMessage() . "\n";
}

// 简单工具调用示例
try {
    $result = $session->callTool('sequential_thinking', [
        'thought' => '选择数据库的关键因素有哪些？',
        'nextThoughtNeeded' => false,
        'thoughtNumber' => 1,
        'totalThoughts' => 1,
    ]);
    
    echo "单个思考结果：\n";
    foreach ($result->getContent() as $content) {
        if ($content instanceof \Dtyq\PhpMcp\Types\Content\TextContent) {
            echo $content->getText() . "\n";
        }
    }
} catch (Exception $e) {
    echo "工具调用失败：" . $e->getMessage() . "\n";
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

// 读取资源
try {
    $resourceResult = $session->readResource('system://info');
    foreach ($resourceResult->getContents() as $content) {
        if ($content instanceof TextResourceContents) {
            $text = $content->getText();
            if ($text !== null) {
                $data = json_decode($text, true);
                echo "系统信息：\n";
                echo "- PHP 版本：" . ($data['php_version'] ?? '未知') . "\n";
                echo "- 操作系统：" . ($data['os'] ?? '未知') . "\n";
                echo "- 内存使用：" . number_format((float)($data['memory_usage'] ?? 0)) . " 字节\n";
            }
        }
    }
} catch (Exception $e) {
    echo "资源读取失败：" . $e->getMessage() . "\n";
}

// 使用参数读取资源模板
try {
    $userProfile = $session->readResource('user://admin/profile');
    foreach ($userProfile->getContents() as $content) {
        if ($content instanceof TextResourceContents) {
            $text = $content->getText();
            if ($text !== null) {
                $profile = json_decode($text, true);
                echo "用户档案：\n";
                echo "- 用户 ID：" . ($profile['userId'] ?? '未知') . "\n";
                echo "- 角色：" . ($profile['role'] ?? '未知') . "\n";
                echo "- 邮箱：" . ($profile['email'] ?? '未知') . "\n";
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

// 获取提示
try {
    $promptResult = $session->getPrompt('greeting', [
        'name' => '小明',
        'language' => 'chinese',
    ]);
    
    echo "提示：" . ($promptResult->getDescription() ?? '无描述') . "\n";
    echo "消息：\n";
    
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

### STDIO 传输配置

```php
$session = $client->connect('stdio', [
    'command' => 'php',                    // 要执行的命令
    'args' => ['server.php'],              // 命令参数
    'cwd' => '/path/to/working/directory', // 工作目录
    'env' => [                             // 环境变量
        'LOG_LEVEL' => 'debug',
        'CONFIG_PATH' => '/etc/config',
    ],
    'timeout' => 30,                       // 超时时间（秒）
    'buffer_size' => 8192,                 // I/O 缓冲区大小
    'validate_messages' => true,           // 验证 JSON-RPC 消息
]);
```

### 客户端配置

```php
$config = [
    'sdk_name' => 'my-mcp-client',
    'timeout' => 30,                       // 默认超时时间
    'max_retries' => 3,                    // 最大重试次数
    'retry_delay' => 1000,                 // 重试延迟（毫秒）
    'logging' => [
        'level' => 'info',                 // 日志级别
        'enabled' => true,                 // 启用日志
    ],
];
```

## 高级用法

### 1. 错误处理

```php
use Dtyq\PhpMcp\Shared\Exceptions\McpError;
use Dtyq\PhpMcp\Shared\Exceptions\TransportError;
use Dtyq\PhpMcp\Shared\Exceptions\ConnectionError;

try {
    $session = $client->connect('stdio', [
        'command' => 'nonexistent-command',
        'args' => [],
    ]);
    $session->initialize();
} catch (ConnectionError $e) {
    echo "连接失败：" . $e->getMessage() . "\n";
    echo "请确保服务器命令正确且可执行\n";
} catch (TransportError $e) {
    echo "传输错误：" . $e->getMessage() . "\n";
} catch (McpError $e) {
    echo "MCP 协议错误：" . $e->getMessage() . "\n";
    echo "错误代码：" . $e->getCode() . "\n";
}

// 工具调用错误处理
try {
    $result = $session->callTool('nonexistent_tool', []);
} catch (McpError $e) {
    if ($e->getCode() === -32601) {
        echo "找不到工具\n";
    } else {
        echo "工具错误：" . $e->getMessage() . "\n";
    }
}
```

### 2. 会话管理

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

### 3. 批量操作

```php
// 高效执行多个操作
$operations = [
    ['type' => 'tool', 'name' => 'echo', 'args' => ['message' => '第一个']],
    ['type' => 'tool', 'name' => 'echo', 'args' => ['message' => '第二个']],
    ['type' => 'resource', 'uri' => 'system://info'],
];

foreach ($operations as $op) {
    try {
        if ($op['type'] === 'tool') {
            $result = $session->callTool($op['name'], $op['args']);
            echo "工具 '{$op['name']}' 结果：";
            // 处理结果...
        } elseif ($op['type'] === 'resource') {
            $result = $session->readResource($op['uri']);
            echo "资源 '{$op['uri']}' 内容：";
            // 处理结果...
        }
    } catch (Exception $e) {
        echo "操作失败：" . $e->getMessage() . "\n";
        continue;
    }
}
```

## 完整示例

这是一个完整的 STDIO 客户端实现：

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
                file_put_contents('client.log', "[{$timestamp}] {$level}: {$message}{$contextStr}\n", FILE_APPEND);
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

echo "=== PHP MCP 客户端演示 ===\n";

try {
    // 创建客户端
    $app = new Application($container, ['sdk_name' => 'demo-client']);
    $client = new McpClient('demo-client', '1.0.0', $app);

    // 连接到服务器
    echo "1. 连接到服务器...\n";
    $session = $client->connect('stdio', [
        'command' => 'php',
        'args' => [__DIR__ . '/server.php'],
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
        
        // 如果可用，调用 echo 工具
        if ($firstTool->getName() === 'echo') {
            $result = $session->callTool('echo', ['message' => '来自演示的问候！']);
            $content = $result->getContent();
            if (!empty($content) && $content[0] instanceof TextContent) {
                echo "   结果：" . $content[0]->getText() . "\n";
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
                    echo "   内容预览：" . substr($text, 0, 100) . "...\n";
                }
            }
        }
    }

    // 测试提示
    echo "\n4. 测试提示...\n";
    $prompts = $session->listPrompts();
    echo "   可用提示：" . count($prompts->getPrompts()) . "\n";

    if (count($prompts->getPrompts()) > 0) {
        $firstPrompt = $prompts->getPrompts()[0];
        echo "   测试提示：" . $firstPrompt->getName() . "\n";
        
        if ($firstPrompt->getName() === 'greeting') {
            $promptResult = $session->getPrompt('greeting', [
                'name' => '演示用户',
                'language' => 'chinese',
            ]);
            
            echo "   提示描述：" . ($promptResult->getDescription() ?? '无') . "\n";
        }
    }

    // 显示统计信息
    echo "\n5. 会话统计信息...\n";
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
        echo "\n6. 关闭客户端...\n";
        $client->close();
        echo "   ✓ 客户端已关闭\n";
    }
}

echo "\n=== 演示完成 ===\n";
```

## 测试和调试

### 1. 启用调试日志

```php
// 增强日志容器
$container = new class implements \Psr\Container\ContainerInterface {
    private array $services = [];

    public function __construct() {
        $this->services[\Psr\Log\LoggerInterface::class] = new class extends \Psr\Log\AbstractLogger {
            public function log($level, $message, array $context = []): void {
                $timestamp = date('Y-m-d H:i:s.u');
                $contextStr = empty($context) ? '' : "\n  上下文：" . json_encode($context, JSON_PRETTY_PRINT);
                
                $logEntry = "[{$timestamp}] {$level}: {$message}{$contextStr}\n";
                
                // 记录到文件和控制台
                file_put_contents('debug.log', $logEntry, FILE_APPEND);
                if (in_array($level, ['error', 'warning'])) {
                    echo $logEntry;
                }
            }
        };
        // ... 其余容器设置
    }
};
```

### 2. 连接测试

```php
function testConnection($command, $args = []) {
    // 测试命令是否可执行
    $fullCommand = $command . ' ' . implode(' ', array_map('escapeshellarg', $args));
    
    echo "测试命令：{$fullCommand}\n";
    
    $process = proc_open($fullCommand, [
        0 => ['pipe', 'r'],  // stdin
        1 => ['pipe', 'w'],  // stdout
        2 => ['pipe', 'w'],  // stderr
    ], $pipes);
    
    if (!is_resource($process)) {
        echo "❌ 启动进程失败\n";
        return false;
    }
    
    // 发送简单测试消息
    $testMessage = json_encode([
        'jsonrpc' => '2.0',
        'method' => 'initialize',
        'id' => 1,
    ]);
    
    fwrite($pipes[0], $testMessage . "\n");
    fclose($pipes[0]);
    
    // 读取响应
    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    
    fclose($pipes[1]);
    fclose($pipes[2]);
    
    $exitCode = proc_close($process);
    
    echo "退出代码：{$exitCode}\n";
    if (!empty($output)) {
        echo "输出：{$output}\n";
    }
    if (!empty($error)) {
        echo "错误：{$error}\n";
    }
    
    return $exitCode === 0;
}

// 测试服务器连接性
testConnection('php', ['server.php']);
```

## 最佳实践

1. **始终关闭连接**：使用 try-finally 块确保正确清理
2. **优雅处理错误**：捕获并处理特定的异常类型
3. **验证响应**：在访问数据之前检查响应类型
4. **使用超时**：为长时间运行的操作设置适当的超时
5. **记录重要事件**：启用日志以便调试和监控
6. **测试服务器兼容性**：在生产环境中使用之前验证服务器命令是否工作

## 下一步

- [HTTP 客户端指南](./http-client.md) - 了解 HTTP 传输
- [客户端示例](./examples.md) - 查看完整实现示例
- [服务端文档](../server/stdio-server.md) - 了解如何创建服务器 