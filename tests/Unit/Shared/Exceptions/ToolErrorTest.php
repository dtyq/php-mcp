<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Shared\Exceptions;

use Dtyq\PhpMcp\Shared\Exceptions\McpError;
use Dtyq\PhpMcp\Shared\Exceptions\ToolError;
use Exception;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Unit tests for ToolError class.
 * @internal
 */
class ToolErrorTest extends TestCase
{
    public function testToolErrorInheritsFromMcpError(): void
    {
        $toolError = ToolError::unknownTool('test_tool');

        $this->assertInstanceOf(ToolError::class, $toolError);
        $this->assertInstanceOf(McpError::class, $toolError);
        $this->assertInstanceOf(Exception::class, $toolError);
    }

    public function testUnknownToolError(): void
    {
        $toolName = 'nonexistent_tool';
        $error = ToolError::unknownTool($toolName);

        $this->assertEquals($toolName, $error->getToolName());
        $this->assertEquals("Unknown tool: {$toolName}", $error->getMessage());
        $this->assertEquals(-32601, $error->getErrorCode()); // Method not found
        $this->assertNull($error->getOriginalException());

        $errorData = $error->getErrorData();
        $this->assertIsArray($errorData);
        $this->assertEquals($toolName, $errorData['toolName']);
    }

    public function testValidationFailedError(): void
    {
        $toolName = 'test_tool';
        $reason = 'Missing required parameter';
        $error = ToolError::validationFailed($toolName, $reason);

        $this->assertEquals($toolName, $error->getToolName());
        $this->assertEquals("Tool validation failed for {$toolName}: {$reason}", $error->getMessage());
        $this->assertEquals(-32602, $error->getErrorCode()); // Invalid params
        $this->assertNull($error->getOriginalException());
    }

    public function testExecutionFailedError(): void
    {
        $toolName = 'database_tool';
        $originalException = new RuntimeException('Database connection failed');
        $error = ToolError::executionFailed($toolName, $originalException);

        $this->assertEquals($toolName, $error->getToolName());
        $this->assertEquals("Error executing tool {$toolName}: " . $originalException->getMessage(), $error->getMessage());
        $this->assertEquals(-32603, $error->getErrorCode()); // Internal error
        $this->assertSame($originalException, $error->getOriginalException());
    }

    public function testToArrayMethod(): void
    {
        $toolName = 'test_tool';
        $error = ToolError::unknownTool($toolName);
        $array = $error->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('code', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('data', $array);

        $this->assertEquals(-32601, $array['code']);
        $this->assertEquals("Unknown tool: {$toolName}", $array['message']);
        $this->assertEquals(['toolName' => $toolName], $array['data']);
    }

    public function testErrorDataStructure(): void
    {
        $toolName = 'test_tool';
        $error = ToolError::unknownTool($toolName);
        $errorData = $error->getErrorData();

        $this->assertIsArray($errorData);
        $this->assertArrayHasKey('toolName', $errorData);
        $this->assertEquals($toolName, $errorData['toolName']);
    }

    public function testOriginalExceptionChaining(): void
    {
        $originalException = new RuntimeException('Original error');
        $error = ToolError::executionFailed('test_tool', $originalException);

        // Test that the original exception is properly chained
        $this->assertSame($originalException, $error->getOriginalException());
        $this->assertSame($originalException, $error->getPrevious());
    }
}
