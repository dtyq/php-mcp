<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Logging;

use InvalidArgumentException;
use Psr\Log\LogLevel;

/**
 * Stream handler for writing log records to streams.
 *
 * Writes log records to any PHP stream resource, including files,
 * stdout, stderr, or custom streams.
 */
class StreamHandler implements HandlerInterface
{
    /** @var resource */
    private $stream;

    private string $minLevel;

    private ?FormatterInterface $formatter;

    private bool $closeOnDestruct;

    /**
     * @param resource|string $stream Stream resource or path
     * @param string $minLevel Minimum log level to handle
     * @param null|FormatterInterface $formatter Log formatter
     */
    public function __construct(
        $stream,
        string $minLevel = LogLevel::DEBUG,
        ?FormatterInterface $formatter = null
    ) {
        if (is_string($stream)) {
            $this->stream = $this->openStream($stream);
            $this->closeOnDestruct = true;
        } elseif (is_resource($stream)) {
            $this->stream = $stream;
            $this->closeOnDestruct = false;
        } else {
            throw new InvalidArgumentException('Stream must be a resource or a string path');
        }

        $this->minLevel = $minLevel;
        $this->formatter = $formatter ?? new LineFormatter();
    }

    /**
     * Destructor - close stream if needed.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Handle a log record.
     *
     * @param LogRecord $record Log record to handle
     * @return bool True if handled successfully
     */
    public function handle(LogRecord $record): bool
    {
        if (! $this->isHandling($record->getLevel())) {
            return false;
        }

        $formatted = $this->formatter->format($record);
        $bytesWritten = fwrite($this->stream, $formatted);

        return $bytesWritten !== false;
    }

    /**
     * Handle multiple log records.
     *
     * @param LogRecord[] $records Log records to handle
     * @return bool True if all handled successfully
     */
    public function handleBatch(array $records): bool
    {
        $success = true;
        foreach ($records as $record) {
            if (! $this->handle($record)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Check if this handler can handle the given log level.
     *
     * @param string $level Log level to check
     * @return bool True if level can be handled
     */
    public function isHandling(string $level): bool
    {
        return $this->getLevelPriority($level) >= $this->getLevelPriority($this->minLevel);
    }

    /**
     * Set the minimum log level.
     *
     * @param string $level Minimum log level
     */
    public function setLevel(string $level): void
    {
        $this->minLevel = $level;
    }

    /**
     * Get the minimum log level.
     *
     * @return string Minimum log level
     */
    public function getLevel(): string
    {
        return $this->minLevel;
    }

    /**
     * Set the formatter.
     */
    public function setFormatter(FormatterInterface $formatter): self
    {
        $this->formatter = $formatter;

        return $this;
    }

    /**
     * Get the formatter.
     */
    public function getFormatter(): FormatterInterface
    {
        return $this->formatter;
    }

    /**
     * Close the handler and flush any pending records.
     */
    public function close(): void
    {
        if (is_resource($this->stream)) {
            fflush($this->stream);
            if ($this->closeOnDestruct) {
                fclose($this->stream);
            }
        }
    }

    /**
     * Open a stream from a file path.
     *
     * @param string $path File path
     * @return resource Stream resource
     */
    private function openStream(string $path)
    {
        // Handle special streams
        if ($path === 'php://stdout') {
            return STDOUT;
        }

        if ($path === 'php://stderr') {
            return STDERR;
        }

        // Ensure directory exists for file paths
        if (strpos($path, '://') === false) {
            $dir = dirname($path);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        $stream = fopen($path, 'a');
        if ($stream === false) {
            throw new InvalidArgumentException("Failed to open stream: {$path}");
        }

        return $stream;
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
