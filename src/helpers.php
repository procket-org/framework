<?php

use Illuminate\Contracts\Database\Query\Expression as DbExpression;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Connection as DbConnection;
use Illuminate\Database\Query\Builder as DbQueryBuilder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Client\PendingRequest as HttpClientRequest;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Validation\Validator;
use League\Flysystem\Filesystem as Flysystem;
use Monolog\Logger;
use Pocket\Framework\Pocket;
use Pocket\Framework\ServiceApiException;
use Predis\Client as PredisClient;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\Psr16Cache as SimpleCache;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\SharedLockInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Twig\TemplateWrapper;

if (!function_exists('pocket')) {
    /**
     * Get the Pocket singleton
     *
     * @return Pocket
     */
    function pocket(): Pocket
    {
        return Pocket::instance();
    }
}

if (!function_exists('api')) {
    /**
     * Call the service API directly
     *
     * @param string|null $route The route string, obtained from request parameters by default
     * @param array|null $params Action parameters, obtained from request parameters by default
     * @param array|null $constructorParams Constructor parameters, obtained from request parameters by default
     * @throws ServiceApiException
     * @throws Throwable
     */
    function api(?string $route = null, ?array $params = null, ?array $constructorParams = null): mixed
    {
        return Pocket::api($route, $params, $constructorParams);
    }
}

if (!function_exists('config')) {
    /**
     * Get configuration
     *
     * @param array|string|null $key Configuration key, when null, you can get all configurations.
     * @param mixed|null $default Default value
     * @return array|mixed
     */
    function config(array|string $key = null, mixed $default = null): mixed
    {
        return pocket()->getConfig($key, $default);
    }
}

if (!function_exists('__')) {
    /**
     * Get the translation for the given key.
     *
     * @param string $key Translation key
     * @param array $replace Replacements
     * @param string|null $locale Locale
     * @return string|array
     */
    function __(string $key, array $replace = [], string $locale = null): array|string
    {
        return pocket()->trans($key, $replace, $locale);
    }
}

if (!function_exists('__n')) {
    /**
     * Get a translation according to an integer value.
     *
     * @param string $key Translation key
     * @param Countable|float|int|array $number Number
     * @param array $replace Replacements
     * @param string|null $locale Locale
     * @return string
     */
    function __n(string $key, Countable|float|int|array $number, array $replace = [], string $locale = null): string
    {
        return pocket()->transChoice($key, $number, $replace, $locale);
    }
}

if (!function_exists('render')) {
    /**
     * Render a twig template
     *
     * @param string|TemplateWrapper $name The template name
     * @param array $context Context parameters
     * @return string
     */
    function render(TemplateWrapper|string $name, array $context = []): string
    {
        return pocket()->render($name, $context);
    }
}

if (!function_exists('render_string')) {
    /**
     * Render a twig template string
     *
     * @param string $templateString Template string
     * @param array $context Context parameters
     * @return string
     */
    function render_string(string $templateString, array $context = []): string
    {
        return pocket()->renderString($templateString, $context);
    }
}

if (!function_exists('render_php')) {
    /**
     * Render a php template file
     *
     * @param string $file The php file that needs to be rendered
     * @param array $context Context parameters
     * @return string
     */
    function render_php(string $file, array $context = []): string
    {
        return pocket()->renderPhp($file, $context);
    }
}

if (!function_exists('logger')) {
    /**
     * Get logger
     *
     * @param string $channel The logging channel
     * @return Logger
     */
    function logger(string $channel = 'app'): Logger
    {
        return pocket()->getLogger($channel);
    }
}

if (!function_exists('request')) {
    /**
     * Get current HTTP request
     *
     * @return HttpRequest
     */
    function request(): HttpRequest
    {
        return pocket()->getHttpRequest();
    }
}

if (!function_exists('session')) {
    /**
     * Get the session associated with the current request
     *
     * @return Session
     */
    function session(): Session
    {
        return pocket()->getHttpSession();
    }
}

if (!function_exists('response')) {
    /**
     * Create a new HTTP response
     *
     * @param mixed $content response content
     * @param int $status response status code，default is 200
     * @param array $headers response headers
     * @return HttpResponse
     */
    function response(mixed $content = '', int $status = 200, array $headers = []): HttpResponse
    {
        return pocket()->makeHttpResponse($content, $status, $headers);
    }
}

if (!function_exists('http')) {
    /**
     * Create a new HTTP client request
     *
     * @return HttpClientRequest
     */
    function http(): HttpClientRequest
    {
        return pocket()->makeHttpClientRequest();
    }
}

if (!function_exists('db')) {
    /**
     * Get database connection
     *
     * @param string|null $connection Connection name
     * @return DbConnection
     */
    function db(string $connection = null): DbConnection
    {
        return pocket()->getDbConnection($connection);
    }
}

if (!function_exists('query')) {
    /**
     * Create a database table query
     *
     * @param Closure|string|DbExpression|DbQueryBuilder $table Table Name
     * @param string|null $as Table alias, defaults to null
     * @param string|null $connection Connection name
     * @return DbQueryBuilder
     */
    function query(Closure|string|DbQueryBuilder|DbExpression $table, string $as = null, string $connection = null): DbQueryBuilder
    {
        return pocket()->makeDbQuery($table, $as, $connection);
    }
}

if (!function_exists('redis')) {
    /**
     * Get predis client instance
     *
     * @param string|null $connection Connection name
     * @return PredisClient
     */
    function redis(string $connection = null): PredisClient
    {
        return pocket()->getRedis($connection);
    }
}

if (!function_exists('filesystem')) {
    /**
     * Get filesystem instance
     *
     * @return Filesystem
     */
    function filesystem(): Filesystem
    {
        return pocket()->getFilesystem();
    }
}

if (!function_exists('ensure_directory')) {
    /**
     * Ensure the directory exists
     *
     * @param string $path Directory path
     * @param int $mode Directory permission
     * @param bool $recursive Whether to recursively create directories
     * @return bool
     */
    function ensure_directory(string $path, int $mode = 0755, bool $recursive = true): bool
    {
        return pocket()->ensureDirectory($path, $mode, $recursive);
    }
}

if (!function_exists('disk')) {
    /**
     * Get the registered disk driver
     *
     * @param string $name The registered name
     * @return Flysystem
     */
    function disk(string $name): Flysystem
    {
        return pocket()->getDisk($name);
    }
}

if (!function_exists('validator')) {
    /**
     * Create a validator instance
     *
     * @param array $data Data to be verified
     * @param array $rules Validation rules
     * @param array $messages Validation message
     * @param array $customAttributes Custom attributes
     * @return Validator
     */
    function validator(
        array $data,
        array $rules,
        array $messages = [],
        array $customAttributes = []
    ): Validator
    {
        return pocket()->makeValidator($data, $rules, $messages, $customAttributes);
    }
}

if (!function_exists('cache')) {
    /**
     * Get cache instance
     *
     * @return TagAwareAdapterInterface|TagAwareCacheInterface
     */
    function cache(): TagAwareAdapterInterface|TagAwareCacheInterface
    {
        return pocket()->getCache();
    }
}

if (!function_exists('simple_cache')) {
    /**
     * Get simple cache instance
     *
     * @return SimpleCache
     */
    function simple_cache(): SimpleCache
    {
        return pocket()->getSimpleCache();
    }
}

if (!function_exists('lock')) {
    /**
     * Create and get a lock instance for the given resource
     *
     * @param string $resource The resource to lock
     * @param float|null $ttl Maximum expected lock duration in seconds
     * @param bool $autoRelease Whether to automatically release the lock or not when the lock instance is destroyed
     * @return LockInterface|SharedLockInterface
     */
    function lock(string $resource, ?float $ttl = 300.0, bool $autoRelease = true): LockInterface|SharedLockInterface
    {
        return pocket()->getLock($resource, $ttl, $autoRelease);
    }
}
