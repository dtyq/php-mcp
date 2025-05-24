<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Logging;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * MCP-specific logger implementation.
 *
 * A PSR-3 compatible logger with MCP protocol enhancements,
 * including request/session tracking and structured output.
 */
class McpLogger extends AbstractLogger
{
    private string $channel;

    private string $minLevel;

    /** @var HandlerInterface[] */
    private array $handlers;

    /** @var callable[] */
    private array $processors;

    /** @var array<string, mixed> */
    private array $globalContext;

    /**
     * @param string $channel Logger channel name
     * @param string $minLevel Minimum log level to process
     * @param HandlerInterface[] $handlers Log handlers
     */
    public function __construct(
        string $channel = 'mcp',
        string $minLevel = LogLevel::DEBUG,
        array $handlers = []
    ) {
        $this->channel = $channel;
        $this->minLevel = $minLevel;
        $this->handlers = $handlers;
        $this->processors = [];
        $this->globalContext = [];
    }

    /**
     * Log with arbitrary level (PSR-3 implementation).
     *
     * @param mixed $level Log level
     * @param string $message Log message
     * @param array<string, mixed> $context Context data
     */
    public function log($level, $message, array $context = []): void
    {
        $level = (string) $level;

        if (! $this->isHandling($level)) {
            return;
        }

        // Create log record
        $record = new LogRecord(
            $level,
            $message,
            array_merge($this->globalContext, $context),
            null,
            [],
            $this->channel,
            $context['request_id'] ?? null,
            $context['session_id'] ?? null
        );

        // Apply processors
        foreach ($this->processors as $processor) {
            $record = $processor($record);
        }

        // Send to handlers
        foreach ($this->handlers as $handler) {
            $handler->handle($record);
        }
    }

    /**
     * Log an MCP protocol message.
     *
     * @param string $level Log level
     * @param string $direction Message direction (inbound/outbound)
     * @param array<string, mixed> $message MCP message data
     * @param array<string, mixed> $context Additional context
     */
    public function logMcpMessage(
        string $level,
        string $direction,
        array $message,
        array $context = []
    ): void {
        $context = array_merge($context, [
            'mcp_direction' => $direction,
            'mcp_method' => $message['method'] ?? null,
            'mcp_id' => $message['id'] ?? null,
        ]);

        $this->log($level, "MCP {$direction} message", $context);
    }

    /**
     * Log an MCP request.
     *
     * @param array<string, mixed> $request Request message
     * @param array<string, mixed> $context Additional context
     */
    public function logRequest(array $request, array $context = []): void
    {
        $this->logMcpMessage(LogLevel::INFO, 'inbound', $request, $context);
    }

    /**
     * Log an MCP response.
     *
     * @param array<string, mixed> $response Response message
     * @param array<string, mixed> $context Additional context
     */
    public function logResponse(array $response, array $context = []): void
    {
        $this->logMcpMessage(LogLevel::INFO, 'outbound', $response, $context);
    }

    /**
     * Log an MCP notification.
     *
     * @param array<string, mixed> $notification Notification message
     * @param array<string, mixed> $context Additional context
     */
    public function logNotification(array $notification, array $context = []): void
    {
        $this->logMcpMessage(LogLevel::INFO, 'notification', $notification, $context);
    }

    /**
     * Log an MCP error.
     *
     * @param array<string, mixed> $error Error message
     * @param array<string, mixed> $context Additional context
     */
    public function logError(array $error, array $context = []): void
    {
        $this->logMcpMessage(LogLevel::ERROR, 'error', $error, $context);
    }

    /**
     * Check if a log level would be processed.
     */
    public function isHandling(string $level): bool
    {
        return $this->getLevelPriority($level) >= $this->getLevelPriority($this->minLevel);
    }

    /**
     * Set the minimum log level.
     */
    public function setLevel(string $level): self
    {
        $this->minLevel = $level;

        return $this;
    }

    /**
     * Get the current minimum log level.
     */
    public function getLevel(): string
    {
        return $this->minLevel;
    }

    /**
     * Add a log handler.
     */
    public function pushHandler(HandlerInterface $handler): self
    {
        $this->handlers[] = $handler;

        return $this;
    }

    /**
     * Remove the last handler.
     */
    public function popHandler(): ?HandlerInterface
    {
        return array_pop($this->handlers);
    }

    /**
     * Add a processor to modify log records.
     */
    public function pushProcessor(callable $processor): self
    {
        $this->processors[] = $processor;

        return $this;
    }

    /**
     * Remove the last processor.
     */
    public function popProcessor(): ?callable
    {
        return array_pop($this->processors);
    }

    /**
     * Set global context that will be included in all log messages.
     *
     * @param array<string, mixed> $context Global context data
     */
    public function setGlobalContext(array $context): self
    {
        $this->globalContext = $context;

        return $this;
    }

    /**
     * Add to global context.
     *
     * @param array<string, mixed> $context Additional global context
     */
    public function addGlobalContext(array $context): self
    {
        $this->globalContext = array_merge($this->globalContext, $context);

        return $this;
    }

    /**
     * Create a child logger with additional context.
     *
     * @param array<string, mixed> $context Additional context data
     */
    public function withContext(array $context): self
    {
        $logger = clone $this;
        $logger->addGlobalContext($context);

        return $logger;
    }

    /**
     * Create a child logger for a specific MCP session.
     */
    public function forSession(string $sessionId): self
    {
        return $this->withContext(['session_id' => $sessionId]);
    }

    /**
     * Create a child logger for a specific MCP request.
     */
    public function forRequest(string $requestId): self
    {
        return $this->withContext(['request_id' => $requestId]);
    }

    /**
     * Get numeric priority for a log level.
     */
    private function getLevelPriority(string $level): int
    {
        $priorities = [
            LogLevel::DEBUG => 100,
            LogLevel::INFO => 200,
            LogLevel::NOTICE => 250,
            LogLevel::WARNING => 300,
            LogLevel::ERROR => 400,
            LogLevel::CRITICAL => 500,
            LogLevel::ALERT => 550,
            LogLevel::EMERGENCY => 600,
        ];

        return $priorities[$level] ?? 0;
    }
}
