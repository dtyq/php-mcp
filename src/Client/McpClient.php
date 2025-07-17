<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client;

use Dtyq\PhpMcp\Client\Configuration\ClientConfig;
use Dtyq\PhpMcp\Client\Core\ClientStats;
use Dtyq\PhpMcp\Client\Session\ClientSession;
use Dtyq\PhpMcp\Client\Session\SessionManager;
use Dtyq\PhpMcp\Client\Session\SessionMetadata;
use Dtyq\PhpMcp\Client\Transport\TransportFactory;
use Dtyq\PhpMcp\Shared\Exceptions\TransportError;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Dtyq\PhpMcp\Shared\Kernel\Logger\LoggerProxy;
use Dtyq\PhpMcp\Types\Constants\TransportTypes;
use Exception;

/**
 * Main MCP client for connecting to MCP servers.
 *
 * This is the primary interface for users to interact with MCP servers.
 * It provides a simplified API for establishing connections and managing sessions.
 */
class McpClient
{
    private string $name;

    private string $version;

    private Application $application;

    /** @var SessionManager Session manager for handling multiple sessions */
    private SessionManager $sessionManager;

    /** @var ClientStats Connection statistics and metrics */
    private ClientStats $stats;

    private LoggerProxy $logger;

    /**
     * @param string $name Client name for identification
     * @param string $version Client version
     * @param Application $application Application container
     */
    public function __construct(
        string $name,
        string $version,
        Application $application
    ) {
        $this->name = $name;
        $this->version = $version;
        $this->application = $application;
        $this->logger = $application->getLogger();
        $this->sessionManager = new SessionManager();
        $this->stats = new ClientStats();
    }

    /**
     * Cleanup resources on destruction.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Connect to an MCP server using specified transport.
     *
     * @param string $transportType Transport type (e.g., 'stdio', 'http')
     * @param array<string, mixed> $config Transport configuration
     * @return ClientSession The created session
     * @throws TransportError If connection fails
     */
    public function connect(string $transportType, array $config): ClientSession
    {
        try {
            $session = $this->createSession($transportType, $config);

            $this->logger->info('Successfully connected to MCP server', ['session_id' => $session->getSessionId()]);

            $this->stats->recordConnectionAttempt();
            return $session;
        } catch (Exception $e) {
            $this->logger->error('Failed to connect to MCP server', [
                'error' => $e->getMessage(),
                'transport' => $transportType,
            ]);

            $this->stats->recordConnectionError();
            throw TransportError::startupFailed($transportType, $e->getMessage());
        }
    }

    /**
     * Close all active connections and sessions.
     */
    public function close(): void
    {
        try {
            $this->sessionManager->closeAll();

            $this->stats->recordClosure();
            $this->logger->info('MCP client closed successfully');
        } catch (Exception $e) {
            $this->logger->error('Error while closing MCP client', [
                'error' => $e->getMessage(),
                'client' => $this->name,
            ]);

            $this->stats->recordCloseError();
        }
    }

    /**
     * Get a specific session by ID.
     *
     * @param string $sessionId Session ID
     * @return ClientSession The session
     * @throws ValidationError If session not found
     */
    public function getSession(string $sessionId): ClientSession
    {
        return $this->sessionManager->getSession($sessionId);
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
     * Remove a specific session.
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
     *
     * @return ClientStats Statistics data
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
     * Create a session for the specified transport type.
     *
     * @param string $transportType Transport type
     * @param array<string, mixed> $config Transport configuration
     * @return ClientSession The created session
     * @throws ValidationError If configuration is invalid
     */
    private function createSession(string $transportType, array $config): ClientSession
    {
        switch ($transportType) {
            case TransportTypes::TRANSPORT_TYPE_STDIO:
                $session = $this->createStdioSession($config);
                break;
            case TransportTypes::TRANSPORT_TYPE_HTTP:
                $session = $this->createHttpSession($config);
                break;
            default:
                throw ValidationError::invalidFieldValue(
                    'transportType',
                    'Unsupported transport type',
                    [
                        'type' => $transportType,
                        'supported' => [
                            TransportTypes::TRANSPORT_TYPE_STDIO,
                            TransportTypes::TRANSPORT_TYPE_HTTP,
                        ],
                    ]
                );
        }
        $this->sessionManager->addSession($session->getSessionId(), $session);
        return $session;
    }

    /**
     * Create a stdio session.
     *
     * @param array<string, mixed> $config Stdio configuration
     * @return ClientSession The created session
     * @throws ValidationError If configuration is invalid
     */
    private function createStdioSession(array $config): ClientSession
    {
        // Validate required stdio config
        if (! isset($config['command'])) {
            throw ValidationError::emptyField('command');
        }

        // Normalize command to array BEFORE creating transport config
        $command = is_array($config['command']) ? $config['command'] : [$config['command']];

        // Add args if provided
        if (isset($config['args']) && is_array($config['args'])) {
            $command = array_merge($command, $config['args']);
        }

        // Create transport config with normalized command array
        $transportConfig = array_merge([
            'command' => $command, // This is now guaranteed to be an array
            'read_timeout' => 30.0,
            'write_timeout' => 30.0,
            'shutdown_timeout' => 5.0,
        ], $config);

        // Override the command in the config to ensure it's the normalized array
        $transportConfig['command'] = $command;

        // Create client config
        $clientConfig = new ClientConfig(
            TransportTypes::TRANSPORT_TYPE_STDIO,
            $transportConfig
        );

        // Create transport using factory
        $transport = TransportFactory::create(
            TransportTypes::TRANSPORT_TYPE_STDIO,
            $clientConfig,
            $this->application
        );

        // Connect transport
        $transport->connect();

        // Create session metadata
        $metadata = SessionMetadata::fromArray([
            'client_name' => $this->name,
            'client_version' => $this->version,
            'response_timeout' => $transportConfig['read_timeout'],
            'initialization_timeout' => $transportConfig['read_timeout'] * 2,
        ]);

        // Create session
        return new ClientSession($transport, $metadata);
    }

    /**
     * Create an HTTP session.
     *
     * @param array<string, mixed> $config HTTP configuration
     * @return ClientSession The created session
     * @throws ValidationError If configuration is invalid
     */
    private function createHttpSession(array $config): ClientSession
    {
        // Validate required HTTP config
        if (! isset($config['base_url'])) {
            throw ValidationError::emptyField('base_url');
        }

        // Create transport config with defaults
        $transportConfig = array_merge([
            'timeout' => 30.0,
            'sse_timeout' => 300.0,
            'max_retries' => 3,
            'retry_delay' => 1.0,
            'validate_ssl' => true,
            'user_agent' => 'php-mcp-client/1.0',
            'headers' => [],
            'auth' => null,
            'protocol_version' => 'auto',
            'enable_resumption' => true,
            'event_store_type' => 'memory',
            'event_store_config' => [],
            'json_response_mode' => false,
            'terminate_on_close' => true,
        ], $config);

        // Create client config
        $clientConfig = new ClientConfig(
            TransportTypes::TRANSPORT_TYPE_HTTP,
            $transportConfig
        );

        // Create transport using factory
        $transport = TransportFactory::create(
            TransportTypes::TRANSPORT_TYPE_HTTP,
            $clientConfig,
            $this->application
        );

        // Connect transport
        $transport->connect();

        // Create session metadata
        $metadata = SessionMetadata::fromArray([
            'client_name' => $this->name,
            'client_version' => $this->version,
            'response_timeout' => $transportConfig['timeout'],
            'initialization_timeout' => $transportConfig['timeout'] * 2,
        ]);

        // Create session
        return new ClientSession($transport, $metadata);
    }
}
