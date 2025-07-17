<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Types\Content;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Constants\MessageConstants;
use Dtyq\PhpMcp\Types\Content\Annotations;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class AnnotationsTest extends TestCase
{
    public function testConstructorWithValidData(): void
    {
        $audience = [MessageConstants::ROLE_USER, MessageConstants::ROLE_ASSISTANT];
        $priority = 0.8;

        $annotations = new Annotations($audience, $priority);

        $this->assertSame($audience, $annotations->getAudience());
        $this->assertSame($priority, $annotations->getPriority());
        $this->assertTrue($annotations->hasAudience());
        $this->assertTrue($annotations->hasPriority());
        $this->assertFalse($annotations->isEmpty());
    }

    public function testConstructorWithNullValues(): void
    {
        $annotations = new Annotations();

        $this->assertNull($annotations->getAudience());
        $this->assertNull($annotations->getPriority());
        $this->assertFalse($annotations->hasAudience());
        $this->assertFalse($annotations->hasPriority());
        $this->assertTrue($annotations->isEmpty());
    }

    public function testFromArray(): void
    {
        $data = [
            'audience' => [MessageConstants::ROLE_USER],
            'priority' => 0.5,
        ];

        $annotations = Annotations::fromArray($data);

        $this->assertSame($data['audience'], $annotations->getAudience());
        $this->assertSame($data['priority'], $annotations->getPriority());
    }

    public function testFromArrayWithEmptyData(): void
    {
        $annotations = Annotations::fromArray([]);

        $this->assertNull($annotations->getAudience());
        $this->assertNull($annotations->getPriority());
        $this->assertTrue($annotations->isEmpty());
    }

    public function testSetAudienceWithValidRoles(): void
    {
        $annotations = new Annotations();
        $audience = [MessageConstants::ROLE_USER, MessageConstants::ROLE_ASSISTANT];

        $annotations->setAudience($audience);

        $this->assertSame($audience, $annotations->getAudience());
        $this->assertTrue($annotations->hasAudience());
    }

    public function testSetAudienceWithInvalidRole(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid value for field \'audience\': all roles must be strings');

        $annotations = new Annotations();
        $annotations->setAudience(['user', 123]);
    }

    public function testSetAudienceWithNonStringRole(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid value for field \'audience\': all roles must be strings');

        $annotations = new Annotations();
        $annotations->setAudience([123]);
    }

    public function testSetPriorityWithValidValue(): void
    {
        $annotations = new Annotations();
        $priority = 0.7;

        $annotations->setPriority($priority);

        $this->assertSame($priority, $annotations->getPriority());
        $this->assertTrue($annotations->hasPriority());
    }

    public function testSetPriorityWithInvalidValue(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid value for field \'priority\': must be between 0.0 and 1.0');

        $annotations = new Annotations();
        $annotations->setPriority(1.5);
    }

    public function testIsTargetedToWithAudience(): void
    {
        $annotations = new Annotations([MessageConstants::ROLE_USER]);

        $this->assertTrue($annotations->isTargetedTo(MessageConstants::ROLE_USER));
        $this->assertFalse($annotations->isTargetedTo(MessageConstants::ROLE_ASSISTANT));
    }

    public function testIsTargetedToWithoutAudience(): void
    {
        $annotations = new Annotations();

        $this->assertTrue($annotations->isTargetedTo(MessageConstants::ROLE_USER));
        $this->assertTrue($annotations->isTargetedTo(MessageConstants::ROLE_ASSISTANT));
    }

    public function testToArray(): void
    {
        $audience = [MessageConstants::ROLE_USER];
        $priority = 0.6;
        $annotations = new Annotations($audience, $priority);

        $expected = [
            'audience' => $audience,
            'priority' => $priority,
        ];

        $this->assertSame($expected, $annotations->toArray());
    }

    public function testToArrayWithEmptyAnnotations(): void
    {
        $annotations = new Annotations();

        $this->assertSame([], $annotations->toArray());
    }

    public function testToJson(): void
    {
        $annotations = new Annotations([MessageConstants::ROLE_USER], 0.5);

        $json = $annotations->toJson();
        $decoded = json_decode($json, true);

        $this->assertSame([MessageConstants::ROLE_USER], $decoded['audience']);
        $this->assertSame(0.5, $decoded['priority']);
    }

    public function testFromArrayWithInvalidAudienceType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid type for field \'audience\': expected array, got string');

        Annotations::fromArray(['audience' => 'user']);
    }

    public function testFromArrayWithInvalidPriorityType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid type for field \'priority\': expected number, got string');

        Annotations::fromArray(['priority' => 'high']);
    }
}
