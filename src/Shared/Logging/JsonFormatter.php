<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Logging;

use DateTimeInterface;

/**
 * JSON formatter for structured logging.
 *
 * Formats log records as JSON objects, suitable for log aggregation
 * and analysis systems.
 */
class JsonFormatter implements FormatterInterface
{
    private bool $includeContext;

    private bool $includeExtra;

    private bool $prettyPrint;

    /**
     * @param bool $includeContext Include context data in output
     * @param bool $includeExtra Include extra metadata in output
     * @param bool $prettyPrint Pretty-print JSON output
     */
    public function __construct(
        bool $includeContext = true,
        bool $includeExtra = true,
        bool $prettyPrint = false
    ) {
        $this->includeContext = $includeContext;
        $this->includeExtra = $includeExtra;
        $this->prettyPrint = $prettyPrint;
    }

    /**
     * Format a log record as JSON.
     *
     * @param LogRecord $record Log record to format
     * @return string JSON formatted log message
     */
    public function format(LogRecord $record): string
    {
        $data = [
            'timestamp' => $record->getDatetime()->format(DateTimeInterface::RFC3339_EXTENDED),
            'level' => $record->getLevel(),
            'message' => $record->getFormattedMessage(),
        ];

        if ($record->getChannel() !== null) {
            $data['channel'] = $record->getChannel();
        }

        if ($record->getRequestId() !== null) {
            $data['request_id'] = $record->getRequestId();
        }

        if ($record->getSessionId() !== null) {
            $data['session_id'] = $record->getSessionId();
        }

        if ($this->includeContext && ! empty($record->getContext())) {
            $data['context'] = $record->getContext();
        }

        if ($this->includeExtra && ! empty($record->getExtra())) {
            $data['extra'] = $record->getExtra();
        }

        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        if ($this->prettyPrint) {
            $flags |= JSON_PRETTY_PRINT;
        }

        $json = json_encode($data, $flags);
        if ($json === false) {
            return json_encode([
                'timestamp' => $record->getDatetime()->format(DateTimeInterface::RFC3339_EXTENDED),
                'level' => 'error',
                'message' => 'Failed to encode log record as JSON',
                'error' => json_last_error_msg(),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
        }

        return $json;
    }

    /**
     * Format multiple log records as JSON array.
     *
     * @param LogRecord[] $records Log records to format
     * @return string JSON formatted log messages
     */
    public function formatBatch(array $records): string
    {
        $formatted = [];
        foreach ($records as $record) {
            $json = $this->format($record);
            $formatted[] = json_decode($json, true);
        }

        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        if ($this->prettyPrint) {
            $flags |= JSON_PRETTY_PRINT;
        }

        return json_encode($formatted, $flags) ?: '[]';
    }

    /**
     * Set whether to include context data.
     */
    public function setIncludeContext(bool $include): self
    {
        $this->includeContext = $include;

        return $this;
    }

    /**
     * Set whether to include extra metadata.
     */
    public function setIncludeExtra(bool $include): self
    {
        $this->includeExtra = $include;

        return $this;
    }

    /**
     * Set whether to pretty-print JSON.
     */
    public function setPrettyPrint(bool $prettyPrint): self
    {
        $this->prettyPrint = $prettyPrint;

        return $this;
    }
}
