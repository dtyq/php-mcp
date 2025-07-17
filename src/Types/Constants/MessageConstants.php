<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Constants;

/**
 * Message-related constants for MCP protocol.
 *
 * Contains constants for message roles, content types, stop reasons,
 * and reference types used in MCP communication.
 */
final class MessageConstants
{
    // Message roles
    public const ROLE_USER = 'user';

    public const ROLE_ASSISTANT = 'assistant';

    // Content types
    public const CONTENT_TYPE_TEXT = 'text';

    public const CONTENT_TYPE_IMAGE = 'image';

    public const CONTENT_TYPE_RESOURCE = 'resource';

    public const CONTENT_TYPE_AUDIO = 'audio';

    // Stop reasons for sampling
    public const STOP_REASON_END_TURN = 'endTurn';

    public const STOP_REASON_MAX_TOKENS = 'maxTokens';

    public const STOP_REASON_STOP_SEQUENCE = 'stopSequence';

    public const STOP_REASON_TOOL_USE = 'toolUse';

    // Reference types
    public const REF_TYPE_RESOURCE = 'ref/resource';

    public const REF_TYPE_PROMPT = 'ref/prompt';

    /**
     * Get all valid message roles.
     *
     * @return array<string>
     */
    public static function getValidRoles(): array
    {
        return [
            self::ROLE_USER,
            self::ROLE_ASSISTANT,
        ];
    }

    /**
     * Get all valid content types.
     *
     * @return array<string>
     */
    public static function getValidContentTypes(): array
    {
        return [
            self::CONTENT_TYPE_TEXT,
            self::CONTENT_TYPE_IMAGE,
            self::CONTENT_TYPE_RESOURCE,
            self::CONTENT_TYPE_AUDIO,
        ];
    }

    /**
     * Get all valid stop reasons.
     *
     * @return array<string>
     */
    public static function getValidStopReasons(): array
    {
        return [
            self::STOP_REASON_END_TURN,
            self::STOP_REASON_MAX_TOKENS,
            self::STOP_REASON_STOP_SEQUENCE,
            self::STOP_REASON_TOOL_USE,
        ];
    }

    /**
     * Get all valid reference types.
     *
     * @return array<string>
     */
    public static function getValidReferenceTypes(): array
    {
        return [
            self::REF_TYPE_RESOURCE,
            self::REF_TYPE_PROMPT,
        ];
    }

    /**
     * Check if a role is valid.
     */
    public static function isValidRole(string $role): bool
    {
        return in_array($role, self::getValidRoles(), true);
    }

    /**
     * Check if a content type is valid.
     */
    public static function isValidContentType(string $contentType): bool
    {
        return in_array($contentType, self::getValidContentTypes(), true);
    }

    /**
     * Check if a stop reason is valid.
     */
    public static function isValidStopReason(string $stopReason): bool
    {
        return in_array($stopReason, self::getValidStopReasons(), true);
    }

    /**
     * Check if a reference type is valid.
     */
    public static function isValidReferenceType(string $refType): bool
    {
        return in_array($refType, self::getValidReferenceTypes(), true);
    }
}
