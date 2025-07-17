<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Utilities\SSE;

use Dtyq\PhpMcp\Shared\Exceptions\TransportError;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Generator;

/**
 * A simple, synchronous SSE (Server-Sent Events) client in PHP.
 *
 * This class provides a client to connect to an SSE endpoint and process events
 * in a blocking, synchronous manner. It uses basic PHP socket functions and
 * is designed to be straightforward and self-contained.
 *
 * Compatible with PHP 7.4+
 */
final class SSEClient
{
    /**
     * @var string The URL of the SSE stream
     */
    private string $url;

    /**
     * @var int Connection timeout in seconds
     */
    private int $timeout;

    /**
     * @var array<string, string> Custom headers to send with the request
     */
    private array $headers;

    /**
     * @var null|resource The socket resource for the connection
     */
    private $socket;

    /**
     * @var bool Whether the response is using chunked transfer encoding
     */
    private bool $isChunked = false;

    /**
     * @var string A buffer for incoming stream data
     */
    private string $buffer = '';

    /**
     * @var string The ID of the last event received
     */
    private string $lastEventId = '';

    /**
     * SSEClient constructor.
     *
     * @param string $url The URL of the SSE stream
     * @param int $timeout Connection timeout in seconds
     * @param array<string, string> $headers Custom headers to send with the request
     */
    public function __construct(string $url, int $timeout = 10, array $headers = [])
    {
        $this->url = $url;
        $this->timeout = $timeout;
        $this->headers = $headers;
    }

    /**
     * Destructor to ensure the socket connection is closed.
     */
    public function __destruct()
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }
    }

    /**
     * Connects to the SSE stream and returns a Generator of events.
     *
     * This method yields SSEEvent objects as they are received from the stream.
     * It will block execution while waiting for new data.
     *
     * @return Generator<SSEEvent>
     * @throws ValidationError if the URL is invalid
     * @throws TransportError if the connection fails or the server returns an error
     */
    public function getEvents(): Generator
    {
        $this->connect();

        if ($this->isChunked) {
            yield from $this->processChunkedStream();
        } else {
            yield from $this->processStream();
        }
    }

    /**
     * Establishes the socket connection and sends the HTTP request.
     */
    private function connect(): void
    {
        if ($this->socket) {
            return;
        }
        $urlParts = parse_url($this->url);
        if ($urlParts === false) {
            throw ValidationError::invalidFieldValue('url', "Invalid URL provided: {$this->url}");
        }

        $host = $urlParts['host'];
        $path = $urlParts['path'] ?? '/';
        $query = isset($urlParts['query']) ? "?{$urlParts['query']}" : '';
        $scheme = $urlParts['scheme'] ?? 'http';
        $port = $urlParts['port'] ?? ($scheme === 'https' ? 443 : 80);
        $transport = $scheme === 'https' ? 'ssl' : 'tcp';

        $this->socket = @fsockopen("{$transport}://{$host}", $port, $errno, $errstr, $this->timeout);

        if (! $this->socket) {
            throw TransportError::connectionRefused('SSE', "{$host}:{$port}. Error: {$errstr} ({$errno})");
        }

        $this->sendRequest($host, $path . $query);
        $this->processHeaders();
    }

    /**
     * Sends the initial HTTP GET request to the SSE endpoint.
     *
     * @param string $host The host to connect to
     * @param string $fullPath The full path including query string
     */
    private function sendRequest(string $host, string $fullPath): void
    {
        $defaultHeaders = [
            'Host' => $host,
            'Accept' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ];

        if ($this->lastEventId !== '') {
            $defaultHeaders['Last-Event-ID'] = $this->lastEventId;
        }

        $headers = array_merge($defaultHeaders, $this->headers);

        $request = "GET {$fullPath} HTTP/1.1\r\n";
        foreach ($headers as $name => $value) {
            $request .= "{$name}: {$value}\r\n";
        }
        $request .= "\r\n";

        if (fwrite($this->socket, $request) === false) {
            throw TransportError::connectionLost('HTTP');
        }
    }

    /**
     * Reads and processes the HTTP response headers.
     *
     * @throws TransportError if server returns a non-200 status code
     */
    private function processHeaders(): void
    {
        $headers = '';
        while (($line = fgets($this->socket)) !== false) {
            $headers .= $line;
            if (rtrim($line) === '') {
                break; // End of headers
            }
        }

        if (! preg_match('/^HTTP\/\d\.\d\s+200\s+OK/i', $headers)) {
            $this->closeConnection();
            throw new TransportError("Server returned a non-200 status. Full headers:\n{$headers}");
        }

        // Check for chunked transfer encoding
        if (preg_match('/^Transfer-Encoding:\s*chunked/im', $headers)) {
            $this->isChunked = true;
        }
    }

    /**
     * Parses a block of lines into an SSEEvent object.
     *
     * @param string $messageBlock A block of one or more lines representing an SSE message
     * @return null|SSEEvent An SSEEvent object or null if the message was empty
     */
    private function parseMessage(string $messageBlock): ?SSEEvent
    {
        $id = '';
        $event = 'message';
        $dataLines = [];
        $retry = 0;

        foreach (explode("\n", $messageBlock) as $line) {
            if ($this->startsWith($line, ':')) {
                continue; // Skip comments
            }

            // A line can be just the field name with no value
            $parts = explode(':', $line, 2);
            $field = $parts[0];
            $value = $parts[1] ?? '';

            // The spec says to trim a single leading space, if present.
            if ($this->startsWith($value, ' ')) {
                $value = substr($value, 1);
            }

            switch ($field) {
                case 'event':
                    $event = $value;
                    break;
                case 'data':
                    $dataLines[] = $value;
                    break;
                case 'id':
                    $id = $value;
                    break;
                case 'retry':
                    if (is_numeric($value)) {
                        $retry = (int) $value;
                    }
                    break;
            }
        }

        if (empty($dataLines)) {
            return null; // A message with no data is a comment, essentially.
        }

        return new SSEEvent($id, $event, implode("\n", $dataLines), $retry);
    }

    /**
     * Closes the socket connection.
     */
    private function closeConnection(): void
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
            $this->socket = null;
        }
    }

    /**
     * Processes a standard, non-chunked SSE stream.
     *
     * @return Generator<SSEEvent>
     */
    private function processStream(): Generator
    {
        // Set socket timeout to prevent indefinite blocking
        stream_set_timeout($this->socket, 1, 0); // 1 second timeout

        while (! feof($this->socket)) {
            $chunk = fread($this->socket, 8192);
            if ($chunk === false || $chunk === '') {
                break; // Connection closed or error
            }

            $this->buffer .= $chunk;

            yield from $this->yieldEventsFromBuffer();
        }
    }

    /**
     * Processes an SSE stream with chunked transfer encoding.
     *
     * @return Generator<SSEEvent>
     */
    private function processChunkedStream(): Generator
    {
        // Set socket timeout to prevent indefinite blocking
        stream_set_timeout($this->socket, 1, 0); // 1 second timeout

        while (true) {
            $sizeLine = fgets($this->socket);
            if ($sizeLine === false || trim($sizeLine) === '') {
                break;
            }

            $chunkSize = hexdec(trim($sizeLine));

            if ($chunkSize === 0) {
                break; // End of stream
            }

            $chunkData = '';
            $bytesToRead = $chunkSize;
            while ($bytesToRead > 0 && ! feof($this->socket)) {
                $read = fread($this->socket, $bytesToRead);
                if ($read === false || $read === '') {
                    break 2; // Connection closed or error
                }
                $chunkData .= $read;
                $bytesToRead -= strlen($read);
            }

            // Consume the CRLF at the end of the chunk
            fgets($this->socket);

            $this->buffer .= $chunkData;

            yield from $this->yieldEventsFromBuffer();
        }
    }

    /**
     * A generator that parses the internal buffer and yields any complete SSE events.
     *
     * @return Generator<SSEEvent>
     */
    private function yieldEventsFromBuffer(): Generator
    {
        // Normalize all newline characters to LF (\n) to handle CRLF from servers.
        $this->buffer = str_replace(["\r\n", "\r"], "\n", $this->buffer);

        while (($pos = strpos($this->buffer, "\n\n")) !== false) {
            $messageBlock = substr($this->buffer, 0, $pos);
            $this->buffer = substr($this->buffer, $pos + 2);

            $event = $this->parseMessage($messageBlock);
            if ($event !== null) {
                if ($event->id !== '') {
                    $this->lastEventId = $event->id;
                }
                yield $event;
            }
        }
    }

    /**
     * Polyfill for str_starts_with() (PHP 8.0+) to support PHP 7.4+.
     *
     * @param string $haystack The string to search in
     * @param string $needle The string to search for
     * @return bool True if haystack starts with needle, false otherwise
     */
    private function startsWith(string $haystack, string $needle): bool
    {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }
}
