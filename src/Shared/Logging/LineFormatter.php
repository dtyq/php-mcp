<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Logging;

/**
 * Line formatter for human-readable log output.
 *
 * Formats log records as single lines suitable for console output
 * and traditional log files.
 */
class LineFormatter implements FormatterInterface
{
    public const DEFAULT_FORMAT = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";

    private string $format;

    private string $dateFormat;

    private bool $allowInlineLineBreaks;

    /**
     * @param string $format Log line format template
     * @param string $dateFormat Date format for timestamps
     * @param bool $allowInlineLineBreaks Whether to allow line breaks in log messages
     */
    public function __construct(
        string $format = self::DEFAULT_FORMAT,
        string $dateFormat = 'Y-m-d H:i:s',
        bool $allowInlineLineBreaks = false
    ) {
        $this->format = $format;
        $this->dateFormat = $dateFormat;
        $this->allowInlineLineBreaks = $allowInlineLineBreaks;
    }

    /**
     * Format a log record as a single line.
     *
     * @param LogRecord $record Log record to format
     * @return string Formatted log line
     */
    public function format(LogRecord $record): string
    {
        $replacements = $this->getReplacements($record);

        $output = $this->format;
        foreach ($replacements as $placeholder => $value) {
            $output = str_replace($placeholder, $value, $output);
        }

        // Clean up unused placeholders
        $output = preg_replace('/%[a-zA-Z_]+%/', '', $output);

        if (! $this->allowInlineLineBreaks) {
            $output = str_replace(["\r\n", "\r", "\n"], ' ', $output);
        }

        return $output;
    }

    /**
     * Format multiple log records.
     *
     * @param LogRecord[] $records Log records to format
     * @return string Formatted log lines
     */
    public function formatBatch(array $records): string
    {
        $formatted = '';
        foreach ($records as $record) {
            $formatted .= $this->format($record);
        }

        return $formatted;
    }

    /**
     * Set the log line format.
     */
    public function setFormat(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Set the date format.
     */
    public function setDateFormat(string $dateFormat): self
    {
        $this->dateFormat = $dateFormat;

        return $this;
    }

    /**
     * Set whether to allow inline line breaks.
     */
    public function setAllowInlineLineBreaks(bool $allow): self
    {
        $this->allowInlineLineBreaks = $allow;

        return $this;
    }

    /**
     * Get replacement values for placeholders.
     *
     * @param LogRecord $record Log record
     * @return array<string, string> Placeholder to value mapping
     */
    private function getReplacements(LogRecord $record): array
    {
        $replacements = [
            '%message%' => $record->getFormattedMessage(),
            '%level%' => $record->getLevel(),
            '%level_name%' => strtoupper($record->getLevel()),
            '%datetime%' => $record->getDatetime()->format($this->dateFormat),
            '%channel%' => $record->getChannel() ?? 'app',
        ];

        // Add MCP-specific fields
        if ($record->getRequestId() !== null) {
            $replacements['%request_id%'] = $record->getRequestId();
        }

        if ($record->getSessionId() !== null) {
            $replacements['%session_id%'] = $record->getSessionId();
        }

        // Format context and extra data
        $replacements['%context%'] = $this->formatArray($record->getContext());
        $replacements['%extra%'] = $this->formatArray($record->getExtra());

        return $replacements;
    }

    /**
     * Format array data for output.
     *
     * @param array<string, mixed> $data Array to format
     */
    private function formatArray(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $formatted = [];
        foreach ($data as $key => $value) {
            $formatted[] = $key . '=' . $this->convertToString($value);
        }

        return '[' . implode(' ', $formatted) . ']';
    }

    /**
     * Convert a value to string representation.
     *
     * @param mixed $value Value to convert
     */
    private function convertToString($value): string
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

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[serialization failed]';
    }
}
