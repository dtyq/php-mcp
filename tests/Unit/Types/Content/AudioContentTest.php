<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Tests\Unit\Types\Content;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Content\Annotations;
use Dtyq\PhpMcp\Types\Content\AudioContent;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class AudioContentTest extends TestCase
{
    private string $validBase64Audio;

    protected function setUp(): void
    {
        // Create a small valid base64 audio data for testing
        $this->validBase64Audio = base64_encode('fake audio data for testing');
    }

    public function testConstructorWithValidData(): void
    {
        $content = new AudioContent(
            $this->validBase64Audio,
            ProtocolConstants::MIME_TYPE_AUDIO_MP3
        );

        $this->assertEquals(ProtocolConstants::CONTENT_TYPE_AUDIO, $content->getType());
        $this->assertEquals($this->validBase64Audio, $content->getData());
        $this->assertEquals(ProtocolConstants::MIME_TYPE_AUDIO_MP3, $content->getMimeType());
        $this->assertNull($content->getAnnotations());
    }

    public function testConstructorWithAnnotations(): void
    {
        $annotations = new Annotations(['user'], 0.8);
        $content = new AudioContent(
            $this->validBase64Audio,
            ProtocolConstants::MIME_TYPE_AUDIO_WAV,
            $annotations
        );

        $this->assertEquals($annotations, $content->getAnnotations());
        $this->assertTrue($content->hasAnnotations());
        $this->assertTrue($content->isTargetedTo('user'));
        $this->assertEquals(0.8, $content->getPriority());
    }

    public function testSetDataWithInvalidBase64(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('must be valid base64-encoded audio data');

        new AudioContent('invalid base64!@#', ProtocolConstants::MIME_TYPE_AUDIO_MP3);
    }

    public function testSetDataWithEmptyData(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('audio data cannot be empty');

        new AudioContent('', ProtocolConstants::MIME_TYPE_AUDIO_MP3);
    }

    public function testSetMimeTypeWithInvalidType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('unsupported audio MIME type');

        new AudioContent($this->validBase64Audio, 'text/plain');
    }

    public function testSetMimeTypeWithEmptyType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('MIME type cannot be empty');

        new AudioContent($this->validBase64Audio, '');
    }

    public function testGetDecodedData(): void
    {
        $content = new AudioContent(
            $this->validBase64Audio,
            ProtocolConstants::MIME_TYPE_AUDIO_MP3
        );

        $decoded = $content->getDecodedData();
        $this->assertEquals('fake audio data for testing', $decoded);
    }

    public function testGetDataSize(): void
    {
        $content = new AudioContent(
            $this->validBase64Audio,
            ProtocolConstants::MIME_TYPE_AUDIO_MP3
        );

        $expectedSize = (int) (strlen($this->validBase64Audio) * 3 / 4);
        $this->assertEquals($expectedSize, $content->getDataSize());
    }

    public function testGetSummary(): void
    {
        $content = new AudioContent(
            $this->validBase64Audio,
            ProtocolConstants::MIME_TYPE_AUDIO_MP3
        );

        $summary = $content->getSummary();
        $this->assertStringContainsString('Audio', $summary);
        $this->assertStringContainsString('audio/mpeg', $summary);
        $this->assertStringContainsString('B', $summary); // Size unit
    }

    public function testToArray(): void
    {
        $annotations = new Annotations(['user'], 0.5);
        $content = new AudioContent(
            $this->validBase64Audio,
            ProtocolConstants::MIME_TYPE_AUDIO_WAV,
            $annotations
        );

        $array = $content->toArray();

        $this->assertEquals([
            'type' => ProtocolConstants::CONTENT_TYPE_AUDIO,
            'data' => $this->validBase64Audio,
            'mimeType' => ProtocolConstants::MIME_TYPE_AUDIO_WAV,
            'annotations' => $annotations->toArray(),
        ], $array);
    }

    public function testToArrayWithoutAnnotations(): void
    {
        $content = new AudioContent(
            $this->validBase64Audio,
            ProtocolConstants::MIME_TYPE_AUDIO_MP3
        );

        $array = $content->toArray();

        $this->assertEquals([
            'type' => ProtocolConstants::CONTENT_TYPE_AUDIO,
            'data' => $this->validBase64Audio,
            'mimeType' => ProtocolConstants::MIME_TYPE_AUDIO_MP3,
        ], $array);
    }

    public function testFromArray(): void
    {
        $data = [
            'data' => $this->validBase64Audio,
            'mimeType' => ProtocolConstants::MIME_TYPE_AUDIO_OGG,
            'annotations' => [
                'audience' => ['assistant'],
                'priority' => 0.7,
            ],
        ];

        $content = AudioContent::fromArray($data);

        $this->assertEquals($this->validBase64Audio, $content->getData());
        $this->assertEquals(ProtocolConstants::MIME_TYPE_AUDIO_OGG, $content->getMimeType());
        $this->assertTrue($content->hasAnnotations());
        $this->assertEquals(0.7, $content->getPriority());
    }

    public function testFromArrayMissingData(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Required field \'data\' is missing');

        AudioContent::fromArray([
            'mimeType' => ProtocolConstants::MIME_TYPE_AUDIO_MP3,
        ]);
    }

    public function testFromArrayMissingMimeType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Required field \'mimeType\' is missing');

        AudioContent::fromArray([
            'data' => $this->validBase64Audio,
        ]);
    }

    public function testFromArrayInvalidDataType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid type for field \'data\': expected string, got integer');

        AudioContent::fromArray([
            'data' => 123,
            'mimeType' => ProtocolConstants::MIME_TYPE_AUDIO_MP3,
        ]);
    }

    public function testSupportedAudioMimeTypes(): void
    {
        $supportedTypes = [
            ProtocolConstants::MIME_TYPE_AUDIO_MP3,
            ProtocolConstants::MIME_TYPE_AUDIO_WAV,
            ProtocolConstants::MIME_TYPE_AUDIO_OGG,
            ProtocolConstants::MIME_TYPE_AUDIO_M4A,
            ProtocolConstants::MIME_TYPE_AUDIO_WEBM,
        ];

        foreach ($supportedTypes as $mimeType) {
            $content = new AudioContent($this->validBase64Audio, $mimeType);
            $this->assertEquals($mimeType, $content->getMimeType());
        }
    }

    public function testGenericAudioMimeType(): void
    {
        // Should accept any audio/* MIME type
        $content = new AudioContent($this->validBase64Audio, 'audio/custom');
        $this->assertEquals('audio/custom', $content->getMimeType());
    }

    public function testToJson(): void
    {
        $content = new AudioContent(
            $this->validBase64Audio,
            ProtocolConstants::MIME_TYPE_AUDIO_MP3
        );

        $json = $content->toJson();
        $decoded = json_decode($json, true);

        $this->assertEquals($content->toArray(), $decoded);
    }
}
