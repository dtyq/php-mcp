# PHP MCP

A complete PHP implementation of the **Model Context Protocol (MCP)**, providing both server and client functionality with support for multiple transport protocols.

[![CI](https://github.com/dtyq/php-mcp/actions/workflows/ci.yml/badge.svg)](https://github.com/dtyq/php-mcp/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/dtyq/php-mcp/branch/master/graph/badge.svg)](https://codecov.io/gh/dtyq/php-mcp)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%20%7C%208.0%20%7C%208.1%20%7C%208.2%20%7C%208.3-blue)](composer.json)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Latest Version](https://img.shields.io/github/v/release/dtyq/php-mcp)](https://github.com/dtyq/php-mcp/releases)

> **Language**: [English](./README.md) | [简体中文](./README_CN.md)

## ✨ Key Features

- 🚀 **Latest MCP Protocol** - Supports MCP 2025-03-26 specification
- 🔧 **Complete Implementation** - Tools, resources, and prompts support
- 🔌 **Multiple Transports** - STDIO ✅, HTTP ✅, Streamable HTTP 🚧
- 🌐 **Framework Compatible** - Works with any PSR-compliant framework, built-in Hyperf integration
- 📚 **Well Documented** - Comprehensive guides in English and Chinese

## 🚀 Quick Start

### Installation

```bash
composer require dtyq/php-mcp
```

### Hyperf Framework Quick Integration

If you're using Hyperf framework, integration is extremely simple:

```php
// Just one line of code!
Router::addRoute(['POST', 'GET', 'DELETE'], '/mcp', function () {
    return \Hyperf\Context\ApplicationContext::getContainer()->get(HyperfMcpServer::class)->handler();
});
```

**Annotation-Based Registration**:
```php
class CalculatorService
{
    #[McpTool(description: 'Mathematical calculations')]
    public function calculate(string $operation, int $a, int $b): array
    {
        return ['result' => match($operation) {
            'add' => $a + $b,
            'multiply' => $a * $b,
            default => 0
        }];
    }
    
    #[McpResource(description: 'System information')]
    public function systemInfo(): TextResourceContents
    {
        return new TextResourceContents('mcp://system/info', 
            json_encode(['php' => PHP_VERSION]), 'application/json');
    }
}
```

**Advanced Options**:
- 🔐 **AuthenticatorInterface** - Custom authentication
- 📊 **HttpTransportAuthenticatedEvent** - Dynamic tool/resource registration
- 📝 **Annotation System** - Auto-register tools, resources and prompts

👉 [View Complete Hyperf Integration Guide](./docs/en/server/hyperf-integration.md)

### Basic Server Example

```php
<?php
require_once 'vendor/autoload.php';

use Dtyq\PhpMcp\Server\McpServer;
use Dtyq\PhpMcp\Shared\Kernel\Application;

// Create server with simple container
$container = /* your PSR-11 container */;
$app = new Application($container, ['sdk_name' => 'my-server']);
$server = new McpServer('my-server', '1.0.0', $app);

// Add a tool
$server->registerTool(
    new \Dtyq\PhpMcp\Types\Tools\Tool('echo', [
        'type' => 'object',
        'properties' => ['message' => ['type' => 'string']],
        'required' => ['message']
    ], 'Echo a message'),
    function(array $args): array {
        return ['response' => $args['message']];
    }
);

// Start server
$server->stdio(); // or $server->http($request)
```

### Basic Client Example

```php
<?php
use Dtyq\PhpMcp\Client\McpClient;

$client = new McpClient('my-client', '1.0.0', $app);
$session = $client->connect('stdio', ['command' => 'php server.php']);
$session->initialize();

// Call a tool
$result = $session->callTool('echo', ['message' => 'Hello, MCP!']);
echo $result->getContent()[0]->getText();
```

## 📖 Documentation

- [**📚 Complete Documentation**](./docs/README.md) - All guides and references
- [**📖 Project Overview**](./docs/en/overview.md) - Architecture, features, and use cases
- [**🚀 Quick Start Guide**](./docs/en/quick-start.md) - 5-minute tutorial
- [**🔧 Server Guides**](./docs/en/server/) - Build MCP servers
- [**📡 Client Guides**](./docs/en/client/) - Create MCP clients

### Working Examples

Check the `/examples` directory:
- `stdio-server-test.php` - Complete STDIO server
- `http-server-test.php` - HTTP server with tools
- `stdio-client-test.php` - STDIO client example
- `http-client-test.php` - HTTP client example

## 🌟 Transport Protocols

| Protocol | Status | Description |
|----------|--------|-------------|
| STDIO | ✅ | Process communication |
| HTTP | ✅ | JSON-RPC over HTTP |
| Streamable HTTP | 🚧 | HTTP + Server-Sent Events |

## 🛠️ Requirements

- **PHP**: 7.4+ (8.0+ recommended)
- **Extensions**: json, mbstring, openssl, pcntl, curl
- **Composer**: For dependency management

## 🤝 Contributing

We welcome contributions! Please see our [issues page](https://github.com/dtyq/php-mcp/issues) for areas where you can help.

```bash
git clone https://github.com/dtyq/php-mcp.git
cd php-mcp
composer install
composer test
```

## 📄 License

MIT License - see [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- [Model Context Protocol](https://modelcontextprotocol.io/) for the specification
- [Anthropic](https://anthropic.com/) for creating MCP
- The PHP community for excellent tooling and support

---

**⭐ Star this repository if you find it useful!** 