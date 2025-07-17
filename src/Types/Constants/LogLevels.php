<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Constants;

/**
 * Logging level constants for MCP protocol.
 *
 * Contains all logging levels used in MCP communication,
 * following standard syslog severity levels.
 */
final class LogLevels
{
    // Logging levels (following RFC 5424 syslog severity levels)
    public const LOG_LEVEL_DEBUG = 'debug';

    public const LOG_LEVEL_INFO = 'info';

    public const LOG_LEVEL_NOTICE = 'notice';

    public const LOG_LEVEL_WARNING = 'warning';

    public const LOG_LEVEL_ERROR = 'error';

    public const LOG_LEVEL_CRITICAL = 'critical';

    public const LOG_LEVEL_ALERT = 'alert';

    public const LOG_LEVEL_EMERGENCY = 'emergency';

    /**
     * Get all valid logging levels.
     *
     * @return array<string>
     */
    public static function getValidLogLevels(): array
    {
        return array_keys(self::getLevelToSeverityMap());
    }

    /**
     * Check if a logging level is valid.
     */
    public static function isValidLogLevel(string $level): bool
    {
        $levelMap = self::getLevelToSeverityMap();
        return isset($levelMap[$level]);
    }

    /**
     * Get numeric severity for a log level (lower = more severe).
     *
     * @return int Numeric severity (0-7, where 0 is most severe)
     */
    public static function getSeverity(string $level): int
    {
        $levelMap = self::getLevelToSeverityMap();
        return $levelMap[$level] ?? 7; // Default to debug level
    }

    /**
     * Check if a level is more severe than another level.
     */
    public static function isMoreSevere(string $level1, string $level2): bool
    {
        return self::getSeverity($level1) < self::getSeverity($level2);
    }

    /**
     * Get the log level name from numeric severity.
     */
    public static function getLevelFromSeverity(int $severity): string
    {
        $severityMap = self::getSeverityToLevelMap();
        return $severityMap[$severity] ?? self::LOG_LEVEL_DEBUG;
    }

    /**
     * Get log level to severity mapping.
     *
     * @return array<string, int>
     */
    private static function getLevelToSeverityMap(): array
    {
        return [
            self::LOG_LEVEL_EMERGENCY => 0,
            self::LOG_LEVEL_ALERT => 1,
            self::LOG_LEVEL_CRITICAL => 2,
            self::LOG_LEVEL_ERROR => 3,
            self::LOG_LEVEL_WARNING => 4,
            self::LOG_LEVEL_NOTICE => 5,
            self::LOG_LEVEL_INFO => 6,
            self::LOG_LEVEL_DEBUG => 7,
        ];
    }

    /**
     * Get severity to log level mapping.
     *
     * @return array<int, string>
     */
    private static function getSeverityToLevelMap(): array
    {
        return [
            0 => self::LOG_LEVEL_EMERGENCY,
            1 => self::LOG_LEVEL_ALERT,
            2 => self::LOG_LEVEL_CRITICAL,
            3 => self::LOG_LEVEL_ERROR,
            4 => self::LOG_LEVEL_WARNING,
            5 => self::LOG_LEVEL_NOTICE,
            6 => self::LOG_LEVEL_INFO,
            7 => self::LOG_LEVEL_DEBUG,
        ];
    }
}
