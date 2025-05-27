<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Shared\Kernel\Config;

use Dtyq\PhpMcp\Shared\Kernel\Config\Config;
use PHPUnit\Framework\TestCase;

/**
 * Test case for Config class.
 * @internal
 */
class ConfigTest extends TestCase
{
    public function testConstructorWithEmptyArray(): void
    {
        $config = new Config([]);

        $this->assertInstanceOf(Config::class, $config);
        $this->assertSame('php-mcp', $config->getSdkName());
        $this->assertSame('php-mcp', $config->get('sdk_name'));
    }

    public function testConstructorWithCustomItems(): void
    {
        $items = [
            'app_name' => 'test-app',
            'debug' => true,
            'database' => [
                'host' => 'localhost',
                'port' => 3306,
            ],
        ];

        $config = new Config($items);

        $this->assertSame('test-app', $config->get('app_name'));
        $this->assertTrue($config->get('debug'));
        $this->assertSame('localhost', $config->get('database.host'));
        $this->assertSame(3306, $config->get('database.port'));
        $this->assertSame('php-mcp', $config->getSdkName());
    }

    public function testConstructorWithCustomSdkName(): void
    {
        $items = [
            'sdk_name' => 'custom-sdk',
            'other_config' => 'value',
        ];

        $config = new Config($items);

        // Due to constructor implementation, sdk_name is always overridden to 'php-mcp'
        $this->assertSame('php-mcp', $config->getSdkName());
        $this->assertSame('php-mcp', $config->get('sdk_name'));
        $this->assertSame('value', $config->get('other_config'));
    }

    public function testGetSdkNameWithDefault(): void
    {
        $config = new Config([]);

        $this->assertSame('php-mcp', $config->getSdkName());
    }

    public function testGetSdkNameWithCustomValue(): void
    {
        $config = new Config(['sdk_name' => 'my-custom-sdk']);

        // Due to constructor implementation, always returns 'php-mcp'
        $this->assertSame('php-mcp', $config->getSdkName());
    }

    public function testGetSdkNameAfterManualSet(): void
    {
        $config = new Config([]);

        // Manually setting sdk_name after construction should work
        $config->set('sdk_name', 'manually-set-sdk');
        $this->assertSame('manually-set-sdk', $config->getSdkName());
    }

    public function testInheritedDotFunctionality(): void
    {
        $items = [
            'level1' => [
                'level2' => [
                    'level3' => 'deep_value',
                ],
                'array_value' => ['item1', 'item2', 'item3'],
            ],
            'simple_value' => 'test',
        ];

        $config = new Config($items);

        // Test dot notation access
        $this->assertSame('deep_value', $config->get('level1.level2.level3'));
        $this->assertSame(['item1', 'item2', 'item3'], $config->get('level1.array_value'));
        $this->assertSame('test', $config->get('simple_value'));

        // Test default values
        $this->assertSame('default', $config->get('non_existent', 'default'));
        $this->assertNull($config->get('non_existent'));
    }

    public function testSetAndGetValues(): void
    {
        $config = new Config([]);

        // Test setting simple values
        $config->set('new_key', 'new_value');
        $this->assertSame('new_value', $config->get('new_key'));

        // Test setting nested values with dot notation
        $config->set('nested.key', 'nested_value');
        $this->assertSame('nested_value', $config->get('nested.key'));

        // Test setting array values
        $config->set('array_config', ['a', 'b', 'c']);
        $this->assertSame(['a', 'b', 'c'], $config->get('array_config'));
    }

    public function testHasMethod(): void
    {
        $config = new Config([
            'existing_key' => 'value',
            'nested' => [
                'key' => 'nested_value',
            ],
        ]);

        $this->assertTrue($config->has('existing_key'));
        $this->assertTrue($config->has('nested.key'));
        $this->assertTrue($config->has('sdk_name')); // Auto-added by constructor
        $this->assertFalse($config->has('non_existent_key'));
        $this->assertFalse($config->has('nested.non_existent'));
    }

    public function testAllMethod(): void
    {
        $items = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        $config = new Config($items);
        $all = $config->all();

        $this->assertIsArray($all);
        $this->assertSame('value1', $all['key1']);
        $this->assertSame('value2', $all['key2']);
        $this->assertSame('php-mcp', $all['sdk_name']); // Auto-added
    }

    public function testDeleteMethod(): void
    {
        $config = new Config([
            'to_delete' => 'value',
            'to_keep' => 'keep_value',
            'nested' => [
                'to_delete' => 'nested_value',
                'to_keep' => 'keep_nested',
            ],
        ]);

        // Delete simple key
        $config->delete('to_delete');
        $this->assertFalse($config->has('to_delete'));
        $this->assertTrue($config->has('to_keep'));

        // Delete nested key
        $config->delete('nested.to_delete');
        $this->assertFalse($config->has('nested.to_delete'));
        $this->assertTrue($config->has('nested.to_keep'));
    }

    public function testClearMethod(): void
    {
        $config = new Config([
            'key1' => 'value1',
            'key2' => 'value2',
        ]);

        $this->assertTrue($config->has('key1'));
        $this->assertTrue($config->has('sdk_name'));

        $config->clear();

        $this->assertFalse($config->has('key1'));
        $this->assertFalse($config->has('key2'));
        $this->assertFalse($config->has('sdk_name'));
    }

    public function testCountMethod(): void
    {
        $config = new Config([
            'key1' => 'value1',
            'key2' => 'value2',
        ]);

        // Should count 3 items: key1, key2, and auto-added sdk_name
        $this->assertSame(3, $config->count());

        $config->set('key3', 'value3');
        $this->assertSame(4, $config->count());

        $config->delete('key1');
        $this->assertSame(3, $config->count());
    }

    public function testIsEmptyMethod(): void
    {
        $config = new Config([]);

        // Should not be empty because sdk_name is auto-added
        $this->assertFalse($config->isEmpty());

        $config->clear();
        $this->assertTrue($config->isEmpty());

        $config->set('new_key', 'value');
        $this->assertFalse($config->isEmpty());
    }

    public function testSdkNameOverrideInConstructor(): void
    {
        // Test that sdk_name is always set to 'php-mcp' due to constructor implementation
        $config = new Config(['sdk_name' => 'original']);

        // The constructor always overrides sdk_name to 'php-mcp'
        $this->assertSame('php-mcp', $config->getSdkName());

        // Same behavior for config without sdk_name
        $config2 = new Config(['other' => 'value']);
        $this->assertSame('php-mcp', $config2->getSdkName());

        // But manual setting after construction should work
        $config->set('sdk_name', 'manually-set');
        $this->assertSame('manually-set', $config->getSdkName());
    }
}
