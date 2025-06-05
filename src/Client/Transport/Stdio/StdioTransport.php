<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client\Transport\Stdio;

use Dtyq\PhpMcp\Client\Configuration\StdioConfig;
use Dtyq\PhpMcp\Client\Core\TransportInterface;
use Dtyq\PhpMcp\Shared\Exceptions\ProtocolError;
use Dtyq\PhpMcp\Shared\Exceptions\TransportError;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Dtyq\PhpMcp\Shared\Kernel\Logger\LoggerProxy;
use Exception;

/**
 * Stdio transport implementation for MCP client.
 *
 * This transport communicates with MCP servers through standard
 * input/output streams of a child process, implementing the full
 * MCP stdio transport protocol.
 */
class StdioTransport implements TransportInterface
{
    /** @var array<string> Command to execute */
    private array $command;

    /** @var StdioConfig Transport configuration */
    private StdioConfig $config;

    /** @var Application Application instance for services */
    private Application $application;

    /** @var LoggerProxy Logger instance */
    private LoggerProxy $logger;

    /** @var null|ProcessManager Process manager instance */
    private ?ProcessManager $processManager = null;

    /** @var null|StreamHandler Stream handler instance */
    private ?StreamHandler $streamHandler = null;

    /** @var null|MessageParser Message parser instance */
    private ?MessageParser $messageParser = null;

    /** @var bool Whether the transport is connected */
    private bool $connected = false;

    /** @var null|float Timestamp when connection was established */
    private ?float $connectedAt = null;

    /**
     * @param array<string> $command Command and arguments to execute
     * @param StdioConfig $config Transport configuration
     * @param Application $application Application instance for services
     */
    public function __construct(array $command, StdioConfig $config, Application $application)
    {
        $this->command = $command;
        $this->config = $config;
        $this->application = $application;
        $this->logger = $application->getLogger();
    }

    /**
     * Destructor to ensure cleanup.
     */
    public function __destruct()
    {
        if ($this->connected) {
            try {
                $this->disconnect();
            } catch (Exception $e) {
                // Ignore errors during cleanup in destructor
            }
        }
    }

    public function connect(): void
    {
        if ($this->connected) {
            throw new TransportError('Transport is already connected');
        }

        try {
            $this->logger->info('Starting stdio transport connection', [
                'command' => $this->command,
                'config' => $this->config->toArray(),
            ]);

            // Initialize components
            $this->initializeComponents();

            // Start the process
            if ($this->processManager === null) {
                throw new TransportError('Process manager not initialized');
            }
            $this->processManager->start();

            // Initialize stream handler after process is started
            $this->initializeStreamHandler();
            if ($this->streamHandler === null) {
                throw new TransportError('Stream handler not initialized');
            }
            $this->streamHandler->initialize();

            $this->connected = true;
            $this->connectedAt = microtime(true);

            $this->logger->info('Stdio transport connected successfully', [
                'process_id' => $this->getProcessId(),
                'connected_at' => $this->connectedAt,
            ]);
        } catch (Exception $e) {
            $this->logger->error('Failed to connect stdio transport', [
                'error' => $e->getMessage(),
                'command' => $this->command,
            ]);
            $this->cleanup();
            throw new TransportError('Failed to connect: ' . $e->getMessage());
        }
    }

    public function send(string $message): void
    {
        $this->ensureConnected();

        try {
            // Validate message format if enabled
            if ($this->config->shouldValidateMessages()) {
                $this->validateOutgoingMessage($message);
            }

            // Send message through stream handler
            if ($this->streamHandler === null) {
                throw new TransportError('Stream handler not available');
            }
            $this->streamHandler->writeLine($message);
        } catch (Exception $e) {
            $this->logger->error('Failed to send message', [
                'direction' => 'outgoing',
                'error' => $e->getMessage(),
                'message_preview' => substr($message, 0, 100),
                'process_id' => $this->getProcessId(),
            ]);
            throw new TransportError('Failed to send message: ' . $e->getMessage());
        }
    }

    public function receive(?int $timeout = null): ?string
    {
        $this->ensureConnected();

        try {
            // Convert timeout to float for stream handler
            $timeoutFloat = $timeout !== null ? (float) $timeout : null;

            // Read message from stream handler
            if ($this->streamHandler === null) {
                throw new TransportError('Stream handler not available');
            }
            $message = $this->streamHandler->readLine($timeoutFloat);

            if ($message === null) {
                return null; // Timeout or EOF
            }

            // Validate and parse message if enabled
            if ($this->config->shouldValidateMessages()) {
                $this->validateIncomingMessage($message);
            }

            return $message;
        } catch (Exception $e) {
            $this->logger->error('Failed to receive message', [
                'direction' => 'incoming',
                'error' => $e->getMessage(),
                'timeout' => $timeout,
                'process_id' => $this->getProcessId(),
            ]);
            throw new TransportError('Failed to receive message: ' . $e->getMessage());
        }
    }

    public function isConnected(): bool
    {
        if (! $this->connected) {
            return false;
        }

        // Check if process is still running
        if ($this->processManager && ! $this->processManager->isRunning()) {
            $this->connected = false;
            return false;
        }

        return true;
    }

    public function disconnect(): void
    {
        if (! $this->connected) {
            return; // Already disconnected
        }

        $this->logger->info('Disconnecting stdio transport', [
            'process_id' => $this->getProcessId(),
            'uptime' => $this->connectedAt ? microtime(true) - $this->connectedAt : 0,
        ]);

        $this->cleanup();

        $this->logger->info('Stdio transport disconnected successfully');
    }

    public function getType(): string
    {
        return 'stdio';
    }

    /**
     * Get the command being executed.
     *
     * @return array<string> Command and arguments
     */
    public function getCommand(): array
    {
        return $this->command;
    }

    /**
     * Get the transport configuration.
     *
     * @return StdioConfig Configuration instance
     */
    public function getConfig(): StdioConfig
    {
        return $this->config;
    }

    /**
     * Get the application instance.
     *
     * @return Application Application instance
     */
    public function getApplication(): Application
    {
        return $this->application;
    }

    /**
     * Get connection statistics.
     *
     * @return array<string, mixed> Transport statistics and metrics
     */
    public function getStats(): array
    {
        $stats = [
            'connected' => $this->connected,
            'connectedAt' => $this->connectedAt,
            'command' => $this->command,
            'type' => $this->getType(),
        ];

        if ($this->processManager) {
            $stats['processId'] = $this->processManager->getProcessId();
            $stats['processRunning'] = $this->processManager->isRunning();
        }

        if ($this->streamHandler) {
            $stats['streamInitialized'] = $this->streamHandler->isInitialized();
            $stats['bufferContent'] = strlen($this->streamHandler->getBufferContent());
            $stats['errorLogEntries'] = count($this->streamHandler->getErrorLog());
        }

        if ($this->messageParser) {
            $stats['parser'] = $this->messageParser->getStats();
        }

        return $stats;
    }

    /**
     * Get error log from stderr capture.
     *
     * @return array<string> Error log entries
     */
    public function getErrorLog(): array
    {
        if (! $this->streamHandler) {
            return [];
        }

        return $this->streamHandler->getErrorLog();
    }

    /**
     * Get the process ID of the running process.
     *
     * @return null|int Process ID or null if not connected
     */
    public function getProcessId(): ?int
    {
        if (! $this->processManager) {
            return null;
        }

        return $this->processManager->getProcessId();
    }

    /**
     * Initialize all transport components.
     */
    private function initializeComponents(): void
    {
        // Create process manager
        $this->processManager = new ProcessManager($this->command, $this->config);

        // Create message parser
        $this->messageParser = new MessageParser($this->config);

        // Stream handler will be created after process starts
        // to ensure pipes are available
    }

    /**
     * Initialize stream handler after process is started.
     */
    private function initializeStreamHandler(): void
    {
        if (! $this->processManager) {
            throw new TransportError('Process manager not initialized');
        }

        $this->streamHandler = new StreamHandler($this->processManager, $this->config);
    }

    /**
     * Validate an outgoing message.
     *
     * @param string $message Message to validate
     * @throws ProtocolError If message is invalid
     */
    private function validateOutgoingMessage(string $message): void
    {
        if (! $this->messageParser) {
            return;
        }

        try {
            $data = $this->messageParser->parseMessage($message);

            // Additional validation for outgoing messages
            $messageType = $this->messageParser->detectMessageType($data);

            if ($messageType === 'unknown') {
                throw new ProtocolError('Unknown message type for outgoing message');
            }
        } catch (ProtocolError $e) {
            throw $e;
        } catch (Exception $e) {
            throw new ProtocolError('Invalid outgoing message format: ' . $e->getMessage());
        }
    }

    /**
     * Validate an incoming message.
     *
     * @param string $message Message to validate
     * @throws ProtocolError If message is invalid
     */
    private function validateIncomingMessage(string $message): void
    {
        if (! $this->messageParser) {
            return;
        }

        try {
            $data = $this->messageParser->parseMessage($message);

            if (! $this->messageParser->isValidMcpMessage($data)) {
                throw new ProtocolError('Invalid MCP message received');
            }
        } catch (ProtocolError $e) {
            throw $e;
        } catch (Exception $e) {
            throw new ProtocolError('Invalid incoming message format: ' . $e->getMessage());
        }
    }

    /**
     * Ensure the transport is connected.
     *
     * @throws TransportError If not connected
     */
    private function ensureConnected(): void
    {
        if (! $this->isConnected()) {
            throw new TransportError('Transport is not connected');
        }

        // Lazy initialize stream handler if needed
        if (! $this->streamHandler) {
            $this->initializeStreamHandler();
        }
    }

    /**
     * Extract message ID from a JSON-RPC message for logging purposes.
     *
     * @param string $message The message to extract ID from
     * @return null|string Message ID or null if not found
     */
    private function extractMessageId(string $message): ?string
    {
        try {
            $data = json_decode($message, true);
            if (is_array($data) && isset($data['id'])) {
                return (string) $data['id'];
            }
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Cleanup all resources and reset state.
     */
    private function cleanup(): void
    {
        // Close stream handler
        if ($this->streamHandler) {
            try {
                $this->streamHandler->close();
            } catch (Exception $e) {
                // Ignore errors during cleanup
            }
            $this->streamHandler = null;
        }

        // Stop process
        if ($this->processManager) {
            try {
                $this->processManager->stop();
            } catch (Exception $e) {
                // Ignore errors during cleanup
            }
            $this->processManager = null;
        }

        $this->messageParser = null;
        $this->connected = false;
        $this->connectedAt = null;
    }
}
