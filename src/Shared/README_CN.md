# Shared 目录

`Shared` 目录包含了 PHP MCP 实现中使用的通用工具、消息处理、异常管理和核心内核组件。该目录为模型上下文协议（Model Context Protocol）实现提供了基础设施。

> **📖 官方文档**: 本实现遵循 [MCP 2025-03-26 规范](https://modelcontextprotocol.io/specification/2025-03-26/)

## 目录结构

```
Shared/
├── Auth/               # 身份验证框架和接口
├── Exceptions/          # 异常处理和错误管理
├── Kernel/             # 核心应用程序框架
├── Message/            # JSON-RPC 消息处理工具
└── Utilities/          # 通用工具类
```

## 子目录概览

### 1. Auth/

为 MCP 操作提供简单灵活的身份验证框架，通过基于接口的身份验证提供最小依赖。

**文件列表：**
- `AuthenticatorInterface.php` - 自定义实现的身份验证合约
- `NullAuthenticator.php` - 提供通用访问的默认身份验证器

**设计原则：**
- **接口驱动**：通过简单合约支持多种身份验证方法
- **零依赖**：无特定 OAuth2 库要求
- **渐进式采用**：从无身份验证到完整企业身份验证
- **应用程序集成**：易于与现有身份验证系统集成

### 2. Exceptions/

包含 MCP 协议的全面异常处理类，包括 JSON-RPC 错误、MCP 特定错误、OAuth 错误和传输错误。

**文件列表：**
- `ErrorCodes.php` - JSON-RPC 2.0 和 MCP 协议的集中错误码常量
- `McpError.php` - 所有 MCP 相关错误的基础异常类
- `ValidationError.php` - 输入验证和数据格式错误的异常
- `AuthenticationError.php` - 身份验证和 OAuth 相关错误的异常
- `TransportError.php` - 传输层错误的异常（HTTP、SSE 等）
- `ProtocolError.php` - MCP 协议违规的异常
- `SystemException.php` - 系统级错误的异常
- `ErrorData.php` - 错误信息的数据结构

### 3. Kernel/

核心应用程序框架，提供依赖注入、配置管理、身份验证和日志基础设施。

**文件列表：**
- `Application.php` - 带身份验证支持的主应用程序容器和服务定位器
- `Config/Config.php` - 使用点符号的配置管理
- `Logger/LoggerProxy.php` - 带有 SDK 名称前缀的 PSR-3 日志代理

### 4. Message/

用于创建、解析和验证 MCP 协议消息的 JSON-RPC 2.0 消息处理工具。

**文件列表：**
- `JsonRpcMessage.php` - 核心 JSON-RPC 2.0 消息实现
- `MessageUtils.php` - 创建常见 MCP 消息的工具方法
- `SessionMessage.php` - 带有元数据的会话感知消息包装器

### 5. Utilities/

用于 JSON 处理、HTTP 操作和其他共享功能的通用工具类。

**文件列表：**
- `JsonUtils.php` - 带有 MCP 特定默认值的 JSON 编码/解码
- `HttpUtils.php` - 各种传输方法的 HTTP 工具

## 详细文件说明

### Auth/AuthenticatorInterface.php

用于在 MCP 应用程序中实现自定义身份验证策略的核心身份验证合约。

**接口方法：**
- `authenticate(): AuthInfo` - 执行身份验证并返回身份验证信息

**设计理念：**
- **简单合约**：单方法身份验证接口
- **基于异常**：成功时返回 `AuthInfo`，失败时抛出 `AuthenticationError`
- **无依赖**：实现控制凭据提取和验证
- **灵活性**：支持 JWT、数据库、API 或任何自定义身份验证方法

**使用示例：**
```php
// 自定义 JWT 身份验证器
class JwtAuthenticator implements AuthenticatorInterface
{
    public function authenticate(): AuthInfo
    {
        $token = $this->extractTokenFromRequest();
        $payload = $this->validateJwtToken($token);
        
        return AuthInfo::create(
            $payload['sub'],
            $payload['scopes'] ?? [],
            ['token_type' => 'jwt', 'iat' => $payload['iat']]
        );
    }
}

// 自定义数据库身份验证器
class DatabaseAuthenticator implements AuthenticatorInterface
{
    public function authenticate(): AuthInfo
    {
        $apiKey = $this->extractApiKeyFromRequest();
        $user = $this->findUserByApiKey($apiKey);
        
        if (!$user) {
            throw new AuthenticationError('Invalid API key');
        }
        
        return AuthInfo::create(
            $user->id,
            $user->scopes,
            ['user_type' => $user->type, 'api_key' => $apiKey]
        );
    }
}
```

### Auth/NullAuthenticator.php

为开发和测试场景提供通用访问的默认身份验证器实现。

**特性：**
- **通用访问**：为匿名用户授予所有作用域（`*`）
- **零配置**：无需设置即可开箱即用
- **开发友好**：非常适合测试和开发环境
- **永不过期**：身份验证永不过期

**使用示例：**
```php
$authenticator = new NullAuthenticator();
$authInfo = $authenticator->authenticate();

// 始终返回具有通用访问权限的匿名用户
assert($authInfo->getSubject() === 'anonymous');
assert($authInfo->hasScope('any-scope') === true);
assert($authInfo->hasAllScopes(['read', 'write', 'admin']) === true);
```

### Kernel/Application.php

通过流畅接口支持身份验证的增强应用程序容器。

**身份验证方法：**
- `withAuthenticator(AuthenticatorInterface $authenticator): self` - 设置自定义身份验证器
- `getAuthenticator(): AuthenticatorInterface` - 获取当前身份验证器（默认为 NullAuthenticator）

**使用示例：**
```php
// 无身份验证（默认）
$app = new Application($container, $config);
$authInfo = $app->getAuthenticator()->authenticate(); // 返回具有通用访问权限的匿名用户

// JWT 身份验证
$jwtAuth = new JwtAuthenticator($secretKey);
$app = $app->withAuthenticator($jwtAuth);

// 数据库身份验证
$dbAuth = new DatabaseAuthenticator($connection);
$app = $app->withAuthenticator($dbAuth);

// 自定义身份验证
$customAuth = new class implements AuthenticatorInterface {
    public function authenticate(): AuthInfo {
        // 自定义逻辑
        return AuthInfo::create('custom-user', ['read', 'write']);
    }
};
$app = $app->withAuthenticator($customAuth);
```

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
- `tools/list`