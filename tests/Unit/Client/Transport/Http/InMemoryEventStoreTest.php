<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Client\Transport\Http;

use Dtyq\PhpMcp\Client\Transport\Http\InMemoryEventStore;
use Dtyq\PhpMcp\Shared\Message\JsonRpcMessage;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Unit tests for InMemoryEventStore class.
 * @internal
 */
class InMemoryEventStoreTest extends TestCase
{
    private InMemoryEventStore $eventStore;

    protected function setUp(): void
    {
        $this->eventStore = new InMemoryEventStore();
    }

    public function testStoreEvent(): void
    {
        $message = JsonRpcMessage::createRequest('test_method', ['param' => 'value'], 1);
        $streamId = 'test_stream';

        $eventId = $this->eventStore->storeEvent($streamId, $message);

        $this->assertNotEmpty($eventId);
        $this->assertStringContainsString($streamId, $eventId);
        $this->assertEquals(1, $this->eventStore->getEventCount($streamId));
    }

    public function testStoreMultipleEvents(): void
    {
        $streamId = 'test_stream';
        $message1 = JsonRpcMessage::createRequest('method1', [], 1);
        $message2 = JsonRpcMessage::createRequest('method2', [], 2);

        $eventId1 = $this->eventStore->storeEvent($streamId, $message1);
        $eventId2 = $this->eventStore->storeEvent($streamId, $message2);

        $this->assertNotEquals($eventId1, $eventId2);
        $this->assertEquals(2, $this->eventStore->getEventCount($streamId));
    }

    public function testReplayEventsAfterWithoutLastEventId(): void
    {
        $streamId = 'test_stream';
        $message1 = JsonRpcMessage::createRequest('method1', [], 1);
        $message2 = JsonRpcMessage::createRequest('method2', [], 2);

        $this->eventStore->storeEvent($streamId, $message1);
        $this->eventStore->storeEvent($streamId, $message2);

        $replayed = [];
        $count = $this->eventStore->replayEventsAfter($streamId, null, function ($message, $eventId) use (&$replayed) {
            $replayed[] = ['message' => $message, 'event_id' => $eventId];
        });

        $this->assertEquals(2, $count);
        $this->assertCount(2, $replayed);
        $this->assertEquals('method1', $replayed[0]['message']->getMethod());
        $this->assertEquals('method2', $replayed[1]['message']->getMethod());
    }

    public function testReplayEventsAfterWithLastEventId(): void
    {
        $streamId = 'test_stream';
        $message1 = JsonRpcMessage::createRequest('method1', [], 1);
        $message2 = JsonRpcMessage::createRequest('method2', [], 2);
        $message3 = JsonRpcMessage::createRequest('method3', [], 3);

        $eventId1 = $this->eventStore->storeEvent($streamId, $message1);
        $this->eventStore->storeEvent($streamId, $message2);
        $this->eventStore->storeEvent($streamId, $message3);

        $replayed = [];
        $count = $this->eventStore->replayEventsAfter($streamId, $eventId1, function ($message, $eventId) use (&$replayed) {
            $replayed[] = ['message' => $message, 'event_id' => $eventId];
        });

        $this->assertEquals(2, $count);
        $this->assertCount(2, $replayed);
        $this->assertEquals('method2', $replayed[0]['message']->getMethod());
        $this->assertEquals('method3', $replayed[1]['message']->getMethod());
    }

    public function testReplayEventsFromNonExistentStream(): void
    {
        $count = $this->eventStore->replayEventsAfter('non_existent', null, function () {});
        $this->assertEquals(0, $count);
    }

    public function testHasEvent(): void
    {
        $streamId = 'test_stream';
        $message = JsonRpcMessage::createRequest('test_method', [], 1);
        $eventId = $this->eventStore->storeEvent($streamId, $message);

        $this->assertTrue($this->eventStore->hasEvent($streamId, $eventId));
        $this->assertFalse($this->eventStore->hasEvent($streamId, 'non_existent_event'));
        $this->assertFalse($this->eventStore->hasEvent('non_existent_stream', $eventId));
    }

    public function testGetLatestEventId(): void
    {
        $streamId = 'test_stream';

        // No events yet
        $this->assertNull($this->eventStore->getLatestEventId($streamId));

        $message1 = JsonRpcMessage::createRequest('method1', [], 1);
        $message2 = JsonRpcMessage::createRequest('method2', [], 2);

        $eventId1 = $this->eventStore->storeEvent($streamId, $message1);
        $this->assertEquals($eventId1, $this->eventStore->getLatestEventId($streamId));

        $eventId2 = $this->eventStore->storeEvent($streamId, $message2);
        $this->assertEquals($eventId2, $this->eventStore->getLatestEventId($streamId));
    }

    public function testGetEventCount(): void
    {
        $streamId = 'test_stream';
        $this->assertEquals(0, $this->eventStore->getEventCount($streamId));

        $message = JsonRpcMessage::createRequest('test_method', [], 1);
        $this->eventStore->storeEvent($streamId, $message);
        $this->assertEquals(1, $this->eventStore->getEventCount($streamId));

        $this->eventStore->storeEvent($streamId, $message);
        $this->assertEquals(2, $this->eventStore->getEventCount($streamId));
    }

    public function testClearStream(): void
    {
        $streamId = 'test_stream';
        $message = JsonRpcMessage::createRequest('test_method', [], 1);

        $this->eventStore->storeEvent($streamId, $message);
        $this->eventStore->storeEvent($streamId, $message);
        $this->assertEquals(2, $this->eventStore->getEventCount($streamId));

        $cleared = $this->eventStore->clearStream($streamId);
        $this->assertEquals(2, $cleared);
        $this->assertEquals(0, $this->eventStore->getEventCount($streamId));
    }

    public function testClearNonExistentStream(): void
    {
        $cleared = $this->eventStore->clearStream('non_existent');
        $this->assertEquals(0, $cleared);
    }

    public function testClearAll(): void
    {
        $message = JsonRpcMessage::createRequest('test_method', [], 1);

        $this->eventStore->storeEvent('stream1', $message);
        $this->eventStore->storeEvent('stream1', $message);
        $this->eventStore->storeEvent('stream2', $message);

        $cleared = $this->eventStore->clearAll();
        $this->assertEquals(3, $cleared);
        $this->assertEquals(0, $this->eventStore->getEventCount('stream1'));
        $this->assertEquals(0, $this->eventStore->getEventCount('stream2'));
    }

    public function testGetStats(): void
    {
        $message = JsonRpcMessage::createRequest('test_method', [], 1);

        $this->eventStore->storeEvent('stream1', $message);
        $this->eventStore->storeEvent('stream1', $message);
        $this->eventStore->storeEvent('stream2', $message);

        $stats = $this->eventStore->getStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_events', $stats);
        $this->assertArrayHasKey('total_streams', $stats);
        $this->assertArrayHasKey('streams', $stats);

        $this->assertEquals(3, $stats['total_events']);
        $this->assertEquals(2, $stats['total_streams']);
        $this->assertCount(2, $stats['streams']);
    }

    public function testMaxEventsPerStreamLimit(): void
    {
        $maxEvents = 3;
        $eventStore = new InMemoryEventStore($maxEvents);
        $streamId = 'test_stream';
        $message = JsonRpcMessage::createRequest('test_method', [], 1);

        // Add more events than the limit
        for ($i = 1; $i <= 5; ++$i) {
            $eventStore->storeEvent($streamId, $message);
        }

        // Should only keep the last 3 events
        $this->assertEquals($maxEvents, $eventStore->getEventCount($streamId));
    }

    public function testEventExpiration(): void
    {
        // Test with a very short expiration time to avoid long waits
        $expirationTime = 1; // 1 second
        $eventStore = new InMemoryEventStore(1000, $expirationTime);
        $streamId = 'test_stream';
        $message = JsonRpcMessage::createRequest('test_method', [], 1);

        // Store event
        $eventId = $eventStore->storeEvent($streamId, $message);

        // Event should exist immediately
        $this->assertTrue($eventStore->hasEvent($streamId, $eventId));
        $this->assertEquals(1, $eventStore->getEventCount($streamId));

        // Test by modifying the event timestamp directly to simulate expiration
        // This is more reliable than waiting for actual time to pass
        $allEvents = $eventStore->getAllEvents($streamId);
        $this->assertCount(1, $allEvents);

        // Create a new event store with the same parameters but manually set old timestamp
        $testEventStore = new InMemoryEventStore(1000, $expirationTime);

        // Use reflection to manually set an expired event
        $reflection = new ReflectionClass($testEventStore);
        $eventsProperty = $reflection->getProperty('events');
        $eventsProperty->setAccessible(true);
        $countersProperty = $reflection->getProperty('eventCounters');
        $countersProperty->setAccessible(true);

        // Create an event with timestamp that's old enough to be expired
        $expiredTimestamp = time() - $expirationTime - 1; // 1 second past expiration
        $eventsProperty->setValue($testEventStore, [
            $streamId => [
                [
                    'event_id' => $eventId,
                    'message' => $message,
                    'timestamp' => $expiredTimestamp,
                ],
            ],
        ]);
        $countersProperty->setValue($testEventStore, [$streamId => 1]);

        // Now the event should be considered expired
        $this->assertFalse($testEventStore->hasEvent($streamId, $eventId));
        $this->assertEquals(0, $testEventStore->getEventCount($streamId));
    }

    public function testCleanup(): void
    {
        $expirationTime = 1; // 1 second
        $eventStore = new InMemoryEventStore(1000, $expirationTime);
        $streamId = 'test_stream';
        $message = JsonRpcMessage::createRequest('test_method', [], 1);

        // Use reflection to manually create expired and non-expired events
        $reflection = new ReflectionClass($eventStore);
        $eventsProperty = $reflection->getProperty('events');
        $eventsProperty->setAccessible(true);
        $countersProperty = $reflection->getProperty('eventCounters');
        $countersProperty->setAccessible(true);

        $currentTime = time();
        $expiredTimestamp = $currentTime - $expirationTime - 1; // Expired
        $validTimestamp = $currentTime; // Not expired

        // Set up events: 2 expired, 1 valid
        $eventsProperty->setValue($eventStore, [
            $streamId => [
                [
                    'event_id' => 'expired1',
                    'message' => $message,
                    'timestamp' => $expiredTimestamp,
                ],
                [
                    'event_id' => 'expired2',
                    'message' => $message,
                    'timestamp' => $expiredTimestamp,
                ],
                [
                    'event_id' => 'valid1',
                    'message' => $message,
                    'timestamp' => $validTimestamp,
                ],
            ],
        ]);
        $countersProperty->setValue($eventStore, [$streamId => 3]);

        // Cleanup should remove 2 expired events
        $cleaned = $eventStore->cleanup();
        $this->assertEquals(2, $cleaned);

        // Only 1 event should remain
        $this->assertEquals(1, $eventStore->getEventCount($streamId));
    }

    public function testConfigurationGettersAndSetters(): void
    {
        $eventStore = new InMemoryEventStore();

        // Test max events per stream
        $this->assertEquals(1000, $eventStore->getMaxEventsPerStream());
        $eventStore->setMaxEventsPerStream(500);
        $this->assertEquals(500, $eventStore->getMaxEventsPerStream());

        // Test expiration time
        $this->assertEquals(3600, $eventStore->getExpirationTime());
        $eventStore->setExpirationTime(1800);
        $this->assertEquals(1800, $eventStore->getExpirationTime());
    }

    public function testGetAllEventsForTesting(): void
    {
        $streamId = 'test_stream';
        $message1 = JsonRpcMessage::createRequest('method1', [], 1);
        $message2 = JsonRpcMessage::createRequest('method2', [], 2);

        $this->eventStore->storeEvent($streamId, $message1);
        $this->eventStore->storeEvent($streamId, $message2);

        $allEvents = $this->eventStore->getAllEvents($streamId);
        $this->assertCount(2, $allEvents);
        $this->assertEquals('method1', $allEvents[0]['message']->getMethod());
        $this->assertEquals('method2', $allEvents[1]['message']->getMethod());
    }

    public function testMonotonicEventIds(): void
    {
        $streamId = 'test_stream';
        $message = JsonRpcMessage::createRequest('test_method', [], 1);

        $eventIds = [];
        for ($i = 0; $i < 5; ++$i) {
            $eventIds[] = $this->eventStore->storeEvent($streamId, $message);
        }

        // Event IDs should be monotonically increasing within a stream
        for ($i = 1; $i < count($eventIds); ++$i) {
            // Event IDs format: streamId_counter
            $prevParts = explode('_', $eventIds[$i - 1]);
            $currParts = explode('_', $eventIds[$i]);

            $prev = (int) end($prevParts);
            $curr = (int) end($currParts);

            $this->assertGreaterThan($prev, $curr, "Event ID {$eventIds[$i]} should be greater than {$eventIds[$i - 1]}");
        }
    }
}
