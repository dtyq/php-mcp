# PHP MCP

**模型上下文协议（MCP）** 的完整 PHP 实现，提供服务器和客户端功能，支持多种传输协议。

[![CI](https://github.com/dtyq/php-mcp/actions/workflows/ci.yml/badge.svg)](https://github.com/dtyq/php-mcp/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/dtyq/php-mcp/branch/master/graph/badge.svg)](https://codecov.io/gh/dtyq/php-mcp)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%20%7C%208.0%20%7C%208.1%20%7C%208.2%20%7C%208.3-blue)](composer.json)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Latest Version](https://img.shields.io/github/v/release/dtyq/php-mcp)](https://github.com/dtyq/php-mcp/releases)

> **语言版本**: [English](./README.md) | [简体中文](./README_CN.md)

## ✨ 核心特性

- 🚀 **最新 MCP 协议** - 支持 2025-03-26 版本的 MCP 协议
- 🔧 **完整实现** - 支持工具、资源和提示
- 🔌 **多种传输协议** - STDIO ✅、HTTP ✅、流式 HTTP 🚧
- 🌐 **框架兼容** - 兼容任何符合 PSR 标准的框架
- 📚 **文档完善** - 提供中英文完整指南

## 🚀 快速开始

### 安装

```bash
composer require dtyq/php-mcp
```

### 基础服务器示例

```php
<?php
require_once 'vendor/autoload.php';

use Dtyq\PhpMcp\Server\McpServer;
use Dtyq\PhpMcp\Shared\Kernel\Application;

// 使用简单容器创建服务器
$container = /* 您的 PSR-11 容器 */;
$app = new Application($container, ['sdk_name' => 'my-server']);
$server = new McpServer('my-server', '1.0.0', $app);

// 添加工具
$server->registerTool(
    new \Dtyq\PhpMcp\Types\Tools\Tool('echo', [
        'type' => 'object',
        'properties' => ['message' => ['type' => 'string']],
        'required' => ['message']
    ], '回显消息'),
    function(array $args): array {
        return ['response' => $args['message']];
    }
);

// 启动服务器
$server->stdio(); // 或 $server->http($request)
```

### 基础客户端示例

```php
<?php
use Dtyq\PhpMcp\Client\McpClient;

$client = new McpClient('my-client', '1.0.0', $app);
$session = $client->connect('stdio', ['command' => 'php server.php']);
$session->initialize();

// 调用工具
$result = $session->callTool('echo', ['message' => 'Hello, MCP!']);
echo $result->getContent()[0]->getText();
```

## 📖 文档

- [**📚 完整文档**](./docs/README.md) - 所有指南和参考
- [**📖 项目概览**](./docs/cn/overview.md) - 架构、功能和使用场景
- [**🚀 快速开始指南**](./docs/cn/quick-start.md) - 5分钟教程
- [**🔧 服务端指南**](./docs/cn/server/) - 构建 MCP 服务器
- [**📡 客户端指南**](./docs/cn/client/) - 创建 MCP 客户端

### 实用示例

查看 `/examples` 目录：
- `stdio-server-test.php` - 完整的 STDIO 服务器
- `http-server-test.php` - 带工具的 HTTP 服务器
- `stdio-client-test.php` - STDIO 客户端示例
- `http-client-test.php` - HTTP 客户端示例

## 🌟 传输协议

| 协议 | 状态 | 描述 |
|------|------|------|
| STDIO | ✅ | 进程通信 |
| HTTP | ✅ | HTTP 上的 JSON-RPC |
| 流式 HTTP | 🚧 | HTTP + 服务器发送事件 |

## 🛠️ 系统要求

- **PHP**: 7.4+（推荐 8.0+）
- **扩展**: json, mbstring, openssl, pcntl, curl
- **Composer**: 用于依赖管理

## 🤝 贡献

我们欢迎贡献！请查看我们的[问题页面](https://github.com/dtyq/php-mcp/issues)了解可以帮助的领域。

```bash
git clone https://github.com/dtyq/php-mcp.git
cd php-mcp
composer install
composer test
```

## 📄 许可证

MIT 许可证 - 详情请参阅 [LICENSE](LICENSE) 文件。

## 🙏 致谢

- [Model Context Protocol](https://modelcontextprotocol.io/) 提供规范
- [Anthropic](https://anthropic.com/) 创建 MCP
- PHP 社区提供出色的工具和支持

---

**如果您觉得有用，请给这个仓库点个星 ⭐！** 