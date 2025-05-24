<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Exceptions;

/**
 * Exception for authentication and authorization errors.
 *
 * This exception is thrown when there are authentication failures,
 * authorization issues, or OAuth-related errors.
 */
class AuthenticationError extends McpError
{
    /**
     * Create an AuthenticationError with a specific error message.
     *
     * @param string $message The error message
     * @param mixed $data Additional error data (optional)
     */
    public function __construct(string $message, $data = null)
    {
        $error = new ErrorData(ErrorCodes::AUTHENTICATION_ERROR, $message, $data);
        parent::__construct($error);
    }

    /**
     * Create an AuthenticationError for invalid credentials.
     *
     * @param string $reason The reason for invalid credentials
     * @param mixed $data Additional error data (optional)
     */
    public static function invalidCredentials(string $reason = 'Invalid credentials', $data = null): AuthenticationError
    {
        return new self($reason, $data);
    }

    /**
     * Create an AuthenticationError for missing credentials.
     *
     * @param string $credentialType The type of missing credential
     * @param mixed $data Additional error data (optional)
     */
    public static function missingCredentials(string $credentialType, $data = null): AuthenticationError
    {
        return new self("Missing {$credentialType} credentials", $data);
    }

    /**
     * Create an AuthenticationError for expired credentials.
     *
     * @param string $credentialType The type of expired credential
     * @param mixed $data Additional error data (optional)
     */
    public static function expiredCredentials(string $credentialType, $data = null): AuthenticationError
    {
        return new self("{$credentialType} credentials have expired", $data);
    }

    /**
     * Create an AuthenticationError for insufficient permissions.
     *
     * @param string $operation The operation that requires permissions
     * @param string[] $requiredPermissions Required permissions
     * @param mixed $data Additional error data (optional)
     */
    public static function insufficientPermissions(
        string $operation,
        array $requiredPermissions = [],
        $data = null
    ): AuthenticationError {
        $permissions = empty($requiredPermissions) ? '' : ' (required: ' . implode(', ', $requiredPermissions) . ')';
        $error = new ErrorData(
            ErrorCodes::AUTHORIZATION_ERROR,
            "Insufficient permissions for operation '{$operation}'{$permissions}",
            $data
        );
        $exception = new McpError($error);
        return new self($exception->getMessage(), $data);
    }

    /**
     * Create an AuthenticationError for OAuth invalid scope.
     *
     * @param string $scope The invalid scope
     * @param string[] $validScopes List of valid scopes
     * @param mixed $data Additional error data (optional)
     */
    public static function invalidScope(string $scope, array $validScopes = [], $data = null): AuthenticationError
    {
        $valid = empty($validScopes) ? '' : ' (valid: ' . implode(', ', $validScopes) . ')';
        $error = new ErrorData(
            ErrorCodes::OAUTH_INVALID_SCOPE,
            "Invalid OAuth scope: {$scope}{$valid}",
            $data
        );
        $exception = new McpError($error);
        return new self($exception->getMessage(), $data);
    }

    /**
     * Create an AuthenticationError for OAuth invalid redirect URI.
     *
     * @param string $redirectUri The invalid redirect URI
     * @param string[] $validUris List of valid redirect URIs
     * @param mixed $data Additional error data (optional)
     */
    public static function invalidRedirectUri(string $redirectUri, array $validUris = [], $data = null): AuthenticationError
    {
        $valid = empty($validUris) ? '' : ' (valid: ' . implode(', ', $validUris) . ')';
        return new self("Invalid OAuth redirect URI: {$redirectUri}{$valid}", $data);
    }

    /**
     * Create an AuthenticationError for OAuth invalid client.
     *
     * @param string $clientId The invalid client ID
     * @param mixed $data Additional error data (optional)
     */
    public static function invalidClient(string $clientId, $data = null): AuthenticationError
    {
        return new self("Invalid OAuth client: {$clientId}", $data);
    }

    /**
     * Create an AuthenticationError for OAuth invalid grant.
     *
     * @param string $grantType The invalid grant type
     * @param string[] $supportedGrants List of supported grant types
     * @param mixed $data Additional error data (optional)
     */
    public static function invalidGrant(string $grantType, array $supportedGrants = [], $data = null): AuthenticationError
    {
        $supported = empty($supportedGrants) ? '' : ' (supported: ' . implode(', ', $supportedGrants) . ')';
        return new self("Invalid OAuth grant type: {$grantType}{$supported}", $data);
    }

    /**
     * Create an AuthenticationError for OAuth unauthorized client.
     *
     * @param string $clientId The unauthorized client ID
     * @param string $operation The operation that was unauthorized
     * @param mixed $data Additional error data (optional)
     */
    public static function unauthorizedClient(string $clientId, string $operation, $data = null): AuthenticationError
    {
        return new self("Unauthorized OAuth client '{$clientId}' for operation '{$operation}'", $data);
    }

    /**
     * Create an AuthenticationError for OAuth unsupported grant type.
     *
     * @param string $grantType The unsupported grant type
     * @param string[] $supportedGrants List of supported grant types
     * @param mixed $data Additional error data (optional)
     */
    public static function unsupportedGrantType(string $grantType, array $supportedGrants = [], $data = null): AuthenticationError
    {
        $supported = empty($supportedGrants) ? '' : ' (supported: ' . implode(', ', $supportedGrants) . ')';
        return new self("Unsupported OAuth grant type: {$grantType}{$supported}", $data);
    }

    /**
     * Create an AuthenticationError for OAuth invalid request.
     *
     * @param string $reason The reason for invalid request
     * @param mixed $data Additional error data (optional)
     */
    public static function invalidRequest(string $reason, $data = null): AuthenticationError
    {
        return new self("Invalid OAuth request: {$reason}", $data);
    }

    /**
     * Create an AuthenticationError for OAuth access denied.
     *
     * @param string $reason The reason for access denial
     * @param mixed $data Additional error data (optional)
     */
    public static function accessDenied(string $reason = 'Access denied', $data = null): AuthenticationError
    {
        return new self("OAuth access denied: {$reason}", $data);
    }

    /**
     * Create an AuthenticationError for OAuth unsupported response type.
     *
     * @param string $responseType The unsupported response type
     * @param string[] $supportedTypes List of supported response types
     * @param mixed $data Additional error data (optional)
     */
    public static function unsupportedResponseType(string $responseType, array $supportedTypes = [], $data = null): AuthenticationError
    {
        $supported = empty($supportedTypes) ? '' : ' (supported: ' . implode(', ', $supportedTypes) . ')';
        return new self("Unsupported OAuth response type: {$responseType}{$supported}", $data);
    }

    /**
     * Create an AuthenticationError for OAuth server error.
     *
     * @param string $reason The reason for server error
     * @param mixed $data Additional error data (optional)
     */
    public static function serverError(string $reason, $data = null): AuthenticationError
    {
        return new self("OAuth server error: {$reason}", $data);
    }

    /**
     * Create an AuthenticationError for OAuth temporarily unavailable.
     *
     * @param string $reason The reason for temporary unavailability
     * @param mixed $data Additional error data (optional)
     */
    public static function temporarilyUnavailable(string $reason = 'Service temporarily unavailable', $data = null): AuthenticationError
    {
        return new self("OAuth service temporarily unavailable: {$reason}", $data);
    }

    /**
     * Create an AuthenticationError for token expired.
     *
     * @param string $tokenType The type of token that expired
     * @param mixed $data Additional error data (optional)
     */
    public static function tokenExpired(string $tokenType = 'access token', $data = null): AuthenticationError
    {
        return new self("OAuth {$tokenType} has expired", $data);
    }

    /**
     * Create an AuthenticationError for invalid token.
     *
     * @param string $tokenType The type of invalid token
     * @param string $reason The reason for invalid token
     * @param mixed $data Additional error data (optional)
     */
    public static function invalidToken(string $tokenType, string $reason = 'Invalid token', $data = null): AuthenticationError
    {
        return new self("Invalid OAuth {$tokenType}: {$reason}", $data);
    }
}
