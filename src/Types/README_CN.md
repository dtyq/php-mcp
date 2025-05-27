# MCP Types 目录

本目录包含了 Model Context Protocol (MCP) 2025-03-26 规范的完整 PHP 实现。所有类型都按逻辑分组到子目录中，并遵循官方 MCP 协议要求。

## 📁 目录结构

```
Types/
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

## 🔧 核心类型 (`Core/`)

定义基本协议结构的基础类型和接口：

- **`BaseTypes.php`** - 基础工具函数和验证方法
- **`ProtocolConstants.php`** - 协议常量、错误码和方法名
- **`RequestInterface.php`** - 所有请求类型的接口
- **`ResultInterface.php`** - 所有响应结果类型的接口
- **`NotificationInterface.php`** - 所有通知类型的接口
- **`JsonRpcRequest.php`** - JSON-RPC 2.0 请求消息结构
- **`JsonRpcResponse.php`** - JSON-RPC 2.0 响应消息结构
- **`JsonRpcError.php`** - JSON-RPC 2.0 错误结构

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
- **`EmbeddedResource.php`** - 嵌入式资源内容
- **`Annotations.php`** - 用于目标定位和优先级的内容注解

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

## 📬 响应类型 (`Responses/`)

服务器到客户端的响应消息：

- **`InitializeResult.php`** - 带服务器能力的初始化响应
- **`ListResourcesResult.php`** - 支持分页的资源列表
- **`ReadResourceResult.php`** - 资源内容（文本或二进制）
- **`ListToolsResult.php`** - 支持分页的工具列表
- **`CallToolResult.php`** - 带内容和错误状态的工具执行结果
- **`ListPromptsResult.php`** - 支持分页的提示列表

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

可执行函数和能力的类型：

- **`Tool.php`** - 带模式和元数据的工具定义
- **`ToolResult.php`** - 工具执行结果容器
- **`ToolAnnotations.php`** - 工具元数据和行为提示

## 💭 提示类型 (`Prompts/`)

模板化消息和工作流的类型：

- **`Prompt.php`** - 提示模板定义
- **`PromptArgument.php`** - 提示参数定义
- **`PromptMessage.php`** - 提示模板中的单个消息
- **`GetPromptResult.php`** - 提示模板执行结果

## 🤖 采样类型 (`Sampling/`)

LLM 交互和消息生成的类型：

- **`CreateMessageRequest.php`** - LLM 消息生成请求
- **`CreateMessageResult.php`** - LLM 生成的消息响应
- **`SamplingMessage.php`** - 采样的消息结构
- **`ModelPreferences.php`** - LLM 模型偏好和提示
- **`ModelHint.php`** - 模型选择提示

## 🏗️ 架构原则

### 基于接口的设计
所有类型都实现适当的接口（`RequestInterface`、`ResultInterface`、`NotificationInterface`），确保一致的行为和类型安全。

### 验证和错误处理
- 所有类型都使用 `ValidationError` 进行一致的错误报告
- 全面的输入验证和描述性错误消息
- 类型安全的构造和数据访问方法

### JSON-RPC 2.0 合规性
- 完全符合 JSON-RPC 2.0 规范
- 正确的请求/响应 ID 处理
- 标准错误码实现

### 分页支持
列表操作支持基于游标的分页：
- `nextCursor` 用于前向导航
- 所有列表结果的一致分页接口

### 可扩展性
- 元字段支持（`_meta`）用于附加信息
- 内容目标定位和优先级的注解系统
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

✅ **完整的 MCP 2025-03-26 核心协议支持**
- 所有必需的请求/响应对已实现
- 完整的通知系统
- 完整的资源、工具和提示管理
- LLM 交互的采样能力
- 正确的错误处理和验证

## 🔗 相关文档

- [MCP 规范 2025-03-26](https://modelcontextprotocol.io/specification/2025-03-26/)
- [JSON-RPC 2.0 规范](https://www.jsonrpc.org/specification)
- 项目开发标准和编码指南

---

*此实现提供了 Model Context Protocol 的完整、类型安全的 PHP 实现，使 LLM 应用程序与外部数据源和工具之间能够无缝集成。* 