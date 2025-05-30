<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Content;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Core\BaseTypes;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;

/**
 * Audio content for MCP messages.
 *
 * Represents base64-encoded audio data with MIME type specification.
 * Supports various audio formats as defined in MCP 2025-03-26 specification.
 */
class AudioContent implements ContentInterface
{
    private string $type = ProtocolConstants::CONTENT_TYPE_AUDIO;

    private string $data;

    private string $mimeType;

    private ?Annotations $annotations = null;

    /**
     * @param string $data Base64-encoded audio data
     * @param string $mimeType Audio MIME type (e.g., audio/mpeg, audio/wav)
     * @param null|Annotations $annotations Optional annotations
     * @throws ValidationError If data or MIME type is invalid
     */
    public function __construct(string $data, string $mimeType, ?Annotations $annotations = null)
    {
        $this->setData($data);
        $this->setMimeType($mimeType);
        $this->annotations = $annotations;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the base64-encoded audio data.
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * Set the base64-encoded audio data.
     *
     * @throws ValidationError If data is not valid base64
     */
    public function setData(string $data): void
    {
        $data = trim($data);
        if (empty($data)) {
            throw ValidationError::invalidFieldValue('data', 'audio data cannot be empty');
        }

        // Validate base64 encoding
        if (! BaseTypes::isValidBase64($data)) {
            throw ValidationError::invalidFieldValue('data', 'must be valid base64-encoded audio data');
        }

        $this->data = $data;
    }

    /**
     * Get the audio MIME type.
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * Set the audio MIME type.
     *
     * @throws ValidationError If MIME type is invalid
     */
    public function setMimeType(string $mimeType): void
    {
        $mimeType = trim($mimeType);
        if (empty($mimeType)) {
            throw ValidationError::invalidFieldValue('mimeType', 'MIME type cannot be empty');
        }

        if (! $this->isValidAudioMimeType($mimeType)) {
            throw ValidationError::invalidFieldValue('mimeType', "unsupported audio MIME type: {$mimeType}");
        }

        $this->mimeType = $mimeType;
    }

    public function getAnnotations(): ?Annotations
    {
        return $this->annotations;
    }

    public function setAnnotations(?Annotations $annotations): void
    {
        $this->annotations = $annotations;
    }

    public function hasAnnotations(): bool
    {
        return $this->annotations !== null;
    }

    public function isTargetedTo(string $role): bool
    {
        return $this->annotations !== null ? $this->annotations->isTargetedTo($role) : false;
    }

    public function getPriority(): ?float
    {
        return $this->annotations !== null ? $this->annotations->getPriority() : null;
    }

    /**
     * Get the decoded audio data.
     *
     * @throws ValidationError If base64 decoding fails
     */
    public function getDecodedData(): string
    {
        $decoded = base64_decode($this->data, true);
        if ($decoded === false) {
            throw ValidationError::invalidFieldValue('data', 'failed to decode base64 audio data');
        }
        return $decoded;
    }

    /**
     * Get the size of the decoded audio data in bytes.
     */
    public function getDataSize(): int
    {
        return (int) (strlen($this->data) * 3 / 4);
    }

    /**
     * Get a summary of the audio content.
     */
    public function getSummary(): string
    {
        $size = $this->getDataSize();
        $sizeFormatted = BaseTypes::formatBytes($size);
        return "Audio ({$this->mimeType}, {$sizeFormatted})";
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $array = [
            'type' => $this->type,
            'data' => $this->data,
            'mimeType' => $this->mimeType,
        ];

        if ($this->annotations !== null) {
            $array['annotations'] = $this->annotations->toArray();
        }

        return $array;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    /**
     * Create AudioContent from array data.
     *
     * @param array<string, mixed> $data
     * @throws ValidationError If required fields are missing or invalid
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['data'])) {
            throw ValidationError::requiredFieldMissing('data', 'AudioContent');
        }

        if (! isset($data['mimeType'])) {
            throw ValidationError::requiredFieldMissing('mimeType', 'AudioContent');
        }

        if (! is_string($data['data'])) {
            throw ValidationError::invalidFieldType('data', 'string', gettype($data['data']));
        }

        if (! is_string($data['mimeType'])) {
            throw ValidationError::invalidFieldType('mimeType', 'string', gettype($data['mimeType']));
        }

        $annotations = null;
        if (isset($data['annotations'])) {
            if (! is_array($data['annotations'])) {
                throw ValidationError::invalidFieldType('annotations', 'array', gettype($data['annotations']));
            }
            $annotations = Annotations::fromArray($data['annotations']);
        }

        return new self($data['data'], $data['mimeType'], $annotations);
    }

    /**
     * Create AudioContent from file.
     *
     * @param string $filePath Path to the audio file
     * @param null|string $mimeType MIME type (auto-detected if null)
     * @param null|Annotations $annotations Optional annotations
     * @throws ValidationError If file cannot be read or is invalid
     */
    public static function fromFile(string $filePath, ?string $mimeType = null, ?Annotations $annotations = null): self
    {
        if (! file_exists($filePath)) {
            throw ValidationError::invalidFieldValue('filePath', "audio file not found: {$filePath}");
        }

        if (! is_readable($filePath)) {
            throw ValidationError::invalidFieldValue('filePath', "audio file not readable: {$filePath}");
        }

        $data = file_get_contents($filePath);
        if ($data === false) {
            throw ValidationError::invalidFieldValue('filePath', "failed to read audio file: {$filePath}");
        }

        $base64Data = base64_encode($data);

        if ($mimeType === null) {
            $mimeType = self::detectMimeTypeFromFile($filePath);
        }

        return new self($base64Data, $mimeType, $annotations);
    }

    /**
     * Detect MIME type from file extension.
     */
    private static function detectMimeTypeFromFile(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        switch ($extension) {
            case 'mp3':
                return ProtocolConstants::MIME_TYPE_AUDIO_MP3;
            case 'wav':
                return ProtocolConstants::MIME_TYPE_AUDIO_WAV;
            case 'ogg':
                return ProtocolConstants::MIME_TYPE_AUDIO_OGG;
            case 'm4a':
            case 'mp4':
                return ProtocolConstants::MIME_TYPE_AUDIO_M4A;
            case 'webm':
                return ProtocolConstants::MIME_TYPE_AUDIO_WEBM;
            default:
                throw ValidationError::invalidFieldValue('filePath', "unsupported audio file extension: {$extension}");
        }
    }

    /**
     * Check if a MIME type is a valid audio type.
     */
    private function isValidAudioMimeType(string $mimeType): bool
    {
        $supportedTypes = [
            ProtocolConstants::MIME_TYPE_AUDIO_MP3,
            ProtocolConstants::MIME_TYPE_AUDIO_WAV,
            ProtocolConstants::MIME_TYPE_AUDIO_OGG,
            ProtocolConstants::MIME_TYPE_AUDIO_M4A,
            ProtocolConstants::MIME_TYPE_AUDIO_WEBM,
        ];

        return in_array($mimeType, $supportedTypes, true) || substr($mimeType, 0, 6) === 'audio/';
    }
}
