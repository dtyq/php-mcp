<?php

declare(strict_types=1);

namespace Dtyq\Mcp\Client;

interface ClientInterface
{
    /**
     * Send a request to the MCP server
     *
     * @param string $functionName The function name to call
     * @param array $parameters The parameters for the function
     * @return mixed The response from the server
     */
    public function call(string $functionName, array $parameters = []);
    
    /**
     * Set the client options
     *
     * @param array $options The client options
     * @return self
     */
    public function setOptions(array $options): self;
} 