<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Content;

use Dtyq\PhpMcp\Types\Core\BaseTypes;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;
use InvalidArgumentException;

/**
 * Image content for MCP messages.
 *
 * Represents image content that can be included in messages, tool results,
 * and other MCP protocol structures. Images are encoded as base64 strings.
 */
class ImageContent implements ContentInterface
{
    /** @var string Content type identifier */
    private string $type = ProtocolConstants::CONTENT_TYPE_IMAGE;

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
        if (! isset($data['type']) || $data['type'] !== ProtocolConstants::CONTENT_TYPE_IMAGE) {
            throw new InvalidArgumentException('Invalid content type for ImageContent');
        }

        if (! isset($data['data'])) {
            throw new InvalidArgumentException('Data field is required for ImageContent');
        }

        if (! is_string($data['data'])) {
            throw new InvalidArgumentException('Data field must be a string');
        }

        if (! isset($data['mimeType'])) {
            throw new InvalidArgumentException('MimeType field is required for ImageContent');
        }

        if (! is_string($data['mimeType'])) {
            throw new InvalidArgumentException('MimeType field must be a string');
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
            throw new InvalidArgumentException("File does not exist: {$filePath}");
        }

        if (! is_readable($filePath)) {
            throw new InvalidArgumentException("File is not readable: {$filePath}");
        }

        $imageData = file_get_contents($filePath);
        if ($imageData === false) {
            throw new InvalidArgumentException("Failed to read file: {$filePath}");
        }

        $mimeType = self::detectMimeType($filePath);
        $base64Data = base64_encode($imageData);

        return new self($base64Data, $mimeType, $annotations);
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
        if (empty($data)) {
            throw new InvalidArgumentException('Image data cannot be empty');
        }

        // Validate base64 encoding
        if (! self::isValidBase64($data)) {
            throw new InvalidArgumentException('Image data must be valid base64 encoded');
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
        BaseTypes::validateMimeType($mimeType);

        if (! self::isImageMimeType($mimeType)) {
            throw new InvalidArgumentException("Invalid image MIME type: {$mimeType}");
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
        return $this->annotations !== null && ! $this->annotations->isEmpty();
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
    public function getBinaryData(): string
    {
        $decoded = base64_decode($this->data, true);
        if ($decoded === false) {
            throw new InvalidArgumentException('Failed to decode base64 image data');
        }
        return $decoded;
    }

    /**
     * Save image to file.
     */
    public function saveToFile(string $filePath): bool
    {
        $binaryData = $this->getBinaryData();
        return file_put_contents($filePath, $binaryData) !== false;
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
        return json_encode($this->toArray(), JSON_UNESCAPED_SLASHES);
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
     * Check if MIME type is for images.
     */
    private static function isImageMimeType(string $mimeType): bool
    {
        $imageMimeTypes = [
            ProtocolConstants::MIME_TYPE_IMAGE_PNG,
            ProtocolConstants::MIME_TYPE_IMAGE_JPEG,
            ProtocolConstants::MIME_TYPE_IMAGE_GIF,
            ProtocolConstants::MIME_TYPE_IMAGE_WEBP,
            'image/bmp',
            'image/svg+xml',
            'image/tiff',
        ];

        return in_array($mimeType, $imageMimeTypes, true)
               || strpos($mimeType, 'image/') === 0;
    }

    /**
     * Detect MIME type from file path.
     */
    private static function detectMimeType(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $mimeTypes = [
            'png' => ProtocolConstants::MIME_TYPE_IMAGE_PNG,
            'jpg' => ProtocolConstants::MIME_TYPE_IMAGE_JPEG,
            'jpeg' => ProtocolConstants::MIME_TYPE_IMAGE_JPEG,
            'gif' => ProtocolConstants::MIME_TYPE_IMAGE_GIF,
            'webp' => ProtocolConstants::MIME_TYPE_IMAGE_WEBP,
            'bmp' => 'image/bmp',
            'svg' => 'image/svg+xml',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
        ];

        if (isset($mimeTypes[$extension])) {
            return $mimeTypes[$extension];
        }

        // Fallback to finfo if available
        if (function_exists('finfo_file')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);
            finfo_close($finfo);

            if ($mimeType && self::isImageMimeType($mimeType)) {
                return $mimeType;
            }
        }

        throw new InvalidArgumentException("Cannot detect MIME type for file: {$filePath}");
    }
}
