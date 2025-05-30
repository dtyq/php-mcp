# MCP Types 目录

本目录包含了 Model Context Protocol (MCP) 2025-03-26 规范的完整 PHP 实现。所有类型都按逻辑分组到子目录中，并遵循官方 MCP 协议要求。

> **📖 官方文档**: 本实现遵循 [MCP 2025-03-26 规范](https://modelcontextprotocol.io/specification/2025-03-26/)

## 📁 目录结构

```
Types/
├── Auth/           # 身份验证类型和数据结构
├── Core/           # 核心协议类型和接口
├── Messages/       # 通信消息类型
├── Content/        # 内容类型（文本、图像、嵌入资源）
├── Requests/       # 请求消息类型
├── Responses/      # 响应消息类型  
├── Notifications/  # 通知消息类型
├── Resources/      # 资源相关类型
├── Tools/          # 工具相关类型
├── Prompts/        # 提示相关类型
└── Sampling/       # 采样相关类型
```

## 🔐 身份验证类型 (`Auth/`)

用于管理 MCP 操作中身份验证上下文和用户权限的类型：

- **`AuthInfo.php`** - 带基于作用域权限的身份验证信息容器

**主要特性：**
- **基于作用域的权限**：使用基于字符串的作用域进行细粒度访问控制
- **通配符支持**：通过 `*` 作用域实现通用访问
- **元数据存储**：额外的身份验证上下文和用户信息
- **过期处理**：基于时间的身份验证有效性
- **类型安全**：全面的验证和类型安全操作

**使用示例：**
```php
// 创建具有特定作用域的身份验证信息
$authInfo = AuthInfo::create('user123', ['read', 'write'], [
    'role' => 'admin',
    'department' => 'engineering'
], time() + 3600);

// 检查权限
if ($authInfo->hasScope('read')) {
    // 用户可以读取
}

if ($authInfo->hasAllScopes(['read', 'write'])) {
    // 用户可以读取和写入
}

// 匿名通用访问
$anonymous = AuthInfo::anonymous();
assert($anonymous->hasScope('any-scope') === true);
```

## 🔧 核心类型 (`Core/`)

定义基本协议结构的基础类型和接口：

- **`BaseTypes.php`** - 基础工具函数和验证方法
- **`ProtocolConstants.php`** - 协议常量、错误码、方法名和传输类型
- **`MessageValidator.php`** - 符合MCP stdio规范的通用消息验证器
- **`RequestInterface.php`** - 所有请求类型的接口
- **`ResultInterface.php`** - 所有响应结果类型的接口
- **`NotificationInterface.php`** - 所有通知类型的接口
- **`JsonRpcRequest.php`** - JSON-RPC 2.0 请求消息结构
- **`JsonRpcResponse.php`** - JSON-RPC 2.0 响应消息结构
- **`JsonRpcError.php`** - JSON-RPC 2.0 错误结构

### 传输类型常量

**最新版本新增**: `ProtocolConstants` 现在包含标准化的传输类型定义：

```php
// 传输类型常量，用于一致性引用
ProtocolConstants::TRANSPORT_TYPE_STDIO     // 'stdio'
ProtocolConstants::TRANSPORT_TYPE_HTTP      // 'http'  
ProtocolConstants::TRANSPORT_TYPE_SSE       // 'sse'
ProtocolConstants::TRANSPORT_TYPE_WEBSOCKET // 'websocket'

// 工具方法
ProtocolConstants::getSupportedTransportTypes(): array
ProtocolConstants::isValidTransportType(string $type): bool
```

**优势：**
- **消除魔法字符串** - 不再使用硬编码的传输类型字符串
- **类型安全** - 减少拼写错误，提升IDE支持
- **集中管理** - 所有传输类型在一个位置定义
- **未来扩展性** - 轻松添加新的传输类型

### 消息验证系统

**增强的 `MessageValidator`** 提供全面的JSON-RPC和MCP合规性验证：

```php
// 核心验证（验证失败时抛出ValidationError）
MessageValidator::validateMessage(string $message, bool $strictMode = false): void

// 严格模式启用MCP stdio格式验证
MessageValidator::validateMessage($message, true); // 不允许嵌入换行符

// 便利方法
MessageValidator::isValidMessage(string $message, bool $strictMode = false): bool
MessageValidator::getMessageInfo(string $message): array

// 细粒度验证方法
MessageValidator::validateUtf8(string $message): void
MessageValidator::validateStdioFormat(string $message): void
MessageValidator::validateStructure($decoded): void
```

**主要特性：**
- **UTF-8编码验证** - 确保正确的字符编码
- **MCP stdio格式合规性** - 严格模式下验证无嵌入换行符
- **JSON-RPC 2.0结构验证** - 完整的协议合规性
- **批量消息支持** - 处理单个和批量JSON-RPC消息
- **详细错误报告** - 全面的ValidationError异常
- **传输感知验证** - 不同传输类型的不同规则

## 💬 消息类型 (`Messages/`)

协议通信的高级消息类型：

- **`MessageInterface.php`** - 所有消息类型的基础接口
- **`PromptMessage.php`** - 提示模板的消息结构
- **`SamplingMessage.php`** - LLM 采样的消息结构

## 📄 内容类型 (`Content/`)

可包含在消息和响应中的内容：

- **`ContentInterface.php`** - 所有内容类型的基础接口
- **`TextContent.php`** - 带可选注解的纯文本内容
- **`ImageContent.php`** - Base64 编码的图像内容
- **`AudioContent.php`** - Base64 编码的音频内容（MCP 2025-03-26 新增）
- **`EmbeddedResource.php`** - 嵌入式资源内容
- **`Annotations.php`** - 用于目标定位和优先级的内容注解

### 音频内容支持

**MCP 2025-03-26 新增**: 完整的音频内容支持，兼容多种格式：

```php
// 从 base64 数据创建音频内容
$audioContent = new AudioContent($base64Data, 'audio/mpeg');

// 从文件创建并自动检测格式
$audioContent = AudioContent::fromFile('/path/to/audio.mp3');

// 支持的格式
- MP3 (audio/mpeg)
- WAV (audio/wav) 
- OGG (audio/ogg)
- M4A (audio/mp4)
- WebM (audio/webm)
- 自定义 audio/* 类型
```

## 📨 请求类型 (`Requests/`)

客户端到服务器的请求消息：

### 连接管理
- **`InitializeRequest.php`** - 使用能力初始化 MCP 连接
- **`PingRequest.php`** - 连接健康检查

### 资源操作
- **`ListResourcesRequest.php`** - 列出可用资源（支持分页）
- **`ReadResourceRequest.php`** - 读取特定资源内容
- **`SubscribeRequest.php`** - 订阅资源更新通知
- **`UnsubscribeRequest.php`** - 取消订阅资源更新

### 工具操作
- **`ListToolsRequest.php`** - 列出可用工具（支持分页）
- **`CallToolRequest.php`** - 使用参数执行工具

### 提示操作
- **`ListPromptsRequest.php`** - 列出可用提示（支持分页）
- **`GetPromptRequest.php`** - 获取带参数的提示模板

### 完成操作
- **`CompleteRequest.php`** - 获取自动完成建议（MCP 2025-03-26 新增）

## 📬 响应类型 (`Responses/`)

服务器到客户端的响应消息：

- **`InitializeResult.php`** - 带服务器能力的初始化响应
- **`ListResourcesResult.php`** - 支持分页的资源列表
- **`ReadResourceResult.php`** - 资源内容（文本或二进制）
- **`ListToolsResult.php`** - 支持分页的工具列表
- **`CallToolResult.php`** - 带内容和错误状态的工具执行结果
- **`ListPromptsResult.php`** - 支持分页的提示列表
- **`CompleteResult.php`** - 自动完成建议（MCP 2025-03-26 新增）

## 🔔 通知类型 (`Notifications/`)

单向通知消息（不期望响应）：

### 协议通知
- **`InitializedNotification.php`** - 成功初始化后发送
- **`ProgressNotification.php`** - 长时间运行操作的进度更新
- **`CancelledNotification.php`** - 请求取消通知

### 变更通知
- **`ResourceListChangedNotification.php`** - 资源列表已变更
- **`ResourceUpdatedNotification.php`** - 特定资源已更新
- **`ToolListChangedNotification.php`** - 工具列表已变更
- **`PromptListChangedNotification.php`** - 提示列表已变更

## 🗂️ 资源类型 (`Resources/`)

用于管理上下文数据和内容的类型：

- **`Resource.php`** - 带元数据的资源定义
- **`ResourceContents.php`** - 资源内容的基类
- **`TextResourceContents.php`** - 基于文本的资源内容
- **`BlobResourceContents.php`** - 二进制资源内容（base64 编码）
- **`ResourceTemplate.php`** - 参数化资源的模板

## 🔧 工具类型 (`Tools/`)

用于可执行函数和功能的类型：

- **`Tool.php`** - 带模式和元数据的工具定义
- **`ToolResult.php`** - 工具执行结果容器
- **`ToolAnnotations.php`** - 工具元数据和行为提示

## 💭 提示类型 (`Prompts/`)

用于模板化消息和工作流的类型：

- **`Prompt.php`** - 提示模板定义
- **`PromptArgument.php`** - 提示参数定义
- **`PromptMessage.php`** - 提示模板中的单个消息
- **`GetPromptResult.php`** - 提示模板执行结果

## 🤖 采样类型 (`Sampling/`)

用于LLM交互和消息生成的类型：

- **`CreateMessageRequest.php`** - LLM消息生成请求
- **`CreateMessageResult.php`** - LLM生成的消息响应
- **`SamplingMessage.php`** - 采样的消息结构
- **`ModelPreferences.php`** - LLM模型偏好和提示
- **`ModelHint.php`** - 模型选择提示

## 🏗️ 架构原则

### 基于接口的设计
所有类型都实现适当的接口（`RequestInterface`、`ResultInterface`、`NotificationInterface`），确保一致的行为和类型安全。

### 验证和错误处理
- 所有类型都使用 `ValidationError` 进行一致的错误报告
- 全面的输入验证和描述性错误消息
- 类型安全的构造和数据访问方法
- **传输层验证** - 在传输层处理消息验证以确保一致性
- **严格模式支持** - 针对特定传输要求的增强验证（如stdio）

### 基于常量的配置
- **消除魔法字符串** - 所有协议常量在 `ProtocolConstants` 中集中定义
- **传输类型标准化** - 代码库中传输类型引用的一致性
- **IDE友好** - 更好的自动完成和重构支持
- **类型安全** - 常量使用的编译时检查

### JSON-RPC 2.0 合规性
- 完全符合 JSON-RPC 2.0 规范
- 正确的请求/响应ID处理
- 标准错误码实现

### 分页支持
列表操作支持基于游标的分页：
- `nextCursor` 用于前向导航
- 所有列表结果的一致分页接口

### 扩展性
- 元字段支持（`_meta`）用于附加信息
- 内容定位和优先级的注解系统
- 灵活的内容类型系统

## 🔄 协议流程示例

### 基本资源访问
```
客户端 -> ListResourcesRequest -> 服务器
服务器 -> ListResourcesResult -> 客户端
客户端 -> ReadResourceRequest -> 服务器  
服务器 -> ReadResourceResult -> 客户端
```

### 工具执行
```
客户端 -> ListToolsRequest -> 服务器
服务器 -> ListToolsResult -> 客户端
客户端 -> CallToolRequest -> 服务器
服务器 -> CallToolResult -> 客户端
```

### 订阅模型
```
客户端 -> SubscribeRequest -> 服务器
服务器 -> (确认) -> 客户端
服务器 -> ResourceUpdatedNotification -> 客户端
```

## 📋 实现状态

✅ **完整的MCP 2025-03-26核心协议支持**
- 所有必需的请求/响应对已实现
- 带增强进度更新的完整通知系统
- 完整的资源、工具和提示管理
- 多媒体应用的音频内容支持
- 更好用户体验的自动完成能力
- LLM交互的采样功能
- 正确的错误处理和验证

## 🔗 相关文档

- [MCP 规范 2025-03-26](https://modelcontextprotocol.io/specification/2025-03-26/)
- [JSON-RPC 2.0 规范](https://www.jsonrpc.org/specification)
- [MCP 基础协议](https://modelcontextprotocol.io/specification/2025-03-26/basic)
- [MCP 服务器资源](https://modelcontextprotocol.io/specification/2025-03-26/server/resources)
- [MCP 服务器工具](https://modelcontextprotocol.io/specification/2025-03-26/server/tools)
- [MCP 服务器提示](https://modelcontextprotocol.io/specification/2025-03-26/server/prompts)
- [MCP 客户端采样](https://modelcontextprotocol.io/specification/2025-03-26/client/sampling)
- [MCP 更新日志](https://modelcontextprotocol.io/specification/2025-03-26/changelog)
- 项目开发标准和编码指南

## 🆕 MCP 2025-03-26 新功能

本实现包含了 MCP 2025-03-26 规范的所有主要更新：

### 1. 音频内容支持
- **`AudioContent.php`** - 完整的音频内容类型，支持 base64 编码
- 支持 MP3、WAV、OGG、M4A、WebM 格式
- 基于文件的创建和自动检测
- 大小计算和格式验证

### 2. 增强的进度通知
- **描述性消息** - `ProgressNotification` 现在包含可选的 `message` 字段
- 更好的用户体验和状态描述
- 向后兼容现有实现

### 3. 完成能力
- **`CompleteRequest.php`** - 请求自动完成建议
- **`CompleteResult.php`** - 返回完成建议
- 支持提示和资源模板参数完成
- 实现类似 IDE 的体验

### 4. 全面的工具注解
- 增强的工具元数据和行为提示
- 只读与破坏性操作指示器
- 更好的工具发现和安全性

---

*本实现提供了Model Context Protocol的完整、类型安全的PHP实现，使LLM应用程序能够与外部数据源和工具无缝集成。*