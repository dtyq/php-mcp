<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Logging;

/**
 * Handler interface for log record processing.
 *
 * Handlers are responsible for writing log records to various destinations
 * such as files, streams, databases, or external services.
 */
interface HandlerInterface
{
    /**
     * Handle a log record.
     *
     * @param LogRecord $record Log record to handle
     * @return bool True if the record was handled successfully
     */
    public function handle(LogRecord $record): bool;

    /**
     * Handle multiple log records.
     *
     * @param LogRecord[] $records Log records to handle
     * @return bool True if all records were handled successfully
     */
    public function handleBatch(array $records): bool;

    /**
     * Check if this handler can handle the given log level.
     *
     * @param string $level Log level to check
     * @return bool True if level can be handled
     */
    public function isHandling(string $level): bool;

    /**
     * Set the minimum log level this handler will process.
     *
     * @param string $level Minimum log level
     */
    public function setLevel(string $level): void;

    /**
     * Get the minimum log level this handler processes.
     *
     * @return string Minimum log level
     */
    public function getLevel(): string;

    /**
     * Close the handler and flush any pending records.
     */
    public function close(): void;
}
