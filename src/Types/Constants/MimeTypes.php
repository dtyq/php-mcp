<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Constants;

/**
 * MIME type constants for MCP protocol.
 *
 * Contains MIME type definitions for various content types
 * supported in MCP communication.
 */
final class MimeTypes
{
    // Text MIME types
    public const MIME_TYPE_TEXT_PLAIN = 'text/plain';

    public const MIME_TYPE_TEXT_HTML = 'text/html';

    public const MIME_TYPE_TEXT_MARKDOWN = 'text/markdown';

    // Application MIME types
    public const MIME_TYPE_APPLICATION_JSON = 'application/json';

    // Image MIME types
    public const MIME_TYPE_IMAGE_PNG = 'image/png';

    public const MIME_TYPE_IMAGE_JPEG = 'image/jpeg';

    public const MIME_TYPE_IMAGE_GIF = 'image/gif';

    public const MIME_TYPE_IMAGE_WEBP = 'image/webp';

    // Audio MIME types
    public const MIME_TYPE_AUDIO_MP3 = 'audio/mpeg';

    public const MIME_TYPE_AUDIO_WAV = 'audio/wav';

    public const MIME_TYPE_AUDIO_OGG = 'audio/ogg';

    public const MIME_TYPE_AUDIO_M4A = 'audio/mp4';

    public const MIME_TYPE_AUDIO_WEBM = 'audio/webm';

    /**
     * Get all text MIME types.
     *
     * @return array<string>
     */
    public static function getTextMimeTypes(): array
    {
        return [
            self::MIME_TYPE_TEXT_PLAIN,
            self::MIME_TYPE_TEXT_HTML,
            self::MIME_TYPE_TEXT_MARKDOWN,
        ];
    }

    /**
     * Get all image MIME types.
     *
     * @return array<string>
     */
    public static function getImageMimeTypes(): array
    {
        return [
            self::MIME_TYPE_IMAGE_PNG,
            self::MIME_TYPE_IMAGE_JPEG,
            self::MIME_TYPE_IMAGE_GIF,
            self::MIME_TYPE_IMAGE_WEBP,
        ];
    }

    /**
     * Get all audio MIME types.
     *
     * @return array<string>
     */
    public static function getAudioMimeTypes(): array
    {
        return [
            self::MIME_TYPE_AUDIO_MP3,
            self::MIME_TYPE_AUDIO_WAV,
            self::MIME_TYPE_AUDIO_OGG,
            self::MIME_TYPE_AUDIO_M4A,
            self::MIME_TYPE_AUDIO_WEBM,
        ];
    }

    /**
     * Get all supported MIME types.
     *
     * @return array<string>
     */
    public static function getAllSupportedMimeTypes(): array
    {
        return array_merge(
            self::getTextMimeTypes(),
            [self::MIME_TYPE_APPLICATION_JSON],
            self::getImageMimeTypes(),
            self::getAudioMimeTypes()
        );
    }

    /**
     * Check if a MIME type is for text content.
     */
    public static function isTextMimeType(string $mimeType): bool
    {
        return in_array($mimeType, self::getTextMimeTypes(), true);
    }

    /**
     * Check if a MIME type is for image content.
     */
    public static function isImageMimeType(string $mimeType): bool
    {
        return in_array($mimeType, self::getImageMimeTypes(), true);
    }

    /**
     * Check if a MIME type is for audio content.
     */
    public static function isAudioMimeType(string $mimeType): bool
    {
        return in_array($mimeType, self::getAudioMimeTypes(), true);
    }

    /**
     * Check if a MIME type is supported.
     */
    public static function isSupportedMimeType(string $mimeType): bool
    {
        return in_array($mimeType, self::getAllSupportedMimeTypes(), true);
    }

    /**
     * Get the primary content type from a MIME type.
     */
    public static function getPrimaryType(string $mimeType): string
    {
        $parts = explode('/', $mimeType, 2);
        return $parts[0] ?? '';
    }

    /**
     * Get the subtype from a MIME type.
     */
    public static function getSubtype(string $mimeType): string
    {
        $parts = explode('/', $mimeType, 2);
        return $parts[1] ?? '';
    }
}
