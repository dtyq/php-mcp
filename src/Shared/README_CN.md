# Shared 目录

`Shared` 目录包含了 PHP MCP 实现中使用的通用工具、消息处理、异常管理和核心内核组件。该目录为模型上下文协议（Model Context Protocol）实现提供了基础设施。

> **📖 官方文档**: 本实现遵循 [MCP 2025-03-26 规范](https://modelcontextprotocol.io/specification/2025-03-26/)

## 目录结构

```
Shared/
├── Exceptions/          # 异常处理和错误管理
├── Kernel/             # 核心应用程序框架
├── Message/            # JSON-RPC 消息处理工具
└── Utilities/          # 通用工具类
```

## 子目录概览

### 1. Exceptions/

包含 MCP 协议的全面异常处理类，包括 JSON-RPC 错误、MCP 特定错误、OAuth 错误和传输错误。

**文件列表：**
- `ErrorCodes.php` - JSON-RPC 2.0 和 MCP 协议的集中错误码常量
- `McpError.php` - 所有 MCP 相关错误的基础异常类
- `ValidationError.php` - 输入验证和数据格式错误的异常
- `AuthenticationError.php` - 身份验证和 OAuth 相关错误的异常
- `TransportError.php` - 传输层错误的异常（HTTP、WebSocket 等）
- `ProtocolError.php` - MCP 协议违规的异常
- `SystemException.php` - 系统级错误的异常
- `ErrorData.php` - 错误信息的数据结构

### 2. Kernel/

核心应用程序框架，提供依赖注入、配置管理和日志基础设施。

**文件列表：**
- `Application.php` - 主应用程序容器和服务定位器
- `Config/Config.php` - 使用点符号的配置管理
- `Logger/LoggerProxy.php` - 带有 SDK 名称前缀的 PSR-3 日志代理

### 3. Message/

用于创建、解析和验证 MCP 协议消息的 JSON-RPC 2.0 消息处理工具。

**文件列表：**
- `JsonRpcMessage.php` - 核心 JSON-RPC 2.0 消息实现
- `MessageUtils.php` - 创建常见 MCP 消息的工具方法
- `SessionMessage.php` - 带有元数据的会话感知消息包装器

### 4. Utilities/

用于 JSON 处理、HTTP 操作和其他共享功能的通用工具类。

**文件列表：**
- `JsonUtils.php` - 带有 MCP 特定默认值的 JSON 编码/解码
- `HttpUtils.php` - 各种传输方法的 HTTP 工具

## 详细文件说明

### Exceptions/ErrorCodes.php

定义 MCP 实现中使用的所有错误码：

- **JSON-RPC 2.0 标准错误** (-32700 到 -32603)
- **MCP 协议错误** (-32000 到 -32015)
- **OAuth 2.1 错误** (-32020 到 -32030)
- **HTTP 传输错误** (-32040 到 -32049)
- **流式 HTTP 错误** (-32050 到 -32053)
- **连接错误** (-32060 到 -32064)

**主要特性：**
- 人类可读的错误消息
- 错误码验证方法
- 分类助手

**重要说明：** 代码库中有两个错误码定义：
1. `Shared/Exceptions/ErrorCodes.php` - 包含所有传输特定错误码的完整实现
2. `Types/Core/ProtocolConstants.php` - 仅包含核心 MCP 协议错误码

Shared 版本提供了全面的错误处理系统，而 Types 版本专注于核心协议错误。两者都遵循 MCP 2025-03-26 规范，但在架构中服务于不同的目的。

**错误码对齐：** 错误码已更新以严格遵循 MCP 2025-03-26 规范：
- 按照[官方文档](https://modelcontextprotocol.io/specification/2025-03-26/server/resources#error-handling)规定，`-32002` 用于 "Resource not found"
- 所有核心协议错误（-32000 到 -32009）在两个文件中都有一致的定义
- 传输特定错误（OAuth、HTTP、流式 HTTP、连接）仅在 Shared 版本中

> **📋 参考文档**: [MCP 错误处理](https://modelcontextprotocol.io/specification/2025-03-26/server/resources#error-handling) | [JSON-RPC 2.0 错误](https://modelcontextprotocol.io/specification/2025-03-26/basic#responses)

### Exceptions/ValidationError.php

为常见验证场景提供工厂方法：

```php
ValidationError::requiredFieldMissing('name', 'user profile');
ValidationError::invalidFieldType('age', 'integer', 'string');
ValidationError::invalidJsonFormat('malformed JSON structure');
```

### Exceptions/AuthenticationError.php

全面的 OAuth 2.1 和身份验证错误处理：

```php
AuthenticationError::invalidScope('read:admin', ['read:user', 'write:user']);
AuthenticationError::expiredCredentials('access token');
AuthenticationError::insufficientPermissions('delete_resource');
```

### Exceptions/TransportError.php

各种协议的传输层错误处理：

```php
TransportError::connectionTimeout('HTTP', 30);
TransportError::httpError(404, 'Not Found');
TransportError::streamableHttpError('session_expired', 'Session has expired');
```

### Message/JsonRpcMessage.php

核心 JSON-RPC 2.0 消息实现，支持：

- **请求** 包含方法、参数和 ID
- **响应** 包含结果或错误
- **通知** 不包含 ID
- **批处理操作**（消息数组）

**使用示例：**
```php
// 创建请求
$request = JsonRpcMessage::createRequest('tools/list', ['cursor' => 'abc'], 1);

// 创建响应
$response = JsonRpcMessage::createResponse(1, ['tools' => []]);

// 创建通知
$notification = JsonRpcMessage::createNotification('notifications/progress', [
    'progressToken' => 'token123',
    'progress' => 0.5
]);
```

### Message/MessageUtils.php

创建常见 MCP 消息的高级工具：

**协议信息：**
- MCP 协议版本：`2025-03-26`
- JSON-RPC 版本：`2.0`

**支持的方法：**
- `initialize` / `notifications/initialized`
- `ping`
- `tools/list` / `tools/call`
- `resources/list` / `resources/read` / `resources/subscribe` / `resources/unsubscribe`
- `prompts/list` / `prompts/get`
- `sampling/createMessage`
- `roots/list`

**通知类型：**
- `notifications/progress`
- `notifications/message`
- `notifications/cancelled`
- `notifications/resources/updated`
- `notifications/resources/list_changed`
- `notifications/tools/list_changed`
- `notifications/prompts/list_changed`

**使用示例：**
```php
// 初始化连接
$init = MessageUtils::createInitializeRequest(1, [
    'name' => 'MyClient',
    'version' => '1.0.0'
], ['tools' => true]);

// 带分页的工具列表
$listTools = MessageUtils::createListToolsRequest(2, 'cursor123');

// 订阅资源更新
$subscribe = MessageUtils::createSubscribeRequest(3, 'file:///path/to/file');

// 发送进度通知
$progress = MessageUtils::createProgressNotification('token123', 0.75, 100);
```

### Utilities/JsonUtils.php

带有 MCP 特定默认值的 JSON 处理工具：

**特性：**
- 带有适当错误处理的安全编码/解码
- 调试用的美化打印
- 不解码的 JSON 验证
- 对象合并和字段提取
- 大小检查和规范化

**使用示例：**
```php
// 使用 MCP 默认值编码
$json = JsonUtils::encode($data);

// 带错误处理的安全解码
$result = JsonUtils::safeDecode($jsonString);
if ($result['success']) {
    $data = $result['data'];
} else {
    $error = $result['error'];
}

// 验证 JSON 结构
if (JsonUtils::isValid($jsonString)) {
    // 处理有效的 JSON
}
```

### Utilities/HttpUtils.php

各种传输方法的 HTTP 工具：

**支持的传输：**
- 标准 HTTP/HTTPS
- 服务器发送事件（SSE）
- 流式 HTTP（MCP 2025-03-26）
- 表单数据和 JSON 请求

**特性：**
- 不同 HTTP 方法的上下文创建
- 身份验证头助手
- URL 操作工具
- 状态码验证

**使用示例：**
```php
// 创建 JSON 请求上下文
$context = HttpUtils::createJsonContext('POST', $requestData);

// 创建流式 SSE 上下文
$sseContext = HttpUtils::createSseContext(['Authorization' => 'Bearer token']);

// 创建流式 HTTP 上下文
$streamContext = HttpUtils::createStreamableHttpContext('POST', $data);
```

## 架构原则

### 1. 基于接口的设计
所有组件在适用的地方实现相应的 PSR 接口（日志使用 PSR-3，容器使用 PSR-11）。

### 2. 错误处理策略
- 遵循 JSON-RPC 2.0 和 MCP 规范的全面错误码
- 常见错误场景的工厂方法
- 带有附加上下文的结构化错误数据

### 3. JSON-RPC 2.0 合规性
- 严格遵守 JSON-RPC 2.0 规范
- 支持请求、响应、通知和批处理
- 正确的 ID 处理和错误响应

> **📋 参考文档**: [JSON-RPC 2.0 消息](https://modelcontextprotocol.io/specification/2025-03-26/basic#messages) | [批处理支持](https://modelcontextprotocol.io/specification/2025-03-26/basic#batching)

### 4. MCP 2025-03-26 支持
- 最新协议版本支持
- OAuth 2.1 身份验证框架
- 流式 HTTP 传输
- 工具注释和完成功能

> **📋 参考文档**: [MCP 更新日志](https://modelcontextprotocol.io/specification/2025-03-26/changelog) | [身份验证框架](https://modelcontextprotocol.io/specification/2025-03-26/basic#auth)

### 5. 可扩展性
- 允许轻松扩展的模块化设计
- 对象创建的工厂模式
- 配置驱动的行为

## 依赖项

- **PSR-3**：日志接口
- **PSR-11**：容器接口
- **PSR-14**：事件调度器接口
- **PSR-16**：简单缓存接口
- **adbar/dot**：配置管理

## 在 MCP 实现中的使用

Shared 目录为以下内容提供基础：

1. **客户端实现**：消息创建、错误处理、传输工具
2. **服务器实现**：请求处理、响应生成、通知发送
3. **传输层**：HTTP、WebSocket、STDIO 传输实现
4. **协议合规性**：JSON-RPC 2.0 和 MCP 2025-03-26 规范遵守

## 错误处理流程

```
用户输入 → 验证 → 业务逻辑 → 传输 → 响应
    ↓      ↓       ↓       ↓     ↓
ValidationError → McpError → TransportError → JsonRpcMessage
```

## 消息流程示例

```php
// 1. 创建请求
$request = MessageUtils::createListToolsRequest(1);

// 2. 通过传输发送（HTTP、WebSocket 等）
$response = $transport->send($request);

// 3. 处理响应或错误
if ($response->isError()) {
    $error = $response->getError();
    throw new McpError(new ErrorData($error['code'], $error['message']));
}

$result = $response->getResult();
```

这个共享基础设施确保了所有 MCP 组件的一致行为，同时为不同的用例和传输方法提供了灵活性。

## 🔗 相关文档

- [MCP 规范 2025-03-26](https://modelcontextprotocol.io/specification/2025-03-26/)
- [JSON-RPC 2.0 规范](https://www.jsonrpc.org/specification)
- [MCP 基础协议](https://modelcontextprotocol.io/specification/2025-03-26/basic)
- [MCP 服务器资源](https://modelcontextprotocol.io/specification/2025-03-26/server/resources)
- [MCP 身份验证](https://modelcontextprotocol.io/specification/2025-03-26/basic#auth)
- 项目开发标准和编码指南 