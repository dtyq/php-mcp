<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Shared\Exceptions;

use Dtyq\PhpMcp\Shared\Exceptions\ErrorCodes;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ErrorCodes class.
 * @internal
 */
class ErrorCodesTest extends TestCase
{
    public function testStandardJsonRpcErrorCodes(): void
    {
        $this->assertSame(-32700, ErrorCodes::PARSE_ERROR);
        $this->assertSame(-32600, ErrorCodes::INVALID_REQUEST);
        $this->assertSame(-32601, ErrorCodes::METHOD_NOT_FOUND);
        $this->assertSame(-32602, ErrorCodes::INVALID_PARAMS);
        $this->assertSame(-32603, ErrorCodes::INTERNAL_ERROR);
    }

    public function testMcpSpecificErrorCodes(): void
    {
        $this->assertSame(-32000, ErrorCodes::MCP_ERROR);
        $this->assertSame(-32001, ErrorCodes::TRANSPORT_ERROR);
        $this->assertSame(-32002, ErrorCodes::RESOURCE_NOT_FOUND);
        $this->assertSame(-32003, ErrorCodes::AUTHENTICATION_ERROR);
        $this->assertSame(-32004, ErrorCodes::AUTHORIZATION_ERROR);
        $this->assertSame(-32005, ErrorCodes::VALIDATION_ERROR);
        $this->assertSame(-32006, ErrorCodes::TOOL_NOT_FOUND);
        $this->assertSame(-32007, ErrorCodes::PROMPT_NOT_FOUND);
        $this->assertSame(-32008, ErrorCodes::PROTOCOL_ERROR);
        $this->assertSame(-32009, ErrorCodes::CAPABILITY_NOT_SUPPORTED);
    }

    public function testGetErrorMessageForStandardErrors(): void
    {
        $this->assertSame(
            'Parse error: Invalid JSON was received by the server',
            ErrorCodes::getErrorMessage(ErrorCodes::PARSE_ERROR)
        );
        $this->assertSame(
            'Method not found: The method does not exist / is not available',
            ErrorCodes::getErrorMessage(ErrorCodes::METHOD_NOT_FOUND)
        );
    }

    public function testGetErrorMessageForMcpErrors(): void
    {
        $this->assertSame(
            'Transport error: Error in the transport layer',
            ErrorCodes::getErrorMessage(ErrorCodes::TRANSPORT_ERROR)
        );
        $this->assertSame(
            'Protocol error: MCP protocol violation',
            ErrorCodes::getErrorMessage(ErrorCodes::PROTOCOL_ERROR)
        );
    }

    public function testGetErrorMessageForUnknownCode(): void
    {
        $unknownCode = -99999;
        $this->assertSame(
            "Unknown error (code: {$unknownCode})",
            ErrorCodes::getErrorMessage($unknownCode)
        );
    }

    public function testIsJsonRpcError(): void
    {
        $this->assertTrue(ErrorCodes::isJsonRpcError(ErrorCodes::PARSE_ERROR));
        $this->assertTrue(ErrorCodes::isJsonRpcError(ErrorCodes::INVALID_REQUEST));
        $this->assertTrue(ErrorCodes::isJsonRpcError(ErrorCodes::METHOD_NOT_FOUND));
        $this->assertTrue(ErrorCodes::isJsonRpcError(ErrorCodes::INVALID_PARAMS));
        $this->assertTrue(ErrorCodes::isJsonRpcError(ErrorCodes::INTERNAL_ERROR));

        $this->assertFalse(ErrorCodes::isJsonRpcError(ErrorCodes::MCP_ERROR));
        $this->assertFalse(ErrorCodes::isJsonRpcError(ErrorCodes::TRANSPORT_ERROR));
        $this->assertFalse(ErrorCodes::isJsonRpcError(-99999));
    }

    public function testIsReservedJsonRpcError(): void
    {
        $this->assertTrue(ErrorCodes::isReservedJsonRpcError(-32768)); // Start of reserved range
        $this->assertTrue(ErrorCodes::isReservedJsonRpcError(-32000)); // End of reserved range
        $this->assertTrue(ErrorCodes::isReservedJsonRpcError(ErrorCodes::PARSE_ERROR));
        $this->assertTrue(ErrorCodes::isReservedJsonRpcError(ErrorCodes::MCP_ERROR));

        $this->assertFalse(ErrorCodes::isReservedJsonRpcError(-31999)); // Outside range
        $this->assertFalse(ErrorCodes::isReservedJsonRpcError(-32769)); // Outside range
        $this->assertFalse(ErrorCodes::isReservedJsonRpcError(0));
    }

    public function testIsMcpError(): void
    {
        $this->assertTrue(ErrorCodes::isMcpError(ErrorCodes::MCP_ERROR));
        $this->assertTrue(ErrorCodes::isMcpError(ErrorCodes::TRANSPORT_ERROR));
        $this->assertTrue(ErrorCodes::isMcpError(ErrorCodes::PROTOCOL_ERROR));

        // Standard JSON-RPC errors should return false
        $this->assertFalse(ErrorCodes::isMcpError(ErrorCodes::PARSE_ERROR));
        $this->assertFalse(ErrorCodes::isMcpError(ErrorCodes::INVALID_REQUEST));
        $this->assertFalse(ErrorCodes::isMcpError(ErrorCodes::METHOD_NOT_FOUND));

        // Outside reserved range
        $this->assertFalse(ErrorCodes::isMcpError(-31999));
        $this->assertFalse(ErrorCodes::isMcpError(0));
    }
}
