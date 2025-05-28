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
- **`