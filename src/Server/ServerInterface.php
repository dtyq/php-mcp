<?php

declare(strict_types=1);

namespace Dtyq\Mcp\Server;

interface ServerInterface
{
    /**
     * Register a function handler
     *
     * @param string $functionName The function name
     * @param callable $handler The function handler
     * @return self
     */
    public function registerFunction(string $functionName, callable $handler): self;
    
    /**
     * Handle an incoming request
     *
     * @param string $request The request data
     * @return string The response data
     */
    public function handleRequest(string $request): string;
    
    /**
     * Start the server
     *
     * @return void
     */
    public function start(): void;
    
    /**
     * Stop the server
     *
     * @return void
     */
    public function stop(): void;
} 