<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SedApiException extends Exception
{
    protected int $httpStatusCode;
    protected array $context;

    public function __construct(
        string $message = '',
        int $httpStatusCode = 500,
        ?Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, 0, $previous);
        $this->httpStatusCode = $httpStatusCode;
        $this->context = $context;
    }

    /**
     * Get the HTTP status code associated with this exception
     * 
     * @return int
     */
    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    /**
     * Get additional context data
     * 
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Set additional context data
     * 
     * @param array $context
     * @return self
     */
    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Render the exception into an HTTP response
     * 
     * @param Request $request
     * @return Response
     */
    public function render(Request $request): Response
    {
        $statusCode = $this->getHttpStatusCode();
        
        // For API requests, return JSON response
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'error' => [
                    'message' => $this->getMessage(),
                    'type' => 'SED_API_ERROR',
                    'status_code' => $statusCode,
                    'context' => $this->getContext(),
                ]
            ], $statusCode);
        }

        // For web requests, you might want to redirect or show an error page
        return response()->view('errors.sed-api', [
            'message' => $this->getMessage(),
            'statusCode' => $statusCode,
            'context' => $this->getContext(),
        ], $statusCode);
    }

    /**
     * Report the exception
     * 
     * @return bool|null
     */
    public function report(): ?bool
    {
        // Log the exception with context
        \Log::error('SED API Exception: ' . $this->getMessage(), [
            'http_status' => $this->getHttpStatusCode(),
            'context' => $this->getContext(),
            'trace' => $this->getTraceAsString(),
        ]);

        return true;
    }

    /**
     * Create an authentication exception
     * 
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function authenticationFailed(string $message = 'Authentication failed', array $context = []): static
    {
        return new static($message, 401, null, $context);
    }

    /**
     * Create a token expired exception
     * 
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function tokenExpired(string $message = 'Token has expired', array $context = []): static
    {
        return new static($message, 401, null, $context);
    }

    /**
     * Create a configuration error exception
     * 
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function configurationError(string $message = 'SED API configuration error', array $context = []): static
    {
        return new static($message, 500, null, $context);
    }

    /**
     * Create a network error exception
     * 
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function networkError(string $message = 'Network error occurred', array $context = []): static
    {
        return new static($message, 503, null, $context);
    }

    /**
     * Create a rate limit exception
     * 
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function rateLimitExceeded(string $message = 'Rate limit exceeded', array $context = []): static
    {
        return new static($message, 429, null, $context);
    }

    /**
     * Create an invalid parameter exception
     * 
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function invalidParameter(string $message = 'Invalid parameter provided', array $context = []): static
    {
        return new static($message, 400, null, $context);
    }

    /**
     * Create a request failed exception
     * 
     * @param string $message
     * @param int $statusCode
     * @param string $responseBody
     * @param array $context
     * @return static
     */
    public static function requestFailed(string $message = 'Request failed', int $statusCode = 500, string $responseBody = '', array $context = []): static
    {
        $context['response_body'] = $responseBody;
        return new static($message, $statusCode, null, $context);
    }

    /**
     * Create a business error exception
     * 
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function businessError(string $message = 'Business logic error', array $context = []): static
    {
        return new static($message, 422, null, $context);
    }

    /**
     * Create an unexpected error exception
     * 
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function unexpectedError(string $message = 'Unexpected error occurred', array $context = []): static
    {
        return new static($message, 500, null, $context);
    }
}