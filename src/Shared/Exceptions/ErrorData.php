<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Exceptions;

use Dtyq\PhpMcp\Shared\Utilities\JsonUtils;
use JsonException;
use stdClass;

/**
 * Error information for JSON-RPC error responses.
 *
 * This class corresponds to the ErrorData from Python SDK and provides
 * structured error data compatible with JSON-RPC 2.0 specification.
 */
class ErrorData
{
    private int $code;

    private string $message;

    /** @var mixed Additional information about the error */
    private $data; // mixed type - PHP 7.4 compatible

    /**
     * Initialize ErrorData.
     *
     * @param int $code The error type that occurred
     * @param string $message A short description of the error. Should be limited to a concise single sentence
     * @param mixed $data Additional information about the error. Defined by the sender (e.g. detailed error information, nested errors etc.)
     */
    public function __construct(int $code, string $message, $data = null)
    {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }

    /**
     * Get the error code.
     *
     * @return int The error type that occurred
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * Get the error message.
     *
     * @return string A short description of the error
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Get additional error data.
     *
     * @return mixed Additional information about the error, or null if not provided
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Convert the error data to an array format.
     *
     * @return array{code: int, message: string, data?: mixed}
     */
    public function toArray(): array
    {
        $result = [
            'code' => $this->code,
            'message' => $this->message,
        ];

        if ($this->data !== null) {
            $result['data'] = $this->data;
            if (is_array($result['data']) && empty($result['data'])) {
                $result['data'] = new stdClass();
            }
        }

        return $result;
    }

    /**
     * Create ErrorData from an array.
     *
     * @param array{code: int, message: string, data?: mixed} $array
     */
    public static function fromArray(array $array): ErrorData
    {
        return new self(
            $array['code'],
            $array['message'],
            $array['data'] ?? null
        );
    }

    /**
     * Convert the error data to JSON string.
     *
     * @return string JSON representation of the error data
     */
    public function toJson(): string
    {
        return JsonUtils::encode($this->toArray());
    }

    /**
     * Create ErrorData from JSON string.
     *
     * @param string $json JSON string representing error data
     * @throws JsonException If JSON is invalid
     */
    public static function fromJson(string $json): ErrorData
    {
        $data = JsonUtils::decode($json, true);
        return self::fromArray($data);
    }
}
