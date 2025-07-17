<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client;

use Dtyq\PhpMcp\Client\Configuration\ConnectionConfig;
use Dtyq\PhpMcp\Client\Configuration\HttpConfig;
use Dtyq\PhpMcp\Client\Configuration\StdioConfig;
use Dtyq\PhpMcp\Client\Core\ClientStats;
use Dtyq\PhpMcp\Client\Session\ClientSession;
use Dtyq\PhpMcp\Client\Session\SessionManager;
use Dtyq\PhpMcp\Client\Session\SessionMetadata;
use Dtyq\PhpMcp\Client\Transport\TransportFactory;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Dtyq\PhpMcp\Types\Constants\TransportTypes;

/**
 * MCP client for connecting to MCP servers.
 * 用于连接 MCP 服务器的 MCP 客户端。
 *
 * This client provides a high-level interface for connecting to MCP servers
 * using different transport mechanisms (stdio, HTTP) with type-safe configuration
 * and comprehensive session management.
 * 此客户端提供了一个高级接口，用于使用不同的传输机制（stdio、HTTP）
 * 连接到 MCP 服务器，具有类型安全的配置和全面的会话管理。
 */
class McpClient
{
    /**
     * Client name for identification.
     * 用于标识的客户端名称。
     */
    private string $name;

    /**
     * Client version.
     * 客户端版本。
     */
    private string $version;

    /**
     * Application instance.
     * 应用程序实例。
     */
    private Application $application;

    /**
     * Session manager for managing client sessions.
     * 用于管理客户端会话的会话管理器。
     */
    private SessionManager $sessionManager;

    /**
     * Client statistics.
     * 客户端统计信息。
     */
    private ClientStats $stats;

    /**
     * @param string $name Client name / 客户端名称
     * @param string $version Client version / 客户端版本
     * @param Application $application Application instance / 应用程序实例
     */
    public function __construct(string $name, string $version, Application $application)
    {
        $this->name = $name;
        $this->version = $version;
        $this->application = $application;
        $this->sessionManager = new SessionManager();
        $this->stats = new ClientStats();
    }

    /**
     * Create a stdio connection with stdio configuration.
     * 使用 stdio 配置创建 stdio 连接。
     *
     * This is a convenience method that directly accepts a StdioConfig object
     * and creates a stdio transport connection.
     * 这是一个便捷方法，直接接受 StdioConfig 对象并创建 stdio 传输连接。
     *
     * @param StdioConfig $config Stdio configuration / Stdio 配置
     * @return ClientSession The created session / 创建的会话
     * @throws ValidationError If configuration is invalid / 如果配置无效
     */
    public function stdio(StdioConfig $config): ClientSession
    {
        return $this->createStdioSession($config);
    }

    /**
     * Create an HTTP connection with HTTP configuration.
     * 使用 HTTP 配置创建 HTTP 连接。
     *
     * This is a convenience method that directly accepts an HttpConfig object
     * and creates an HTTP transport connection.
     * 这是一个便捷方法，直接接受 HttpConfig 对象并创建 HTTP 传输连接。
     *
     * @param HttpConfig $config HTTP configuration / HTTP 配置
     * @return ClientSession The created session / 创建的会话
     * @throws ValidationError If configuration is invalid / 如果配置无效
     */
    public function http(HttpConfig $config): ClientSession
    {
        return $this->createHttpSession($config);
    }

    /**
     * Connect to an MCP server.
     * 连接到 MCP 服务器。
     *
     * @deprecated Use stdio() or http() shortcut methods instead. This method may be removed in future versions.
     * @deprecated 建议使用 stdio() 或 http() 快捷方法。此方法可能在未来版本中被移除。
     *
     * @param string $transportType Transport type (stdio, http) / 传输类型（stdio、http）
     * @param array<string, mixed>|ConnectionConfig $config Configuration / 配置
     * @return ClientSession The created session / 创建的会话
     * @throws ValidationError If transport type is invalid or configuration is malformed / 如果传输类型无效或配置格式错误
     */
    public function connect(string $transportType, $config = []): ClientSession
    {
        // Validate transport type
        $this->validateTransportType($transportType);

        // Convert array config to ConnectionConfig if needed
        if (is_array($config)) {
            $connectionConfig = ConnectionConfig::fromArray($transportType, $config);
        } elseif ($config instanceof ConnectionConfig) {
            $connectionConfig = $config;

            // Validate that transport type matches
            if ($connectionConfig->getTransportType() !== $transportType) {
                throw ValidationError::invalidFieldValue(
                    'transportType',
                    'Transport type mismatch',
                    [
                        'expected' => $transportType,
                        'actual' => $connectionConfig->getTransportType(),
                    ]
                );
            }
        } else {
            throw ValidationError::invalidFieldType(
                'config',
                'array or ConnectionConfig',
                gettype($config)
            );
        }

        // Create session based on transport type
        switch ($transportType) {
            case TransportTypes::TRANSPORT_TYPE_STDIO:
                return $this->createStdioSession($connectionConfig->getStdioConfig());
            case TransportTypes::TRANSPORT_TYPE_HTTP:
                return $this->createHttpSession($connectionConfig->getHttpConfig());
            default:
                throw ValidationError::invalidFieldValue(
                    'transportType',
                    'Unsupported transport type',
                    ['type' => $transportType]
                );
        }
    }

    /**
     * Disconnect from a server session.
     *
     * @param string $sessionId Session ID to disconnect
     * @throws ValidationError If session is not found
     */
    public function disconnect(string $sessionId): void
    {
        $session = $this->getSession($sessionId);
        if ($session === null) {
            throw ValidationError::invalidFieldValue(
                'sessionId',
                'Session not found',
                ['sessionId' => $sessionId]
            );
        }

        $session->close();
        $this->sessionManager->removeSession($sessionId);
    }

    /**
     * Get a session by ID.
     *
     * @param string $sessionId Session ID
     * @return null|ClientSession Session instance or null if not found
     */
    public function getSession(string $sessionId): ?ClientSession
    {
        try {
            return $this->sessionManager->getSession($sessionId);
        } catch (ValidationError $e) {
            return null;
        }
    }

    /**
     * Get all active sessions.
     *
     * @return array<string, ClientSession> Array of session ID => session
     */
    public function getSessions(): array
    {
        $sessions = [];
        foreach ($this->sessionManager->getSessionIds() as $sessionId) {
            $session = $this->getSession($sessionId);
            if ($session !== null) {
                $sessions[$sessionId] = $session;
            }
        }
        return $sessions;
    }

    /**
     * Get session manager.
     * 获取会话管理器。
     *
     * @return SessionManager Session manager instance / 会话管理器实例
     */
    public function getSessionManager(): SessionManager
    {
        return $this->sessionManager;
    }

    /**
     * Get client name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get client version.
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Get application instance.
     */
    public function getApplication(): Application
    {
        return $this->application;
    }

    /**
     * Close all active connections and sessions.
     */
    public function close(): void
    {
        $this->sessionManager->closeAll();
    }

    /**
     * Check if a session exists.
     *
     * @param string $sessionId Session ID
     * @return bool True if session exists
     */
    public function hasSession(string $sessionId): bool
    {
        return $this->sessionManager->hasSession($sessionId);
    }

    /**
     * Get all session IDs.
     *
     * @return array<string> Array of session IDs
     */
    public function getSessionIds(): array
    {
        return $this->sessionManager->getSessionIds();
    }

    /**
     * Get total number of sessions.
     *
     * @return int Number of sessions
     */
    public function getSessionCount(): int
    {
        return $this->sessionManager->getSessionCount();
    }

    /**
     * Remove a session from the manager.
     *
     * @param string $sessionId Session ID to remove
     * @return bool True if session was removed
     */
    public function removeSession(string $sessionId): bool
    {
        return $this->sessionManager->removeSession($sessionId);
    }

    /**
     * Get client statistics.
     * 获取客户端统计信息。
     *
     * @return ClientStats Client statistics / 客户端统计信息
     */
    public function getStats(): ClientStats
    {
        // Get basic session statistics
        $sessionStats = $this->sessionManager->getStats();

        // Update client stats with session data
        $this->stats->updateSessionStats($sessionStats);

        return $this->stats;
    }

    /**
     * Validate transport type.
     * 验证传输类型。
     *
     * @param string $transportType Transport type to validate / 要验证的传输类型
     * @throws ValidationError If transport type is invalid / 如果传输类型无效
     */
    private function validateTransportType(string $transportType): void
    {
        $supportedTypes = [
            TransportTypes::TRANSPORT_TYPE_STDIO,
            TransportTypes::TRANSPORT_TYPE_HTTP,
        ];

        if (! in_array($transportType, $supportedTypes, true)) {
            throw ValidationError::invalidFieldValue(
                'transportType',
                'Unsupported transport type',
                [
                    'type' => $transportType,
                    'supported' => $supportedTypes,
                ]
            );
        }
    }

    /**
     * Create stdio session with configuration.
     * 使用配置创建 stdio 会话。
     *
     * @param StdioConfig $stdioConfig Stdio configuration / Stdio 配置
     * @return ClientSession The created session / 创建的会话
     * @throws ValidationError If configuration is invalid / 如果配置无效
     */
    private function createStdioSession(StdioConfig $stdioConfig): ClientSession
    {
        // Validate configuration before creating session
        $stdioConfig->validate();

        // Create transport using factory with config object
        $transport = TransportFactory::create($stdioConfig, $this->application);

        // Connect transport
        $transport->connect();

        // Create session metadata
        $metadata = SessionMetadata::fromArray([
            'client_name' => $this->name,
            'client_version' => $this->version,
            'response_timeout' => $stdioConfig->getReadTimeout(),
            'initialization_timeout' => $stdioConfig->getReadTimeout() * 2,
        ]);

        // Create session
        $session = new ClientSession($transport, $metadata);

        // Add session to manager
        $this->sessionManager->addSession($session->getSessionId(), $session);

        return $session;
    }

    /**
     * Create HTTP session with configuration.
     * 使用配置创建 HTTP 会话。
     *
     * @param HttpConfig $httpConfig HTTP configuration / HTTP 配置
     * @return ClientSession The created session / 创建的会话
     * @throws ValidationError If configuration is invalid / 如果配置无效
     */
    private function createHttpSession(HttpConfig $httpConfig): ClientSession
    {
        // Validate configuration before creating session
        $httpConfig->validate();

        // Create transport using factory with config object
        $transport = TransportFactory::create($httpConfig, $this->application);

        // Connect transport
        $transport->connect();

        // Create session metadata
        $metadata = SessionMetadata::fromArray([
            'client_name' => $this->name,
            'client_version' => $this->version,
            'response_timeout' => $httpConfig->getTimeout(),
            'initialization_timeout' => $httpConfig->getTimeout() * 2,
        ]);

        // Create session
        $session = new ClientSession($transport, $metadata);

        // Add session to manager
        $this->sessionManager->addSession($session->getSessionId(), $session);

        return $session;
    }
}
