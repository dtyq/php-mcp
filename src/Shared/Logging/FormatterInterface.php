<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Logging;

/**
 * Formatter interface for log records.
 *
 * Formatters convert LogRecord objects into string representations
 * suitable for various output destinations.
 */
interface FormatterInterface
{
    /**
     * Format a log record into a string.
     *
     * @param LogRecord $record Log record to format
     * @return string Formatted log message
     */
    public function format(LogRecord $record): string;

    /**
     * Format multiple log records.
     *
     * @param LogRecord[] $records Log records to format
     * @return string Formatted log messages
     */
    public function formatBatch(array $records): string;
}
