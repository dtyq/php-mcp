# PHP MCP

一个功能完整的 **MCP (Model Context Protocol)** 的 PHP 实现，提供服务器和客户端功能。

[![CI](https://github.com/dtyq/php-mcp/actions/workflows/ci.yml/badge.svg)](https://github.com/dtyq/php-mcp/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/dtyq/php-mcp/branch/master/graph/badge.svg)](https://codecov.io/gh/dtyq/php-mcp)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%20%7C%208.0%20%7C%208.1%20%7C%208.2%20%7C%208.3-blue)](composer.json)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Latest Version](https://img.shields.io/github/v/release/dtyq/php-mcp)](https://github.com/dtyq/php-mcp/releases)

## 📖 项目简介

PHP MCP 是 [Model Context Protocol](https://modelcontextprotocol.io/) 的原生 PHP 实现，基于官方 [Python SDK](https://github.com/modelcontextprotocol/python-sdk) 的设计思路。MCP 是一个标准化协议，使 AI 应用能够与外部数据源和工具进行安全、可控的交互。

### 🎯 设计目标

- **🔧 功能完整**: 实现完整的 MCP 协议规范
- **⚡ 高性能**: 针对 PHP 生态优化的高效实现
- **🔌 易集成**: 支持主流 PHP 框架 (Laravel, Symfony, Hyperf 等)
- **🛡️ 生产就绪**: 内置认证、授权、日志等企业级功能
- **🔄 协议兼容**: 与官方 Python SDK 完全兼容

## ✨ 核心特性

### 🚀 传输层支持
- **STDIO**: 标准输入输出传输 (开发调试)
- **HTTP SSE**: Server-Sent Events 传输 (实时通信)
- **WebSocket**: 双向实时通信 (低延迟)
- **Streamable HTTP**: 生产环境 HTTP 传输

### 🔧 MCP 功能
- **🛠️ Tools**: 工具定义、注册和调用
- **📄 Resources**: 资源管理和访问控制
- **💬 Prompts**: 提示模板和参数化
- **📊 Progress**: 进度报告和状态跟踪
- **📝 Logging**: 结构化日志和调试

### 🏗️ 架构特性
- **🎨 高级 API**: FastMCP 风格的声明式开发
- **🔌 框架集成**: 无缝集成主流 PHP 框架
- **🔐 安全认证**: OAuth 2.0、Bearer Token 等
- **📈 可扩展**: 模块化设计，易于扩展

## 📋 系统要求

- **PHP**: >= 7.4 (兼容 PHP 8.x)
- **扩展**: `json`, `curl`, `mbstring`
- **Composer**: 最新版本
- **内存**: 建议 >= 128MB

## 📦 安装

### Composer 安装 (推荐)

```bash
composer require dtyq/php-mcp
```

### 从源码安装

```bash
git clone https://github.com/dtyq/php-mcp.git
cd php-mcp
composer install
```

## 🤝 贡献指南

欢迎贡献代码、报告问题或提出改进建议！

### 贡献流程

1. Fork 项目到你的 GitHub 账户
2. 创建特性分支: `git checkout -b feature/amazing-feature`
3. 提交更改: `git commit -m 'feat: add amazing feature'`
4. 推送分支: `git push origin feature/amazing-feature`
5. 提交 Pull Request

### 开发规范

- 遵循 [PSR-12](https://www.php-fig.org/psr/psr-12/) 编码标准
- 编写完整的单元测试
- 更新相关文档
- 确保所有质量检查通过

## 🙏 致谢

- [Model Context Protocol](https://modelcontextprotocol.io/) - 官方协议规范
- [Python SDK](https://github.com/modelcontextprotocol/python-sdk) - 参考实现
- PHP 社区的优秀开源项目

## 📄 许可证

本项目基于 [MIT 许可证](LICENSE) 开源。

## 📞 联系方式

- **GitHub Issues**: [提交问题和建议](https://github.com/dtyq/php-mcp/issues)
- **讨论**: [GitHub Discussions](https://github.com/dtyq/php-mcp/discussions)

---

**⭐ 如果这个项目对你有帮助，请给我们一个 Star！** 