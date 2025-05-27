<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Resources;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;

/**
 * Binary contents of a resource.
 *
 * Represents binary content from a resource that can be embedded
 * in prompts or tool call results. Binary data is base64-encoded.
 */
class BlobResourceContents extends ResourceContents
{
    /** @var string Base64-encoded binary content */
    private string $blob;

    public function __construct(string $uri, string $blob, ?string $mimeType = null)
    {
        parent::__construct($uri, $mimeType);
        $this->setBlob($blob);
    }

    /**
     * Get the base64-encoded blob content.
     */
    public function getBlob(): string
    {
        return $this->blob;
    }

    /**
     * Set the base64-encoded blob content.
     */
    public function setBlob(string $blob): void
    {
        if (empty($blob)) {
            throw ValidationError::emptyField('blob');
        }

        if (! $this->isValidBase64($blob)) {
            throw ValidationError::invalidBase64('blob');
        }

        $this->blob = $blob;
    }

    public function isText(): bool
    {
        return false;
    }

    public function isBlob(): bool
    {
        return true;
    }

    public function getText(): ?string
    {
        return null;
    }

    /**
     * Get the raw binary data.
     */
    public function getBinaryData(): string
    {
        $decoded = base64_decode($this->blob, true);
        if ($decoded === false) {
            throw ValidationError::invalidBase64('blob');
        }
        return $decoded;
    }

    /**
     * Save blob to file.
     */
    public function saveToFile(string $filePath): bool
    {
        $binaryData = $this->getBinaryData();
        return file_put_contents($filePath, $binaryData) !== false;
    }

    /**
     * Create from file path.
     */
    public static function fromFile(string $uri, string $filePath, ?string $mimeType = null): self
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

        return new self($uri, base64_encode($data), $mimeType);
    }

    public function getEstimatedSize(): int
    {
        // Base64 encoding increases size by ~33%, so decode to get actual size
        return (int) (strlen($this->blob) * 0.75);
    }

    public function toArray(): array
    {
        $data = [
            'uri' => $this->uri,
            'blob' => $this->blob,
        ];

        if ($this->mimeType !== null) {
            $data['mimeType'] = $this->mimeType;
        }

        return $data;
    }

    /**
     * Create a copy with different blob.
     */
    public function withBlob(string $blob): self
    {
        return new self($this->uri, $blob, $this->mimeType);
    }

    /**
     * Create a copy with different URI.
     */
    public function withUri(string $uri): self
    {
        return new self($uri, $this->blob, $this->mimeType);
    }

    /**
     * Create a copy with different MIME type.
     */
    public function withMimeType(?string $mimeType): self
    {
        return new self($this->uri, $this->blob, $mimeType);
    }

    /**
     * Validate base64 encoding.
     */
    private function isValidBase64(string $data): bool
    {
        return base64_encode(base64_decode($data, true)) === $data;
    }
}
