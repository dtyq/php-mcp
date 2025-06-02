<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client\Transport\Http;

use Dtyq\PhpMcp\Shared\Message\JsonRpcMessage;

/**
 * Interface for event storage implementations.
 *
 * This interface defines the contract for event storage systems that support
 * the event replay mechanism in MCP Streamable HTTP transport. Event stores
 * are responsible for persisting events with their IDs and enabling replay
 * functionality for connection recovery.
 */
interface EventStore
{
    /**
     * Store an event and return the event ID.
     *
     * Events are stored with a unique ID that can be used later for replay.
     * The event ID should be monotonically increasing within a stream to
     * ensure proper ordering during replay.
     *
     * @param string $streamId The stream identifier (typically session ID)
     * @param JsonRpcMessage $message The JSON-RPC message to store
     * @return string The assigned event ID
     */
    public function storeEvent(string $streamId, JsonRpcMessage $message): string;

    /**
     * Replay events that occurred after the specified event ID.
     *
     * This method is used to recover missed events when a connection is
     * restored. Events are replayed in the order they were stored, starting
     * from the event immediately after the given last event ID.
     *
     * @param string $streamId The stream identifier
     * @param null|string $lastEventId The last event ID received by the client.
     *                                 If null, replay from the beginning.
     * @param callable $callback Callback function to handle each replayed event.
     *                           Signature: function(JsonRpcMessage $message, string $eventId): void
     * @return int Number of events replayed
     */
    public function replayEventsAfter(string $streamId, ?string $lastEventId, callable $callback): int;

    /**
     * Check if an event exists in the store.
     *
     * @param string $streamId The stream identifier
     * @param string $eventId The event ID to check
     * @return bool True if the event exists, false otherwise
     */
    public function hasEvent(string $streamId, string $eventId): bool;

    /**
     * Get the latest event ID for a stream.
     *
     * @param string $streamId The stream identifier
     * @return null|string The latest event ID, or null if no events exist
     */
    public function getLatestEventId(string $streamId): ?string;

    /**
     * Get the count of stored events for a stream.
     *
     * @param string $streamId The stream identifier
     * @return int Number of stored events
     */
    public function getEventCount(string $streamId): int;

    /**
     * Clean up expired or old events.
     *
     * This method should be called periodically to prevent unbounded growth
     * of the event store. The cleanup strategy depends on the implementation
     * (e.g., time-based, count-based, or size-based).
     *
     * @return int Number of events cleaned up
     */
    public function cleanup(): int;

    /**
     * Clear all events for a specific stream.
     *
     * @param string $streamId The stream identifier
     * @return int Number of events cleared
     */
    public function clearStream(string $streamId): int;

    /**
     * Clear all events from all streams.
     *
     * @return int Total number of events cleared
     */
    public function clearAll(): int;

    /**
     * Get statistics about the event store.
     *
     * @return array<string, mixed> statistics including total events, streams, memory usage, etc
     */
    public function getStats(): array;
}
