<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SED API Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration options for the SED (Secretaria de
    | Educação) API integration. These settings control how the application
    | connects to and interacts with the SED API services.
    |
    */

    'api' => [
        /*
        |--------------------------------------------------------------------------
        | API Base URL
        |--------------------------------------------------------------------------
        |
        | The base URL for the SED API. This should include the protocol (https://)
        | and the domain, but not the specific endpoint paths.
        |
        */
        'url' => env('SED_URL', 'https://homologacaointegracaosed.educacao.sp.gov.br/ncaapi/api'),

        /*
        |--------------------------------------------------------------------------
        | API Credentials
        |--------------------------------------------------------------------------
        |
        | The username and password used to authenticate with the SED API.
        | These should be stored in your .env file for security.
        |
        */
        'username' => env('SED_USERNAME'),
        'password' => env('SED_PASSWORD'),

        /*
        |--------------------------------------------------------------------------
        | Request Timeout
        |--------------------------------------------------------------------------
        |
        | The maximum time (in seconds) to wait for API responses before timing out.
        |
        */
        'timeout' => env('SED_TIMEOUT', 30),

        /*
        |--------------------------------------------------------------------------
        | ID da Diretoria
        |--------------------------------------------------------------------------
        |
        | Para realizar as consultas as APIs
        |
        */
        'diretoria_id' => env('SED_DIRETORIA_ID', 20207), // Jacarei

        /*
        |--------------------------------------------------------------------------
        | Codigo do Municipio
        |--------------------------------------------------------------------------
        |
        | Para realizar as consultas as APIs
        | Equivalente no SED : inCodMunicipio
        |
        */
        'municipio_id' => env('SED_MUNICIPIO_ID', 9267), // Jacarei

        /*
        |--------------------------------------------------------------------------
        | Codigo da Rede de Ensino
        |--------------------------------------------------------------------------
        |
        | Para realizar as consultas as APIs
        | Equivalente no SED : inCodRedeEnsino
        |
        |   1 – Estadual
        |   2 – Municipal
        |   3 – Privada
        |   4 – Federal
        |   5 – Estadual Outros (Centro Paula Souza)
        */
        'rede_ensino_cod' => env('SED_REDE_ENSINO_COD', 2), // Municipal

        /*
        |--------------------------------------------------------------------------
        | Token Configuration
        |--------------------------------------------------------------------------
        |
        | Settings related to authentication token management.
        |
        */
        'token' => [
            /*
            | Cache key prefix for storing tokens
            */
            'cache_key' => env('SED_TOKEN_CACHE_KEY', 'sed_api_token'),

            /*
            | Buffer time (in seconds) before token expiration to refresh the token
            */
            'expiration_buffer' => env('SED_TOKEN_EXPIRATION_BUFFER', 300), // 5 minutes

            /*
            | Default token lifetime (in seconds) if not provided by API
            */
            'default_lifetime' => env('SED_TOKEN_DEFAULT_LIFETIME', 3600), // 1 hour
        ],

        /*
        |--------------------------------------------------------------------------
        | Retry Configuration
        |--------------------------------------------------------------------------
        |
        | Settings for handling failed requests and retries.
        |
        */
        'retry' => [
            /*
            | Maximum number of retry attempts for failed requests
            */
            'max_attempts' => env('SED_RETRY_MAX_ATTEMPTS', 3),

            /*
            | Delay (in milliseconds) between retry attempts
            */
            'delay' => env('SED_RETRY_DELAY', 1000),

            /*
            | HTTP status codes that should trigger a retry
            */
            'retry_on_status' => [500, 502, 503, 504, 408, 429],
        ],

        /*
        |--------------------------------------------------------------------------
        | Rate Limiting
        |--------------------------------------------------------------------------
        |
        | Configuration for rate limiting API requests to avoid overwhelming
        | the SED API servers.
        |
        */
        'rate_limit' => [
            /*
            | Maximum requests per minute
            */
            'requests_per_minute' => env('SED_RATE_LIMIT_RPM', 60),

            /*
            | Cache key for rate limiting
            */
            'cache_key' => env('SED_RATE_LIMIT_CACHE_KEY', 'sed_api_rate_limit'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for logging SED API interactions.
    |
    */
    'logging' => [
        /*
        | Enable/disable detailed API request/response logging
        */
        'enabled' => env('SED_LOGGING_ENABLED', true),

        /*
        | Log channel to use for SED API logs
        */
        'channel' => env('SED_LOGGING_CHANNEL', 'single'),

        /*
        | Log level for SED API operations
        */
        'level' => env('SED_LOGGING_LEVEL', 'info'),

        /*
        | Whether to log request/response bodies (be careful with sensitive data)
        */
        'log_bodies' => env('SED_LOGGING_BODIES', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for caching API responses and other data.
    |
    */
    'cache' => [
        /*
        | Default cache TTL (in seconds) for API responses
        */
        'default_ttl' => env('SED_CACHE_TTL', 300), // 5 minutes

        /*
        | Cache key prefix for SED API data
        */
        'key_prefix' => env('SED_CACHE_PREFIX', 'sed_api'),

        /*
        | Cache store to use (null = default)
        */
        'store' => env('SED_CACHE_STORE', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Environment Configuration
    |--------------------------------------------------------------------------
    |
    | Settings that vary by environment (development, staging, production).
    |
    */
    'environment' => [
        /*
        | Whether this is a sandbox/testing environment
        */
        'is_sandbox' => env('SED_IS_SANDBOX', true),

        /*
        | Enable debug mode for additional logging and error details
        */
        'debug' => env('SED_DEBUG', env('APP_DEBUG', false)),
    ],

];