<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Types\Prompts;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Constants\MessageConstants;
use Dtyq\PhpMcp\Types\Content\EmbeddedResource;
use Dtyq\PhpMcp\Types\Content\ImageContent;
use Dtyq\PhpMcp\Types\Prompts\GetPromptResult;
use Dtyq\PhpMcp\Types\Prompts\PromptMessage;
use Dtyq\PhpMcp\Types\Resources\TextResourceContents;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class GetPromptResultTest extends TestCase
{
    public function testConstructorWithValidData(): void
    {
        $description = 'Generated prompt result';
        $messages = [
            PromptMessage::createUserMessage('Hello'),
            PromptMessage::createAssistantMessage('Hi there!'),
        ];

        $result = new GetPromptResult($description, $messages);

        $this->assertSame($description, $result->getDescription());
        $this->assertSame($messages, $result->getMessages());
        $this->assertTrue($result->hasDescription());
        $this->assertTrue($result->hasMessages());
        $this->assertSame(2, $result->getMessageCount());
    }

    public function testConstructorWithMinimalData(): void
    {
        $result = new GetPromptResult();

        $this->assertNull($result->getDescription());
        $this->assertSame([], $result->getMessages());
        $this->assertFalse($result->hasDescription());
        $this->assertFalse($result->hasMessages());
        $this->assertSame(0, $result->getMessageCount());
    }

    public function testFromArrayWithValidData(): void
    {
        $data = [
            'description' => 'Test result',
            'messages' => [
                [
                    'role' => MessageConstants::ROLE_USER,
                    'content' => [
                        'type' => MessageConstants::CONTENT_TYPE_TEXT,
                        'text' => 'Hello',
                    ],
                ],
                [
                    'role' => MessageConstants::ROLE_ASSISTANT,
                    'content' => [
                        'type' => MessageConstants::CONTENT_TYPE_TEXT,
                        'text' => 'Hi there!',
                    ],
                ],
            ],
        ];

        $result = GetPromptResult::fromArray($data);

        $this->assertSame($data['description'], $result->getDescription());
        $this->assertSame(2, $result->getMessageCount());

        $messages = $result->getMessages();
        $this->assertTrue($messages[0]->isUserMessage());
        $this->assertSame('Hello', $messages[0]->getTextContent());
        $this->assertTrue($messages[1]->isAssistantMessage());
        $this->assertSame('Hi there!', $messages[1]->getTextContent());
    }

    public function testFromArrayWithMinimalData(): void
    {
        $data = [];

        $result = GetPromptResult::fromArray($data);

        $this->assertNull($result->getDescription());
        $this->assertSame([], $result->getMessages());
    }

    public function testFromArrayWithInvalidDescriptionType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid type for field \'description\': expected string, got integer');

        GetPromptResult::fromArray([
            'description' => 123,
        ]);
    }

    public function testFromArrayWithInvalidMessagesType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid type for field \'messages\': expected array, got string');

        GetPromptResult::fromArray([
            'messages' => 'invalid',
        ]);
    }

    public function testFromArrayWithInvalidMessageType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid type for field \'messages[0]\': expected array, got string');

        GetPromptResult::fromArray([
            'messages' => ['invalid'],
        ]);
    }

    public function testSetMessagesWithInvalidType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid type for field \'messages[0]\': expected PromptMessage, got string');

        $result = new GetPromptResult();
        $result->setMessages(['invalid']);
    }

    public function testAddMessage(): void
    {
        $result = new GetPromptResult();
        $message = PromptMessage::createUserMessage('Hello');

        $this->assertFalse($result->hasMessages());

        $result->addMessage($message);

        $this->assertTrue($result->hasMessages());
        $this->assertSame(1, $result->getMessageCount());
        $this->assertSame($message, $result->getMessages()[0]);
    }

    public function testRemoveMessage(): void
    {
        $msg1 = PromptMessage::createUserMessage('First');
        $msg2 = PromptMessage::createUserMessage('Second');
        $result = new GetPromptResult(null, [$msg1, $msg2]);

        $this->assertSame(2, $result->getMessageCount());

        $removed = $result->removeMessage(0);
        $this->assertTrue($removed);
        $this->assertSame(1, $result->getMessageCount());
        $this->assertSame('Second', $result->getMessages()[0]->getTextContent());

        $notRemoved = $result->removeMessage(5);
        $this->assertFalse($notRemoved);
        $this->assertSame(1, $result->getMessageCount());
    }

    public function testGetMessage(): void
    {
        $msg1 = PromptMessage::createUserMessage('First');
        $msg2 = PromptMessage::createUserMessage('Second');
        $result = new GetPromptResult(null, [$msg1, $msg2]);

        $found = $result->getMessage(0);
        $this->assertSame($msg1, $found);

        $notFound = $result->getMessage(5);
        $this->assertNull($notFound);
    }

    public function testGetUserMessages(): void
    {
        $userMsg = PromptMessage::createUserMessage('User message');
        $assistantMsg = PromptMessage::createAssistantMessage('Assistant message');
        $result = new GetPromptResult(null, [$userMsg, $assistantMsg]);

        $userMessages = $result->getUserMessages();
        $this->assertCount(1, $userMessages);
        $this->assertSame($userMsg, $userMessages[0]);
    }

    public function testGetAssistantMessages(): void
    {
        $userMsg = PromptMessage::createUserMessage('User message');
        $assistantMsg = PromptMessage::createAssistantMessage('Assistant message');
        $result = new GetPromptResult(null, [$userMsg, $assistantMsg]);

        $assistantMessages = $result->getAssistantMessages();
        $this->assertCount(1, $assistantMessages);
        $this->assertSame($assistantMsg, array_values($assistantMessages)[0]);
    }

    public function testGetTextMessages(): void
    {
        $textMsg = PromptMessage::createUserMessage('Text message');
        $imageMsg = new PromptMessage(MessageConstants::ROLE_USER, new ImageContent('dGVzdA==', 'image/png'));
        $result = new GetPromptResult(null, [$textMsg, $imageMsg]);

        $textMessages = $result->getTextMessages();
        $this->assertCount(1, $textMessages);
        $this->assertSame($textMsg, $textMessages[0]);
    }

    public function testGetImageMessages(): void
    {
        $textMsg = PromptMessage::createUserMessage('Text message');
        $imageMsg = new PromptMessage(MessageConstants::ROLE_USER, new ImageContent('dGVzdA==', 'image/png'));
        $result = new GetPromptResult(null, [$textMsg, $imageMsg]);

        $imageMessages = $result->getImageMessages();
        $this->assertCount(1, $imageMessages);
        $this->assertSame($imageMsg, array_values($imageMessages)[0]);
    }

    public function testGetResourceMessages(): void
    {
        $textMsg = PromptMessage::createUserMessage('Text message');
        $resourceContents = new TextResourceContents('file:///test.txt', 'Test content');
        $resourceMsg = PromptMessage::createUserResourceMessage(new EmbeddedResource($resourceContents));
        $result = new GetPromptResult(null, [$textMsg, $resourceMsg]);

        $resourceMessages = $result->getResourceMessages();
        $this->assertCount(1, $resourceMessages);
        $this->assertSame($resourceMsg, array_values($resourceMessages)[0]);
    }

    public function testGetAllTextContent(): void
    {
        $textMsg1 = PromptMessage::createUserMessage('First text');
        $textMsg2 = PromptMessage::createAssistantMessage('Second text');
        $imageMsg = new PromptMessage(MessageConstants::ROLE_USER, new ImageContent('dGVzdA==', 'image/png'));
        $result = new GetPromptResult(null, [$textMsg1, $textMsg2, $imageMsg]);

        $textContent = $result->getAllTextContent();
        $this->assertSame(['First text', 'Second text'], $textContent);
    }

    public function testToArray(): void
    {
        $description = 'Test result';
        $messages = [
            PromptMessage::createUserMessage('Hello'),
            PromptMessage::createAssistantMessage('Hi'),
        ];

        $result = new GetPromptResult($description, $messages);

        $expected = [
            'messages' => [
                [
                    'role' => MessageConstants::ROLE_USER,
                    'content' => [
                        'type' => MessageConstants::CONTENT_TYPE_TEXT,
                        'text' => 'Hello',
                    ],
                ],
                [
                    'role' => MessageConstants::ROLE_ASSISTANT,
                    'content' => [
                        'type' => MessageConstants::CONTENT_TYPE_TEXT,
                        'text' => 'Hi',
                    ],
                ],
            ],
            'description' => $description,
        ];

        $this->assertSame($expected, $result->toArray());
    }

    public function testToArrayWithMinimalData(): void
    {
        $result = new GetPromptResult();

        $expected = [
            'messages' => [],
        ];

        $this->assertSame($expected, $result->toArray());
    }

    public function testToJson(): void
    {
        $result = new GetPromptResult('Test', [PromptMessage::createUserMessage('Hello')]);
        $json = $result->toJson();
        $decoded = json_decode($json, true);

        $this->assertSame('Test', $decoded['description']);
        $this->assertCount(1, $decoded['messages']);
        $this->assertSame('Hello', $decoded['messages'][0]['content']['text']);
    }

    public function testWithMethods(): void
    {
        $original = new GetPromptResult('Original description');

        $withDescription = $original->withDescription('New description');
        $this->assertNotSame($original, $withDescription);
        $this->assertSame('Original description', $original->getDescription());
        $this->assertSame('New description', $withDescription->getDescription());

        $newMessages = [PromptMessage::createUserMessage('New message')];
        $withMessages = $original->withMessages($newMessages);
        $this->assertNotSame($original, $withMessages);
        $this->assertSame([], $original->getMessages());
        $this->assertSame($newMessages, $withMessages->getMessages());

        $additionalMessage = PromptMessage::createAssistantMessage('Additional');
        $withAdded = $original->withAddedMessage($additionalMessage);
        $this->assertNotSame($original, $withAdded);
        $this->assertSame(0, $original->getMessageCount());
        $this->assertSame(1, $withAdded->getMessageCount());
        $this->assertSame($additionalMessage, $withAdded->getMessages()[0]);
    }

    public function testFactoryMethods(): void
    {
        $textResult = GetPromptResult::createTextResult('Hello world', 'Simple text');
        $this->assertSame('Simple text', $textResult->getDescription());
        $this->assertSame(1, $textResult->getMessageCount());
        $this->assertTrue($textResult->getMessages()[0]->isUserMessage());
        $this->assertSame('Hello world', $textResult->getMessages()[0]->getTextContent());

        $conversation = [
            ['role' => 'user', 'text' => 'Hello'],
            ['role' => 'assistant', 'text' => 'Hi there!'],
            ['role' => 'user', 'text' => 'How are you?'],
        ];

        $conversationResult = GetPromptResult::createConversationResult($conversation, 'Conversation');
        $this->assertSame('Conversation', $conversationResult->getDescription());
        $this->assertSame(3, $conversationResult->getMessageCount());

        $messages = $conversationResult->getMessages();
        $this->assertTrue($messages[0]->isUserMessage());
        $this->assertSame('Hello', $messages[0]->getTextContent());
        $this->assertTrue($messages[1]->isAssistantMessage());
        $this->assertSame('Hi there!', $messages[1]->getTextContent());
        $this->assertTrue($messages[2]->isUserMessage());
        $this->assertSame('How are you?', $messages[2]->getTextContent());
    }
}
