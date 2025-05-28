<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Server\FastMcp\Tools;

use Dtyq\PhpMcp\Server\FastMcp\Tools\RegisteredTool;
use Dtyq\PhpMcp\Server\FastMcp\Tools\ToolManager;
use Dtyq\PhpMcp\Shared\Exceptions\ToolError;
use Dtyq\PhpMcp\Types\Tools\Tool;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ToolManager class.
 * @internal
 */
class ToolManagerTest extends TestCase
{
    private ToolManager $toolManager;

    private RegisteredTool $sampleTool;

    protected function setUp(): void
    {
        $this->toolManager = new ToolManager();

        $schema = [
            'type' => 'object',
            'properties' => [
                'a' => ['type' => 'integer'],
                'b' => ['type' => 'integer'],
            ],
            'required' => ['a', 'b'],
        ];

        $callable = function (array $args): int {
            return $args['a'] + $args['b'];
        };

        $tool = new Tool('add', $schema, 'Add two numbers');
        $this->sampleTool = new RegisteredTool($tool, $callable);
    }

    public function testRegisterTool(): void
    {
        $this->toolManager->register($this->sampleTool);

        $this->assertTrue($this->toolManager->has('add'));
        $this->assertEquals(1, $this->toolManager->count());
        $this->assertContains('add', $this->toolManager->getNames());
    }

    public function testGetTool(): void
    {
        $this->toolManager->register($this->sampleTool);

        $retrievedTool = $this->toolManager->get('add');
        $this->assertSame($this->sampleTool, $retrievedTool);
    }

    public function testGetNonexistentTool(): void
    {
        $retrievedTool = $this->toolManager->get('nonexistent');
        $this->assertNull($retrievedTool);
    }

    public function testHasTool(): void
    {
        $this->assertFalse($this->toolManager->has('add'));

        $this->toolManager->register($this->sampleTool);
        $this->assertTrue($this->toolManager->has('add'));
    }

    public function testRemoveTool(): void
    {
        $this->toolManager->register($this->sampleTool);
        $this->assertTrue($this->toolManager->has('add'));

        $result = $this->toolManager->remove('add');
        $this->assertTrue($result);
        $this->assertFalse($this->toolManager->has('add'));

        // Try to remove again
        $result = $this->toolManager->remove('add');
        $this->assertFalse($result);
    }

    public function testGetNames(): void
    {
        $this->assertEquals([], $this->toolManager->getNames());

        $this->toolManager->register($this->sampleTool);
        $this->assertEquals(['add'], $this->toolManager->getNames());

        // Add another tool
        $greetSchema = ['type' => 'object', 'properties' => []];
        $greetTool = new RegisteredTool(
            new Tool('greet', $greetSchema, 'Greet someone'),
            fn (array $args) => 'Hello'
        );
        $this->toolManager->register($greetTool);

        $names = $this->toolManager->getNames();
        $this->assertCount(2, $names);
        $this->assertContains('add', $names);
        $this->assertContains('greet', $names);
    }

    public function testGetAll(): void
    {
        $this->assertEquals([], $this->toolManager->getAll());

        $this->toolManager->register($this->sampleTool);
        $tools = $this->toolManager->getAll();

        $this->assertCount(1, $tools);
        $this->assertSame($this->sampleTool, $tools[0]);
    }

    public function testCount(): void
    {
        $this->assertEquals(0, $this->toolManager->count());

        $this->toolManager->register($this->sampleTool);
        $this->assertEquals(1, $this->toolManager->count());

        $this->toolManager->remove('add');
        $this->assertEquals(0, $this->toolManager->count());
    }

    public function testClear(): void
    {
        $this->toolManager->register($this->sampleTool);
        $this->assertEquals(1, $this->toolManager->count());

        $this->toolManager->clear();
        $this->assertEquals(0, $this->toolManager->count());
        $this->assertFalse($this->toolManager->has('add'));
    }

    public function testExecuteSuccess(): void
    {
        $this->toolManager->register($this->sampleTool);

        $result = $this->toolManager->execute('add', ['a' => 5, 'b' => 3]);
        $this->assertEquals(8, $result);
    }

    public function testExecuteUnknownTool(): void
    {
        $this->expectException(ToolError::class);
        $this->expectExceptionMessage('Unknown tool: nonexistent');

        $this->toolManager->execute('nonexistent', []);
    }

    public function testExecuteWithValidationError(): void
    {
        $this->toolManager->register($this->sampleTool);

        $this->expectException(ToolError::class);
        $this->expectExceptionMessage('Error executing tool add: Required argument \'b\' is missing');

        // Missing required parameter 'b'
        $this->toolManager->execute('add', ['a' => 5]);
    }

    public function testRegisterMultipleTools(): void
    {
        $greetSchema = ['type' => 'object', 'properties' => []];
        $greetTool = new RegisteredTool(
            new Tool('greet', $greetSchema, 'Greet someone'),
            fn (array $args) => 'Hello ' . ($args['name'] ?? 'World')
        );

        $this->toolManager->register($this->sampleTool);
        $this->toolManager->register($greetTool);

        $this->assertEquals(2, $this->toolManager->count());
        $this->assertTrue($this->toolManager->has('add'));
        $this->assertTrue($this->toolManager->has('greet'));

        // Test execution of both tools
        $this->assertEquals(8, $this->toolManager->execute('add', ['a' => 5, 'b' => 3]));
        $this->assertEquals('Hello Alice', $this->toolManager->execute('greet', ['name' => 'Alice']));
    }

    public function testRegisterOverwritesTool(): void
    {
        $this->toolManager->register($this->sampleTool);
        $this->assertEquals(1, $this->toolManager->count());

        // Create a different tool with the same name
        $newSchema = ['type' => 'object', 'properties' => []];
        $newTool = new RegisteredTool(
            new Tool('add', $newSchema, 'Different add tool'),
            fn (array $args) => 'different result'
        );

        $this->toolManager->register($newTool);
        $this->assertEquals(1, $this->toolManager->count()); // Still 1 tool

        // The new tool should have replaced the old one
        $retrievedTool = $this->toolManager->get('add');
        $this->assertSame($newTool, $retrievedTool);
        $this->assertEquals('Different add tool', $retrievedTool->getDescription());
    }
}
