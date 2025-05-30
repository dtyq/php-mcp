# MCP 传输层

本目录包含 PHP MCP（模型上下文协议）服务器的传输层实现。传输层负责根据 MCP 2025-03-26 规范处理 MCP 客户端和服务器之间的通信。

## 📁 目录结构

```
Transports/
├── Core/                          # 核心传输基础设施
│   ├── TransportInterface.php     # 基础传输接口
│   ├── AbstractTransport.php      # 通用传输功能
│   ├── MessageProcessor.php       # 消息处理逻辑
│   ├── HandlerFactory.php         # 消息处理器工厂
│   ├── TransportMetadata.php      # 传输元数据容器
│   └── Handlers/                  # 消息处理器
│       ├── MessageHandlerInterface.php
│       ├── AbstractMessageHandler.php
│       ├── AbstractNotificationHandler.php
│       ├── InitializeMessageHandler.php
│       ├── InitializedNotificationMessageHandler.php
│       ├── PingMessageHandler.php
│       ├── ListToolsMessageHandler.php
│       ├── CallToolMessageHandler.php
│       ├── ListPromptsMessageHandler.php
│       ├── GetPromptMessageHandler.php
│       ├── ListResourcesMessageHandler.php
│       ├── ListResourceTemplatesMessageHandler.php
│       ├── ReadResourceMessageHandler.php
│       ├── ProgressNotificationMessageHandler.php
│       └── CancelledNotificationMessageHandler.php
└── Stdio/                         # 标准输入输出传输
    ├── StdioTransport.php         # Stdio 传输实现
    └── StreamHandler.php          # 流处理工具
```

## 🚀 概述

传输层为 MCP 通信提供了灵活且可扩展的架构。它实现了：

- **JSON-RPC 2.0** 消息处理
- **请求/响应** 处理
- **通知** 支持
- **错误处理** 和验证
- **类型安全** 的消息路由
- **可扩展** 的处理器系统

## 🏗️ 架构

### 核心组件

#### 1. TransportInterface
所有传输实现必须遵循的基础接口：

```php
interface TransportInterface
{
    public function start(): void;
    public function stop(): void;
    public function isRunning(): bool;
    public function handleMessage(string $message): ?string;
    public function sendMessage(string $message): void;
}
```

#### 2. AbstractTransport
为所有传输实现提供通用功能：

- 消息验证
- 错误处理
- 日志集成
- 请求处理

#### 3. MessageProcessor
处理核心消息处理逻辑：

- JSON-RPC 验证
- 消息路由
- 处理器执行
- 响应生成

#### 4. HandlerFactory
为不同消息类型创建适当的处理器：

- 方法映射
- 处理器实例化
- 类型安全

### 消息处理器

传输层使用基于处理器的架构，每个 MCP 方法都有专用的处理器：

#### 请求处理器
- **InitializeMessageHandler**: 处理服务器初始化
- **PingMessageHandler**: 处理 ping 请求
- **ListToolsMessageHandler**: 列出可用工具
- **CallToolMessageHandler**: 执行工具调用
- **ListPromptsMessageHandler**: 列出可用提示
- **GetPromptMessageHandler**: 获取提示内容
- **ListResourcesMessageHandler**: 列出可用资源
- **ListResourceTemplatesMessageHandler**: 列出资源模板
- **ReadResourceMessageHandler**: 读取资源内容

#### 通知处理器
- **InitializedNotificationMessageHandler**: 处理初始化完成
- **ProgressNotificationMessageHandler**: 处理进度更新
- **CancelledNotificationMessageHandler**: 处理取消请求

## 📡 支持的传输方式

### Stdio 传输

**StdioTransport** 实现了通过标准输入/输出流的通信，适用于：

- 命令行工具
- 进程生成
- 简单集成
- 开发和测试

#### 特性：
- **流缓冲** 以实现高效 I/O
- **消息分隔** 和适当的帧处理
- **错误恢复** 和验证
- **优雅关闭** 处理
- **可配置超时**

#### 配置：
```php
'transports' => [
    'stdio' => [
        'enabled' => true,
        'buffer_size' => 8192,
        'timeout' => 30,
        'validate_messages' => true,
    ],
]
```

## 🛠️ 使用示例

### 基础 Stdio 服务器

```php
use Dtyq\PhpMcp\Server\McpServer;
use Dtyq\PhpMcp\Shared\Kernel\Application;

// 创建应用
$app = new Application($container, $config);

// 创建 MCP 服务器
$server = new McpServer('my-server', '1.0.0', $app);

// 注册工具、提示、资源
$server
    ->registerTool($myTool)
    ->registerPrompt($myPrompt)
    ->registerResource($myResource);

// 启动 stdio 传输
$server->stdio();
```

### 自定义传输实现

```php
use Dtyq\PhpMcp\Server\Transports\Core\AbstractTransport;

class CustomTransport extends AbstractTransport
{
    public function start(): void
    {
        $this->running = true;
        // 自定义传输初始化
    }

    public function stop(): void
    {
        $this->running = false;
        // 自定义传输清理
    }

    public function sendMessage(string $message): void
    {
        // 自定义消息发送逻辑
    }
}
```

### 自定义消息处理器

```php
use Dtyq\PhpMcp\Server\Transports\Core\Handlers\AbstractMessageHandler;

class CustomMethodHandler extends AbstractMessageHandler
{
    public function createRequest(array $request): RequestInterface
    {
        return CustomRequest::fromArray($request);
    }

    public function handle(RequestInterface $message, TransportMetadata $metadata): ?ResultInterface
    {
        // 自定义处理逻辑
        return new CustomResult($data);
    }
}
```

## 🔧 配置

### 传输元数据

`TransportMetadata` 类为消息处理器提供上下文：

```php
$metadata = new TransportMetadata(
    name: 'my-server',
    version: '1.0.0',
    instructions: '服务器说明',
    toolManager: $toolManager,
    promptManager: $promptManager,
    resourceManager: $resourceManager
);
```

### 处理器注册

处理器根据 MCP 方法名称自动注册：

- `initialize` → `InitializeMessageHandler`
- `ping` → `PingMessageHandler`
- `tools/list` → `ListToolsMessageHandler`
- `tools/call` → `CallToolMessageHandler`
- `prompts/list` → `ListPromptsMessageHandler`
- `prompts/get` → `GetPromptMessageHandler`
- `resources/list` → `ListResourcesMessageHandler`
- `resources/templates/list` → `ListResourceTemplatesMessageHandler`
- `resources/read` → `ReadResourceMessageHandler`

## 🧪 测试

传输层包含全面的测试：

```bash
# 运行传输测试
composer test -- --filter=Transport

# 运行特定传输测试
composer test -- tests/Unit/Server/Transports/
```

## 🔍 调试

启用详细日志记录进行传输调试：

```php
'logging' => [
    'level' => 'debug',
    'channels' => ['transport', 'stdio', 'handler'],
]
```

## 🚀 性能

### 优化建议

1. **缓冲区大小**: 根据消息量调整缓冲区大小
2. **超时配置**: 为您的用例设置适当的超时
3. **处理器缓存**: 处理器实例化一次并重复使用
4. **流管理**: 适当的资源清理可防止内存泄漏

### 监控

- 监控消息吞吐量
- 跟踪处理器执行时间
- 观察错误率
- 测量内存使用

## 🔮 未来的传输方式

架构支持额外的传输实现：

- **HTTP 传输**: REST/WebSocket 支持
- **TCP 传输**: 直接套接字通信
- **IPC 传输**: 进程间通信
- **自定义协议**: 特定领域的实现

## 📚 参考资料

- [MCP 规范 2025-03-26](https://spec.modelcontextprotocol.io/)
- [JSON-RPC 2.0 规范](https://www.jsonrpc.org/specification)
- [PHP MCP 文档](../../../docs/)

## 🤝 贡献

添加新传输或处理器时：

1. 实现所需的接口
2. 遵循现有模式
3. 添加全面的测试
4. 更新文档
5. 考虑错误场景
6. 根据 MCP 规范验证

## 📋 消息处理流程

### 1. 消息接收
```
客户端 → 传输层 → MessageProcessor → HandlerFactory → 具体处理器
```

### 2. 消息处理
```
请求验证 → 类型转换 → 业务逻辑 → 响应生成 → 结果返回
```

### 3. 错误处理
```
异常捕获 → 错误包装 → 标准化响应 → 日志记录 → 客户端返回
```

## 🛡️ 安全考虑

### 输入验证
- JSON-RPC 格式验证
- 参数类型检查
- 请求大小限制
- 恶意载荷过滤

### 资源保护
- 内存使用限制
- 执行时间控制
- 并发连接限制
- 资源泄漏防护

### 日志安全
- 敏感信息过滤
- 安全事件记录
- 审计跟踪
- 错误信息脱敏

## 🔄 生命周期管理

### 传输生命周期
1. **初始化**: 配置加载和资源分配
2. **启动**: 开始监听和处理消息
3. **运行**: 持续处理客户端请求
4. **关闭**: 优雅停止和资源清理

### 处理器生命周期
1. **注册**: 根据方法名映射处理器
2. **实例化**: 按需创建处理器实例
3. **执行**: 处理具体的请求消息
4. **缓存**: 重用处理器实例提升性能

## 🧩 扩展点

### 自定义传输
- 实现 `TransportInterface`
- 继承 `AbstractTransport`
- 添加特定配置选项
- 集成到应用配置

### 自定义处理器
- 实现 `MessageHandlerInterface`
- 继承 `AbstractMessageHandler`
- 在 `HandlerFactory` 中注册
- 支持新的 MCP 方法

### 中间件支持
- 请求预处理
- 响应后处理
- 认证和授权
- 性能监控 