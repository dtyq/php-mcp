# 客户端配置指南

## 概述

PHP MCP 客户端提供了一个全面的配置系统，支持多种传输协议和类型安全的配置类。本指南涵盖了所有可用的配置选项和使用模式。

## 配置架构

配置系统围绕分层结构构建：

- **TransportConfigInterface**: 所有传输配置的基础接口
- **ConnectionConfig**: 传输特定配置的统一包装器
- **StdioConfig**: STDIO 传输配置
- **HttpConfig**: HTTP 传输配置

## 传输配置接口

所有传输配置都实现了 `TransportConfigInterface`，它提供：

```php
interface TransportConfigInterface extends JsonSerializable
{
    public static function fromArray(array $config): self;
    public static function getDefaults(): array;
    public function getTransportType(): string;
    public function toArray(): array;
    public function validate(): void;
    public function withChanges(array $changes): self;
}
```

### 主要特性

- **类型安全**: 所有配置都是强类型的
- **验证**: 内置所有配置值的验证
- **不可变性**: 配置支持通过 `withChanges()` 进行不可变更新
- **序列化**: 完整的 JSON 序列化支持
- **工厂方法**: 从数组或默认值轻松创建

## STDIO 配置

`StdioConfig` 类为基于进程的 MCP 服务器配置标准输入/输出传输。

### 基本使用

```php
use Dtyq\PhpMcp\Client\Configuration\StdioConfig;

// 简单配置
$config = new StdioConfig('npx', ['-y', '@modelcontextprotocol/server-sequential-thinking']);

// 带附加选项
$config = new StdioConfig('python', ['server.py'], [
    'read_timeout' => 30,
    'write_timeout' => 10,
    'buffer_size' => 8192,
]);
```

### 配置选项

| 选项 | 类型 | 默认值 | 描述 |
|------|------|--------|------|
| `command` | string | 必需 | 要执行的命令 |
| `args` | array | `[]` | 命令参数 |
| `env` | array | `[]` | 环境变量 |
| `cwd` | string\|null | `null` | 工作目录 |
| `read_timeout` | int | `30` | 读取超时（秒） |
| `write_timeout` | int | `10` | 写入超时（秒） |
| `buffer_size` | int | `8192` | I/O 操作缓冲区大小 |
| `use_stderr` | bool | `false` | 捕获 stderr 输出 |
| `validate_command` | bool | `true` | 验证命令是否存在 |

### 高级配置

```php
// 从数组创建
$config = StdioConfig::fromArray([
    'command' => 'node',
    'args' => ['server.js'],
    'env' => [
        'NODE_ENV' => 'production',
        'DEBUG' => 'mcp:*',
    ],
    'cwd' => '/path/to/server',
    'read_timeout' => 60,
    'buffer_size' => 16384,
]);

// 环境配置
$config->setEnv([
    'MCP_LOG_LEVEL' => 'debug',
    'DISABLE_THOUGHT_LOGGING' => 'true',
]);

// 超时配置
$config->setTimeout(45);
$config->setReadTimeout(60);
$config->setWriteTimeout(15);

// 不可变更新
$newConfig = $config->withChanges([
    'read_timeout' => 120,
    'buffer_size' => 32768,
]);
```

### 验证

```php
try {
    $config->validate();
    echo "配置有效\n";
} catch (ValidationError $e) {
    echo "无效配置：" . $e->getMessage() . "\n";
}
```

## HTTP 配置

`HttpConfig` 类为基于 Web 的 MCP 服务器配置 HTTP 传输。

### 基本使用

```php
use Dtyq\PhpMcp\Client\Configuration\HttpConfig;

// 简单配置
$config = new HttpConfig('https://api.example.com/mcp');

// 带认证
$config = new HttpConfig('https://api.example.com/mcp', [
    'headers' => [
        'Authorization' => 'Bearer your-token-here',
        'X-API-Key' => 'your-api-key',
    ],
    'timeout' => 30,
]);
```

### 配置选项

| 选项 | 类型 | 默认值 | 描述 |
|------|------|--------|------|
| `base_url` | string | 必需 | HTTP 请求的基础 URL |
| `headers` | array | `[]` | HTTP 头部 |
| `timeout` | int | `30` | 请求超时（秒） |
| `connect_timeout` | int | `10` | 连接超时（秒） |
| `protocol_version` | string | `'1.1'` | HTTP 协议版本 |
| `verify_ssl` | bool | `true` | 验证 SSL 证书 |
| `ca_bundle` | string\|null | `null` | CA 证书包路径 |
| `proxy` | string\|null | `null` | 代理服务器 URL |
| `auth` | array\|null | `null` | 认证配置 |
| `cookies` | bool | `false` | 启用 Cookie 处理 |
| `max_redirects` | int | `5` | 最大重定向次数 |
| `event_replay` | bool | `false` | 启用事件重放调试 |

### 高级配置

```php
// 从数组创建
$config = HttpConfig::fromArray([
    'base_url' => 'https://api.example.com/mcp',
    'headers' => [
        'Authorization' => 'Bearer token',
        'Content-Type' => 'application/json',
        'User-Agent' => 'PHP-MCP-Client/1.0',
    ],
    'timeout' => 60,
    'verify_ssl' => false, // 仅用于开发
    'proxy' => 'http://proxy.example.com:8080',
]);

// 头部管理
$config->setHeaders([
    'Authorization' => 'Bearer new-token',
    'X-Request-ID' => uniqid(),
]);

$config->addHeader('X-Custom-Header', 'value');

// 超时配置
$config->setTimeout(45);
$config->setConnectTimeout(15);

// SSL 配置
$config->setVerifySSL(true);
$config->setCABundle('/path/to/ca-bundle.pem');

// 认证
$config->setAuth([
    'username' => 'user',
    'password' => 'pass',
    'type' => 'basic',
]);

// 不可变更新
$newConfig = $config->withChanges([
    'timeout' => 120,
    'max_redirects' => 10,
]);
```

### SSL 和安全

```php
// 生产环境 SSL 配置
$config = new HttpConfig('https://api.example.com/mcp');
$config->setVerifySSL(true);
$config->setCABundle('/etc/ssl/certs/ca-certificates.crt');

// 开发环境配置（禁用 SSL 验证）
$config = new HttpConfig('https://localhost:8080/mcp');
$config->setVerifySSL(false); // 仅用于开发！
```

## 连接配置

`ConnectionConfig` 类为管理不同传输配置提供统一接口。

### 基本使用

```php
use Dtyq\PhpMcp\Client\Configuration\ConnectionConfig;
use Dtyq\PhpMcp\Client\Configuration\StdioConfig;
use Dtyq\PhpMcp\Client\Configuration\HttpConfig;

// 使用 STDIO 传输创建
$stdioConfig = new StdioConfig('npx', ['-y', '@modelcontextprotocol/server-sequential-thinking']);
$connectionConfig = new ConnectionConfig('stdio', $stdioConfig);

// 使用 HTTP 传输创建
$httpConfig = new HttpConfig('https://api.example.com/mcp');
$connectionConfig = new ConnectionConfig('http', $httpConfig);
```

### 工厂方法

```php
// 从数组创建，自动检测传输类型
$connectionConfig = ConnectionConfig::fromArray('stdio', [
    'command' => 'python',
    'args' => ['server.py'],
    'read_timeout' => 60,
]);

$connectionConfig = ConnectionConfig::fromArray('http', [
    'base_url' => 'https://api.example.com/mcp',
    'timeout' => 30,
    'headers' => ['Authorization' => 'Bearer token'],
]);
```

### 配置访问

```php
// 获取传输类型
$transportType = $connectionConfig->getTransportType(); // 'stdio' 或 'http'

// 获取特定配置
$stdioConfig = $connectionConfig->getStdioConfig(); // 返回 StdioConfig
$httpConfig = $connectionConfig->getHttpConfig();   // 返回 HttpConfig

// 序列化
$array = $connectionConfig->toArray();
$json = json_encode($connectionConfig);
```

## 配置示例

### Sequential Thinking 服务器

```php
// Sequential Thinking 服务器的 STDIO 配置
$config = new StdioConfig('npx', ['-y', '@modelcontextprotocol/server-sequential-thinking']);
$config->setEnv(['DISABLE_THOUGHT_LOGGING' => 'true']);
$config->setReadTimeout(60);

// 与客户端一起使用
$session = $client->stdio($config);
```

### 自定义 Python 服务器

```php
// 自定义 Python 服务器的 STDIO 配置
$config = new StdioConfig('python', ['my_server.py'], [
    'env' => [
        'PYTHONPATH' => '/path/to/modules',
        'MCP_LOG_LEVEL' => 'debug',
    ],
    'cwd' => '/path/to/server',
    'read_timeout' => 45,
]);

$session = $client->stdio($config);
```

### HTTP API 服务器

```php
// API 服务器的 HTTP 配置
$config = new HttpConfig('https://api.example.com/mcp');
$config->setHeaders([
    'Authorization' => 'Bearer ' . $apiToken,
    'X-API-Version' => '2024-01-01',
]);
$config->setTimeout(30);
$config->setVerifySSL(true);

$session = $client->http($config);
```

## 最佳实践

### 1. 使用类型安全配置

```php
// ✅ 推荐：使用类型化配置类
$config = new StdioConfig('npx', ['-y', '@modelcontextprotocol/server-sequential-thinking']);

// ❌ 避免：原始数组（已弃用）
$session = $client->connect('stdio', ['command' => 'npx']);
```

### 2. 验证配置

```php
try {
    $config->validate();
    $session = $client->stdio($config);
} catch (ValidationError $e) {
    throw new RuntimeException("无效配置：" . $e->getMessage());
}
```

### 3. 使用环境变量

```php
// 从环境加载敏感数据
$config = new HttpConfig($_ENV['MCP_SERVER_URL']);
$config->setHeaders([
    'Authorization' => 'Bearer ' . $_ENV['MCP_API_TOKEN'],
]);
```

### 4. 不可变更新

```php
// 创建变体而不修改原始配置
$baseConfig = new StdioConfig('python', ['server.py']);

$debugConfig = $baseConfig->withChanges([
    'env' => ['DEBUG' => '1'],
    'read_timeout' => 120,
]);

$prodConfig = $baseConfig->withChanges([
    'env' => ['ENVIRONMENT' => 'production'],
    'read_timeout' => 30,
]);
```

### 5. 配置序列化

```php
// 保存配置供以后使用
$configArray = $config->toArray();
file_put_contents('config.json', json_encode($configArray, JSON_PRETTY_PRINT));

// 加载配置
$configArray = json_decode(file_get_contents('config.json'), true);
$config = StdioConfig::fromArray($configArray);
```

## 错误处理

### 常见验证错误

```php
try {
    $config = new StdioConfig('', []); // 空命令
    $config->validate();
} catch (ValidationError $e) {
    // 处理验证错误
    echo "配置错误：" . $e->getMessage();
}
```

### 超时配置

```php
// 配置适当的超时
$config = new StdioConfig('slow-server', ['--mode', 'complex']);
$config->setReadTimeout(300);  // 复杂操作 5 分钟
$config->setWriteTimeout(60);  // 写入 1 分钟
```

## 迁移指南

### 从数组配置迁移

```php
// 旧方式（已弃用）
$session = $client->connect('stdio', [
    'command' => 'python',
    'args' => ['server.py'],
    'env' => ['DEBUG' => '1'],
]);

// 新方式（推荐）
$config = new StdioConfig('python', ['server.py']);
$config->setEnv(['DEBUG' => '1']);
$session = $client->stdio($config);
```

### 从 ConnectionConfig 迁移

```php
// 如果您使用 ConnectionConfig
$connectionConfig = ConnectionConfig::fromArray('stdio', $arrayConfig);
$session = $client->connect('stdio', $connectionConfig);

// 迁移到直接配置
$stdioConfig = StdioConfig::fromArray($arrayConfig);
$session = $client->stdio($stdioConfig);
```

此配置系统提供了一种健壮、类型安全的方式来配置 MCP 客户端连接，同时保持灵活性和易用性。 