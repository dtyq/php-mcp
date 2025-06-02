<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client\Transport\Http;

use Dtyq\PhpMcp\Shared\Message\JsonRpcMessage;

/**
 * In-memory implementation of the EventStore interface.
 *
 * This implementation stores events in memory and is suitable for
 * development, testing, and scenarios where event persistence across
 * process restarts is not required. Events are lost when the process
 * terminates.
 */
class InMemoryEventStore implements EventStore
{
    /** @var array<string, array<array{event_id: string, message: JsonRpcMessage, timestamp: int}>> */
    private array $events = [];

    /** @var array<string, int> Event counters per stream */
    private array $eventCounters = [];

    /** @var int Maximum events per stream */
    private int $maxEventsPerStream;

    /** @var int Event expiration time in seconds */
    private int $expirationTime;

    /**
     * @param int $maxEventsPerStream Maximum events to store per stream (default: 1000)
     * @param int $expirationTime Event expiration time in seconds (default: 3600 = 1 hour)
     */
    public function __construct(int $maxEventsPerStream = 1000, int $expirationTime = 3600)
    {
        $this->maxEventsPerStream = $maxEventsPerStream;
        $this->expirationTime = $expirationTime;
    }

    public function storeEvent(string $streamId, JsonRpcMessage $message): string
    {
        // Initialize stream if it doesn't exist
        if (! isset($this->events[$streamId])) {
            $this->events[$streamId] = [];
            $this->eventCounters[$streamId] = 0;
        }

        // Generate monotonically increasing event ID
        ++$this->eventCounters[$streamId];
        $eventId = $streamId . '_' . $this->eventCounters[$streamId];

        // Store the event
        $event = [
            'event_id' => $eventId,
            'message' => $message,
            'timestamp' => time(),
        ];

        $this->events[$streamId][] = $event;

        // Clean up old events if we exceed the limit
        if (count($this->events[$streamId]) > $this->maxEventsPerStream) {
            array_shift($this->events[$streamId]);
        }

        return $eventId;
    }

    public function replayEventsAfter(string $streamId, ?string $lastEventId, callable $callback): int
    {
        if (! isset($this->events[$streamId])) {
            return 0;
        }

        $events = $this->events[$streamId];
        $startIndex = 0;
        $replayedCount = 0;

        // Find the starting position if lastEventId is provided
        if ($lastEventId !== null) {
            foreach ($events as $index => $event) {
                if ($event['event_id'] === $lastEventId) {
                    $startIndex = $index + 1;
                    break;
                }
            }
        }

        // Replay events from the starting position
        for ($i = $startIndex; $i < count($events); ++$i) {
            $event = $events[$i];

            // Skip expired events
            if ($this->isEventExpired($event['timestamp'])) {
                continue;
            }

            $callback($event['message'], $event['event_id']);
            ++$replayedCount;
        }

        return $replayedCount;
    }

    public function hasEvent(string $streamId, string $eventId): bool
    {
        if (! isset($this->events[$streamId])) {
            return false;
        }

        foreach ($this->events[$streamId] as $event) {
            if ($event['event_id'] === $eventId) {
                return ! $this->isEventExpired($event['timestamp']);
            }
        }

        return false;
    }

    public function getLatestEventId(string $streamId): ?string
    {
        if (! isset($this->events[$streamId]) || empty($this->events[$streamId])) {
            return null;
        }

        // Get the last event in the stream
        $lastEvent = end($this->events[$streamId]);

        // Check if the event is expired
        if ($this->isEventExpired($lastEvent['timestamp'])) {
            return null;
        }

        return $lastEvent['event_id'];
    }

    public function getEventCount(string $streamId): int
    {
        if (! isset($this->events[$streamId])) {
            return 0;
        }

        $count = 0;
        foreach ($this->events[$streamId] as $event) {
            if (! $this->isEventExpired($event['timestamp'])) {
                ++$count;
            }
        }

        return $count;
    }

    public function cleanup(): int
    {
        $cleanedCount = 0;
        $expireTime = time() - $this->expirationTime;

        foreach ($this->events as $streamId => $events) {
            $originalCount = count($events);

            // Filter out expired events
            $this->events[$streamId] = array_filter($events, function ($event) use ($expireTime) {
                return $event['timestamp'] > $expireTime;
            });

            // Re-index the array
            $this->events[$streamId] = array_values($this->events[$streamId]);

            $cleanedCount += $originalCount - count($this->events[$streamId]);

            // Remove empty streams
            if (empty($this->events[$streamId])) {
                unset($this->events[$streamId], $this->eventCounters[$streamId]);
            }
        }

        return $cleanedCount;
    }

    public function clearStream(string $streamId): int
    {
        if (! isset($this->events[$streamId])) {
            return 0;
        }

        $count = count($this->events[$streamId]);
        unset($this->events[$streamId], $this->eventCounters[$streamId]);

        return $count;
    }

    public function clearAll(): int
    {
        $totalCount = 0;

        foreach ($this->events as $events) {
            $totalCount += count($events);
        }

        $this->events = [];
        $this->eventCounters = [];

        return $totalCount;
    }

    public function getStats(): array
    {
        $totalEvents = 0;
        $totalStreams = count($this->events);
        $streamStats = [];

        foreach ($this->events as $streamId => $events) {
            $validEvents = 0;
            $expiredEvents = 0;

            foreach ($events as $event) {
                if ($this->isEventExpired($event['timestamp'])) {
                    ++$expiredEvents;
                } else {
                    ++$validEvents;
                }
            }

            $totalEvents += $validEvents;
            $streamStats[$streamId] = [
                'valid_events' => $validEvents,
                'expired_events' => $expiredEvents,
                'latest_event_id' => $this->getLatestEventId($streamId),
            ];
        }

        return [
            'total_events' => $totalEvents,
            'total_streams' => $totalStreams,
            'max_events_per_stream' => $this->maxEventsPerStream,
            'expiration_time' => $this->expirationTime,
            'memory_usage' => memory_get_usage(true),
            'streams' => $streamStats,
        ];
    }

    /**
     * Get the maximum events per stream setting.
     *
     * @return int Maximum events per stream
     */
    public function getMaxEventsPerStream(): int
    {
        return $this->maxEventsPerStream;
    }

    /**
     * Set the maximum events per stream.
     *
     * @param int $maxEvents Maximum events per stream
     */
    public function setMaxEventsPerStream(int $maxEvents): void
    {
        $this->maxEventsPerStream = $maxEvents;
    }

    /**
     * Get the event expiration time in seconds.
     *
     * @return int Expiration time in seconds
     */
    public function getExpirationTime(): int
    {
        return $this->expirationTime;
    }

    /**
     * Set the event expiration time.
     *
     * @param int $expirationTime Expiration time in seconds
     */
    public function setExpirationTime(int $expirationTime): void
    {
        $this->expirationTime = $expirationTime;
    }

    /**
     * Get all events for a stream (for testing purposes).
     *
     * @param string $streamId Stream identifier
     * @return array<array{event_id: string, message: JsonRpcMessage, timestamp: int}>
     */
    public function getAllEvents(string $streamId): array
    {
        return $this->events[$streamId] ?? [];
    }

    /**
     * Check if an event is expired based on its timestamp.
     *
     * @param int $timestamp Event timestamp
     * @return bool True if the event is expired
     */
    private function isEventExpired(int $timestamp): bool
    {
        return (time() - $timestamp) > $this->expirationTime;
    }
}
