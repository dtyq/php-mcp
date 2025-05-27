<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Types\Sampling;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Sampling\ModelHint;
use Dtyq\PhpMcp\Types\Sampling\ModelPreferences;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ModelPreferencesTest extends TestCase
{
    public function testConstructorWithDefaults(): void
    {
        $preferences = new ModelPreferences();

        $this->assertEmpty($preferences->getHints());
        $this->assertNull($preferences->getCostPriority());
        $this->assertNull($preferences->getSpeedPriority());
        $this->assertNull($preferences->getIntelligencePriority());
        $this->assertFalse($preferences->hasHints());
        $this->assertFalse($preferences->hasCostPriority());
        $this->assertFalse($preferences->hasSpeedPriority());
        $this->assertFalse($preferences->hasIntelligencePriority());
    }

    public function testConstructorWithAllParameters(): void
    {
        $hints = [new ModelHint('claude-3')];
        $preferences = new ModelPreferences($hints, 0.8, 0.5, 0.9);

        $this->assertSame($hints, $preferences->getHints());
        $this->assertSame(0.8, $preferences->getCostPriority());
        $this->assertSame(0.5, $preferences->getSpeedPriority());
        $this->assertSame(0.9, $preferences->getIntelligencePriority());
        $this->assertTrue($preferences->hasHints());
        $this->assertTrue($preferences->hasCostPriority());
        $this->assertTrue($preferences->hasSpeedPriority());
        $this->assertTrue($preferences->hasIntelligencePriority());
    }

    public function testFromArrayWithValidData(): void
    {
        $data = [
            'hints' => [
                ['name' => 'claude-3'],
                ['name' => 'gpt-4'],
            ],
            'costPriority' => 0.7,
            'speedPriority' => 0.3,
            'intelligencePriority' => 0.9,
        ];

        $preferences = ModelPreferences::fromArray($data);

        $this->assertCount(2, $preferences->getHints());
        $this->assertSame(['claude-3', 'gpt-4'], $preferences->getHintNames());
        $this->assertSame(0.7, $preferences->getCostPriority());
        $this->assertSame(0.3, $preferences->getSpeedPriority());
        $this->assertSame(0.9, $preferences->getIntelligencePriority());
    }

    public function testFromArrayWithEmptyData(): void
    {
        $preferences = ModelPreferences::fromArray([]);

        $this->assertEmpty($preferences->getHints());
        $this->assertNull($preferences->getCostPriority());
    }

    public function testCreateWithHints(): void
    {
        $preferences = ModelPreferences::createWithHints(['claude-3', 'gpt-4']);

        $this->assertCount(2, $preferences->getHints());
        $this->assertSame(['claude-3', 'gpt-4'], $preferences->getHintNames());
    }

    public function testWithPriorities(): void
    {
        $preferences = ModelPreferences::withPriorities(0.8, 0.5, 0.9);

        $this->assertSame(0.8, $preferences->getCostPriority());
        $this->assertSame(0.5, $preferences->getSpeedPriority());
        $this->assertSame(0.9, $preferences->getIntelligencePriority());
    }

    public function testSetCostPriorityWithInvalidValue(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid value for field \'costPriority\': must be between 0.0 and 1.0');

        $preferences = new ModelPreferences();
        $preferences->setCostPriority(1.5);
    }

    public function testSetSpeedPriorityWithInvalidValue(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid value for field \'speedPriority\': must be between 0.0 and 1.0');

        $preferences = new ModelPreferences();
        $preferences->setSpeedPriority(-0.1);
    }

    public function testSetIntelligencePriorityWithInvalidValue(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid value for field \'intelligencePriority\': must be between 0.0 and 1.0');

        $preferences = new ModelPreferences();
        $preferences->setIntelligencePriority(2.0);
    }

    public function testAddHint(): void
    {
        $preferences = new ModelPreferences();
        $hint = new ModelHint('claude-3');

        $preferences->addHint($hint);

        $this->assertCount(1, $preferences->getHints());
        $this->assertSame($hint, $preferences->getHints()[0]);
    }

    public function testAddHintByName(): void
    {
        $preferences = new ModelPreferences();

        $preferences->addHintByName('gpt-4');

        $this->assertCount(1, $preferences->getHints());
        $this->assertSame('gpt-4', $preferences->getHints()[0]->getName());
    }

    public function testWithHints(): void
    {
        $original = new ModelPreferences();
        $hints = [new ModelHint('claude-3')];
        $modified = $original->withHints($hints);

        $this->assertNotSame($original, $modified);
        $this->assertEmpty($original->getHints());
        $this->assertSame($hints, $modified->getHints());
    }

    public function testWithCostPriority(): void
    {
        $original = new ModelPreferences();
        $modified = $original->withCostPriority(0.8);

        $this->assertNotSame($original, $modified);
        $this->assertNull($original->getCostPriority());
        $this->assertSame(0.8, $modified->getCostPriority());
    }

    public function testToArray(): void
    {
        $hints = [new ModelHint('claude-3')];
        $preferences = new ModelPreferences($hints, 0.8, 0.5, 0.9);

        $expected = [
            'hints' => [
                ['name' => 'claude-3'],
            ],
            'costPriority' => 0.8,
            'speedPriority' => 0.5,
            'intelligencePriority' => 0.9,
        ];

        $this->assertSame($expected, $preferences->toArray());
    }

    public function testToArrayWithEmptyPreferences(): void
    {
        $preferences = new ModelPreferences();

        $this->assertSame([], $preferences->toArray());
    }

    public function testToJson(): void
    {
        $preferences = new ModelPreferences([new ModelHint('test')], 0.5);
        $json = $preferences->toJson();
        $decoded = json_decode($json, true);

        $this->assertSame('test', $decoded['hints'][0]['name']);
        $this->assertSame(0.5, $decoded['costPriority']);
    }
}
