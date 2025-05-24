<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Logging;

use DateTime;
use DateTimeInterface;
use Psr\Log\LogLevel;

/**
 * Represents a single log record with MCP-specific metadata.
 *
 * This class encapsulates all the information about a log entry,
 * compatible with PSR-3 logging standards while providing MCP-specific features.
 */
class LogRecord
{
    private string $level;

    private string $message;

    /** @var array<string, mixed> */
    private array $context;

    private DateTimeInterface $datetime;

    /** @var array<string, mixed> */
    private array $extra;

    private ?string $channel;

    private ?string $requestId;

    private ?string $sessionId;

    /**
     * @param string $level PSR-3 log level
     * @param string $message Log message
     * @param array<string, mixed> $context Context data
     * @param null|DateTimeInterface $datetime Log timestamp
     * @param array<string, mixed> $extra Extra metadata
     * @param null|string $channel Logger channel name
     * @param null|string $requestId MCP request ID
     * @param null|string $sessionId MCP session ID
     */
    public function __construct(
        string $level,
        string $message,
        array $context = [],
        ?DateTimeInterface $datetime = null,
        array $extra = [],
        ?string $channel = null,
        ?string $requestId = null,
        ?string $sessionId = null
    ) {
        $this->level = $level;
        $this->message = $message;
        $this->context = $context;
        $this->datetime = $datetime ?? new DateTime();
        $this->extra = $extra;
        $this->channel = $channel;
        $this->requestId = $requestId;
        $this->sessionId = $sessionId;
    }

    /**
     * Get the PSR-3 log level.
     */
    public function getLevel(): string
    {
        return $this->level;
    }

    /**
     * Get the log message.
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Get the context data.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get the log timestamp.
     */
    public function getDatetime(): DateTimeInterface
    {
        return $this->datetime;
    }

    /**
     * Get extra metadata.
     *
     * @return array<string, mixed>
     */
    public function getExtra(): array
    {
        return $this->extra;
    }

    /**
     * Get the logger channel name.
     */
    public function getChannel(): ?string
    {
        return $this->channel;
    }

    /**
     * Get the MCP request ID.
     */
    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    /**
     * Get the MCP session ID.
     */
    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    /**
     * Set the PSR-3 log level.
     */
    public function setLevel(string $level): self
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Set the log message.
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Set context data.
     *
     * @param array<string, mixed> $context
     */
    public function setContext(array $context): self
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Add context data.
     *
     * @param array<string, mixed> $context
     */
    public function addContext(array $context): self
    {
        $this->context = array_merge($this->context, $context);

        return $this;
    }

    /**
     * Set extra metadata.
     *
     * @param array<string, mixed> $extra
     */
    public function setExtra(array $extra): self
    {
        $this->extra = $extra;

        return $this;
    }

    /**
     * Add extra metadata.
     *
     * @param array<string, mixed> $extra
     */
    public function addExtra(array $extra): self
    {
        $this->extra = array_merge($this->extra, $extra);

        return $this;
    }

    /**
     * Set the logger channel name.
     */
    public function setChannel(?string $channel): self
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * Set the MCP request ID.
     */
    public function setRequestId(?string $requestId): self
    {
        $this->requestId = $requestId;

        return $this;
    }

    /**
     * Set the MCP session ID.
     */
    public function setSessionId(?string $sessionId): self
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Convert the log record to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'level' => $this->level,
            'message' => $this->message,
            'context' => $this->context,
            'datetime' => $this->datetime->format(DateTimeInterface::RFC3339_EXTENDED),
            'extra' => $this->extra,
            'channel' => $this->channel,
            'request_id' => $this->requestId,
            'session_id' => $this->sessionId,
        ];
    }

    /**
     * Get the message with context interpolation (PSR-3 compliant).
     *
     * Replaces placeholders in the message with values from context.
     */
    public function getFormattedMessage(): string
    {
        $message = $this->message;

        foreach ($this->context as $key => $value) {
            $placeholder = '{' . $key . '}';
            if (strpos($message, $placeholder) !== false) {
                $replacement = $this->contextValueToString($value);
                $message = str_replace($placeholder, $replacement, $message);
            }
        }

        return $message;
    }

    /**
     * Check if this record is an MCP protocol message.
     */
    public function isMcpMessage(): bool
    {
        return $this->requestId !== null || $this->sessionId !== null
            || isset($this->context['mcp_method'])
            || isset($this->extra['mcp_direction']);
    }

    /**
     * Get numeric priority for the log level.
     */
    public function getLevelPriority(): int
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

        return $priorities[$this->level] ?? 0;
    }

    /**
     * Convert a context value to string for interpolation.
     *
     * @param mixed $value Context value
     */
    private function contextValueToString($value): string
    {
        if (is_null($value)) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[serialization failed]';
        }

        return '[' . gettype($value) . ']';
    }
}
