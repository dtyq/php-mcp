<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Content;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Constants\MessageConstants;

/**
 * Image content for MCP messages.
 *
 * Represents image content that can be included in messages, tool results,
 * and other MCP protocol structures. Images are encoded as base64 strings.
 */
class ImageContent implements ContentInterface
{
    /** @var string Content type identifier */
    private string $type = MessageConstants::CONTENT_TYPE_IMAGE;

    /** @var string Base64-encoded image data */
    private string $data;

    /** @var string MIME type of the image */
    private string $mimeType;

    /** @var null|Annotations Content annotations */
    private ?Annotations $annotations;

    public function __construct(string $data, string $mimeType, ?Annotations $annotations = null)
    {
        $this->setData($data);
        $this->setMimeType($mimeType);
        $this->annotations = $annotations;
    }

    /**
     * Create from array representation.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['type']) || $data['type'] !== MessageConstants::CONTENT_TYPE_IMAGE) {
            throw ValidationError::invalidContentType(MessageConstants::CONTENT_TYPE_IMAGE, $data['type'] ?? 'unknown');
        }

        if (! isset($data['data'])) {
            throw ValidationError::requiredFieldMissing('data', 'ImageContent');
        }

        if (! is_string($data['data'])) {
            throw ValidationError::invalidFieldType('data', 'string', gettype($data['data']));
        }

        if (! isset($data['mimeType'])) {
            throw ValidationError::requiredFieldMissing('mimeType', 'ImageContent');
        }

        if (! is_string($data['mimeType'])) {
            throw ValidationError::invalidFieldType('mimeType', 'string', gettype($data['mimeType']));
        }

        $annotations = null;
        if (isset($data['annotations']) && is_array($data['annotations'])) {
            $annotations = Annotations::fromArray($data['annotations']);
        }

        return new self($data['data'], $data['mimeType'], $annotations);
    }

    /**
     * Create from file path.
     */
    public static function fromFile(string $filePath, ?Annotations $annotations = null): self
    {
        if (! file_exists($filePath)) {
            throw ValidationError::fileOperationError('read', $filePath, 'file does not exist');
        }

        if (! is_readable($filePath)) {
            throw ValidationError::fileOperationError('read', $filePath, 'file is not readable');
        }

        $data = file_get_contents($filePath);
        if ($data === false) {
            throw ValidationError::fileOperationError('read', $filePath, 'failed to read file content');
        }

        $mimeType = self::detectMimeType($filePath);
        return new self(base64_encode($data), $mimeType, $annotations);
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the base64-encoded image data.
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * Set the base64-encoded image data.
     */
    public function setData(string $data): void
    {
        if (empty(trim($data))) {
            throw ValidationError::emptyField('data');
        }

        if (! self::isValidBase64($data)) {
            throw ValidationError::invalidBase64('data');
        }

        $this->data = $data;
    }

    /**
     * Get the MIME type.
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * Set the MIME type.
     */
    public function setMimeType(string $mimeType): void
    {
        if (! self::isValidImageMimeType($mimeType)) {
            throw ValidationError::invalidFieldValue('mimeType', "invalid image MIME type: {$mimeType}");
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
        if (! $this->hasAnnotations()) {
            return true;
        }

        return $this->annotations->isTargetedTo($role);
    }

    public function getPriority(): ?float
    {
        if (! $this->hasAnnotations()) {
            return null;
        }

        return $this->annotations->getPriority();
    }

    /**
     * Get the estimated size of the image data in bytes.
     */
    public function getEstimatedSize(): int
    {
        // Base64 encoding increases size by ~33%
        return (int) (strlen($this->data) * 0.75);
    }

    /**
     * Get the raw binary image data.
     */
    public function getDecodedData(): string
    {
        $decoded = base64_decode($this->data, true);
        if ($decoded === false) {
            throw ValidationError::invalidBase64('data');
        }
        return $decoded;
    }

    /**
     * Save the image to a file.
     */
    public function saveToFile(string $filePath): bool
    {
        return file_put_contents($filePath, $this->getDecodedData()) !== false;
    }

    public function toArray(): array
    {
        $data = [
            'type' => $this->type,
            'data' => $this->data,
            'mimeType' => $this->mimeType,
        ];

        if ($this->hasAnnotations()) {
            $data['annotations'] = $this->annotations->toArray();
        }

        return $data;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Create a copy with different data.
     */
    public function withData(string $data): self
    {
        return new self($data, $this->mimeType, $this->annotations);
    }

    /**
     * Create a copy with different MIME type.
     */
    public function withMimeType(string $mimeType): self
    {
        return new self($this->data, $mimeType, $this->annotations);
    }

    /**
     * Create a copy with different annotations.
     */
    public function withAnnotations(?Annotations $annotations): self
    {
        return new self($this->data, $this->mimeType, $annotations);
    }

    /**
     * Validate base64 encoding.
     */
    private static function isValidBase64(string $data): bool
    {
        return base64_encode(base64_decode($data, true)) === $data;
    }

    /**
     * Validate image MIME type.
     */
    private static function isValidImageMimeType(string $mimeType): bool
    {
        $validTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/bmp',
            'image/svg+xml',
            'image/tiff',
            'image/ico',
            'image/x-icon',
        ];

        return in_array(strtolower($mimeType), $validTypes, true);
    }

    /**
     * Detect MIME type from file path.
     */
    private static function detectMimeType(string $filePath): string
    {
        $mimeType = mime_content_type($filePath);
        if ($mimeType === false || ! self::isValidImageMimeType($mimeType)) {
            throw ValidationError::fileOperationError('detect MIME type', $filePath, 'cannot detect valid image MIME type');
        }
        return $mimeType;
    }
}
