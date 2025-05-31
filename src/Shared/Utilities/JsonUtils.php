<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Utilities;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;
use Exception;
use JsonException;

/**
 * Utilities for working with JSON data in MCP context.
 *
 * This class provides standardized JSON encoding/decoding with proper error handling
 * and validation for MCP protocol messages.
 */
class JsonUtils
{
    /**
     * Default JSON encoding flags.
     */
    public const DEFAULT_ENCODE_FLAGS = JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    /**
     * Default JSON decoding flags.
     */
    public const DEFAULT_DECODE_FLAGS = JSON_THROW_ON_ERROR;

    /**
     * Pretty printing flags for debugging.
     */
    public const PRETTY_PRINT_FLAGS = self::DEFAULT_ENCODE_FLAGS | JSON_PRETTY_PRINT;

    /**
     * Maximum JSON depth for safety.
     */
    public const MAX_DEPTH = 512;

    /**
     * Encode data to JSON string with MCP defaults.
     *
     * @param mixed $data Data to encode
     * @param int $flags JSON encoding flags
     * @param int $depth Maximum depth
     * @return string JSON string
     * @throws ValidationError If encoding fails
     */
    public static function encode($data, int $flags = self::DEFAULT_ENCODE_FLAGS, int $depth = self::MAX_DEPTH): string
    {
        try {
            return json_encode($data, $flags, $depth);
        } catch (JsonException $e) {
            throw ValidationError::invalidJsonFormat("JSON encoding failed: {$e->getMessage()}");
        }
    }

    /**
     * Encode data to pretty-printed JSON for debugging.
     *
     * @param mixed $data Data to encode
     * @return string Pretty-printed JSON string
     * @throws ValidationError If encoding fails
     */
    public static function encodePretty($data): string
    {
        return self::encode($data, self::PRETTY_PRINT_FLAGS);
    }

    /**
     * Decode JSON string to PHP data with MCP defaults.
     *
     * @param string $json JSON string to decode
     * @param bool $associative Whether to return associative arrays or objects
     * @param int $depth Maximum depth
     * @param int $flags JSON decoding flags
     * @return mixed Decoded data
     * @throws ValidationError If decoding fails
     */
    public static function decode(
        string $json,
        bool $associative = true,
        int $depth = self::MAX_DEPTH,
        int $flags = self::DEFAULT_DECODE_FLAGS
    ) {
        try {
            return json_decode($json, $associative, $depth, $flags);
        } catch (Exception $e) {
            throw ValidationError::invalidJsonFormat("JSON decoding failed: {$e->getMessage()}");
        }
    }

    /**
     * Safely decode JSON string with error handling.
     *
     * @param string $json JSON string to decode
     * @param bool $associative Whether to return associative arrays or objects
     * @return array{success: bool, data?: mixed, error?: string}
     */
    public static function safeDecode(string $json, bool $associative = true): array
    {
        try {
            $data = self::decode($json, $associative);
            return ['success' => true, 'data' => $data];
        } catch (ValidationError $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Validate JSON string without decoding.
     *
     * @param string $json JSON string to validate
     * @return bool True if valid JSON
     */
    public static function isValid(string $json): bool
    {
        $result = self::safeDecode($json);
        return $result['success'];
    }

    /**
     * Get JSON validation error message.
     *
     * @param string $json JSON string to validate
     * @return null|string Error message or null if valid
     */
    public static function getValidationError(string $json): ?string
    {
        $result = self::safeDecode($json);
        return $result['error'] ?? null;
    }

    /**
     * Merge JSON objects as associative arrays.
     *
     * @param string[] $jsonStrings Array of JSON strings to merge
     * @param bool $recursive Whether to perform recursive merge
     * @return string Merged JSON string
     * @throws ValidationError If any JSON is invalid or merge fails
     */
    public static function merge(array $jsonStrings, bool $recursive = true): string
    {
        $arrays = [];

        foreach ($jsonStrings as $json) {
            $decoded = self::decode($json, true);
            if (! is_array($decoded)) {
                throw ValidationError::invalidJsonFormat('Can only merge JSON objects/arrays');
            }
            $arrays[] = $decoded;
        }

        if (empty($arrays)) {
            return self::encode([]);
        }

        $merged = array_shift($arrays);
        foreach ($arrays as $array) {
            if ($recursive) {
                $merged = array_merge_recursive($merged, $array);
            } else {
                $merged = array_merge($merged, $array);
            }
        }

        return self::encode($merged);
    }

    /**
     * Extract specific fields from JSON object.
     *
     * @param string $json JSON string
     * @param string[] $fields Field names to extract
     * @return string JSON string with only specified fields
     * @throws ValidationError If JSON is invalid or not an object
     */
    public static function extractFields(string $json, array $fields): string
    {
        $data = self::decode($json, true);

        if (! is_array($data)) {
            throw ValidationError::invalidJsonFormat('JSON must be an object to extract fields');
        }

        $extracted = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $extracted[$field] = $data[$field];
            }
        }

        return self::encode($extracted);
    }

    /**
     * Remove specified fields from JSON object.
     *
     * @param string $json JSON string
     * @param string[] $fields Field names to remove
     * @return string JSON string without specified fields
     * @throws ValidationError If JSON is invalid or not an object
     */
    public static function removeFields(string $json, array $fields): string
    {
        $data = self::decode($json, true);

        if (! is_array($data)) {
            throw ValidationError::invalidJsonFormat('JSON must be an object to remove fields');
        }

        foreach ($fields as $field) {
            unset($data[$field]);
        }

        return self::encode($data);
    }

    /**
     * Check if JSON object has required fields.
     *
     * @param string $json JSON string
     * @param string[] $requiredFields Required field names
     * @return bool True if all required fields are present
     * @throws ValidationError If JSON is invalid
     */
    public static function hasRequiredFields(string $json, array $requiredFields): bool
    {
        $data = self::decode($json, true);

        if (! is_array($data)) {
            return false;
        }

        foreach ($requiredFields as $field) {
            if (! array_key_exists($field, $data)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get missing required fields from JSON object.
     *
     * @param string $json JSON string
     * @param string[] $requiredFields Required field names
     * @return string[] Missing field names
     * @throws ValidationError If JSON is invalid
     */
    public static function getMissingFields(string $json, array $requiredFields): array
    {
        $data = self::decode($json, true);

        if (! is_array($data)) {
            return $requiredFields;
        }

        $missing = [];
        foreach ($requiredFields as $field) {
            if (! array_key_exists($field, $data)) {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    /**
     * Normalize JSON string by decoding and re-encoding.
     *
     * This removes extra whitespace and ensures consistent formatting.
     *
     * @param string $json JSON string to normalize
     * @return string Normalized JSON string
     * @throws ValidationError If JSON is invalid
     */
    public static function normalize(string $json): string
    {
        $data = self::decode($json);
        return self::encode($data);
    }

    /**
     * Calculate JSON string size in bytes.
     *
     * @param string $json JSON string
     * @return int Size in bytes
     */
    public static function getSize(string $json): int
    {
        return strlen($json);
    }

    /**
     * Check if JSON string exceeds size limit.
     *
     * @param string $json JSON string
     * @param int $maxSizeBytes Maximum size in bytes
     * @return bool True if size exceeds limit
     */
    public static function exceedsSize(string $json, int $maxSizeBytes): bool
    {
        return self::getSize($json) > $maxSizeBytes;
    }

    /**
     * Validate JSON-RPC 2.0 message structure.
     *
     * @param array<string, mixed> $data The decoded JSON data
     * @return bool True if valid JSON-RPC 2.0 message
     */
    public static function isValidJsonRpcMessage(array $data): bool
    {
        // Must be an associative array
        if (empty($data) || array_keys($data) === range(0, count($data) - 1)) {
            return false;
        }

        // Must have jsonrpc field with value "2.0"
        if (! isset($data['jsonrpc']) || $data['jsonrpc'] !== ProtocolConstants::JSONRPC_VERSION) {
            return false;
        }

        // Must be request, notification, response, or error
        $hasMethod = isset($data['method']);
        $hasId = isset($data['id']);
        $hasResult = isset($data['result']);
        $hasError = isset($data['error']);

        // Request: method + id
        if ($hasMethod && $hasId && ! $hasResult && ! $hasError) {
            return true;
        }

        // Notification: method, no id
        if ($hasMethod && ! $hasId && ! $hasResult && ! $hasError) {
            return true;
        }

        // Response: id + result or error (but not both)
        if (! $hasMethod && $hasId && ($hasResult xor $hasError)) {
            return true;
        }

        return false;
    }
}
