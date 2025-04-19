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
use Procket\Framework\Procket;
use Procket\Framework\ServiceApiException;
use Predis\Client as PredisClient;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\Psr16Cache as SimpleCache;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\SharedLockInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Twig\Environment as TwigEngine;
use Twig\TemplateWrapper;

if (!function_exists('procket')) {
    /**
     * Get the Procket singleton
     *
     * @return Procket
     */
    function procket(): Procket
    {
        return Procket::instance();
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
        return Procket::api($route, $params, $constructorParams);
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
        return procket()->getConfig($key, $default);
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
        return procket()->trans($key, $replace, $locale);
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
        return procket()->transChoice($key, $number, $replace, $locale);
    }
}

if (!function_exists('twig')) {
    /**
     * Get twig template engine
     *
     * @return TwigEngine
     */
    function twig(): TwigEngine
    {
        return procket()->getTwig();
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
        return procket()->render($name, $context);
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
        return procket()->renderString($templateString, $context);
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
        return procket()->renderPhp($file, $context);
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
        return procket()->getLogger($channel);
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
        return procket()->getHttpRequest();
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
        return procket()->getHttpSession();
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
        return procket()->makeHttpResponse($content, $status, $headers);
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
        return procket()->makeHttpClientRequest();
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
        return procket()->getDbConnection($connection);
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
        return procket()->makeDbQuery($table, $as, $connection);
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
        return procket()->getRedis($connection);
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
        return procket()->getFilesystem();
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
        return procket()->ensureDirectory($path, $mode, $recursive);
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
        return procket()->getDisk($name);
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
        return procket()->makeValidator($data, $rules, $messages, $customAttributes);
    }
}

if (!function_exists('validation_failed')) {
    /**
     * Determine if the value fails the validation rule.
     *
     * @param string $attribute Attribute name
     * @param mixed $value Value to be verified
     * @param string|array $rule Validation rule
     * @return string|null Return an error message if the validation fails. otherwise, return null.
     */
    function validation_failed(string $attribute, mixed $value, string|array $rule): ?string
    {
        return procket()->validationFailed($attribute, $value, $rule);
    }
}

if (!function_exists('validation_passed')) {
    /**
     * Determine if the value passes the validation rule.
     *
     * @param string $attribute Attribute name
     * @param mixed $value Value to be verified
     * @param string|array $rule Validation rule
     * @return bool
     */
    function validation_passed(string $attribute, mixed $value, string|array $rule): bool
    {
        return procket()->validationPassed($attribute, $value, $rule);
    }
}

if (!function_exists('validate')) {
    /**
     * Determine if the value passes the validation rule. If not, throw an {@see ServiceApiException} exception.
     *
     * @param string $attribute Attribute name
     * @param mixed $value Value to be verified
     * @param string|array $rule Validation rule
     * @return true
     * @throws ServiceApiException
     */
    function validate(string $attribute, mixed $value, string|array $rule): true
    {
        return procket()->validate($attribute, $value, $rule);
    }
}

if (!function_exists('validate_many')) {
    /**
     * Determine if the data passes the validation rules. If not, throw an {@see ServiceApiException} exception.
     *
     * @param array $data Data to be verified
     * @param array $rules Validation rules
     * @param array $messages Validation message
     * @param array $customAttributes Custom attributes
     * @return true
     * @throws ServiceApiException
     */
    function validate_many(
        array $data,
        array $rules,
        array $messages = [],
        array $customAttributes = []
    ): true
    {
        return procket()->validateMany($data, $rules, $messages, $customAttributes);
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
        return procket()->getCache();
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
        return procket()->getSimpleCache();
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
        return procket()->getLock($resource, $ttl, $autoRelease);
    }
}

############################################# Custom Functions #############################################

if (!function_exists('func_get_args_assoc')) {
    /**
     * Dynamically retrieves the argument names and values from a function or method call.
     *
     * **Manual Mode:**
     * If both `$args` (arguments) and `$func` (function name) are provided,
     * the function uses these values to perform the reflection, providing a fast,
     * low-overhead method of extracting argument names and values.
     *
     * **Auto Mode:**
     * If no manual arguments or function name are provided,
     * the function uses debug_backtrace() to inspect the calling function/method and extracts the arguments dynamically.
     * This mode is more flexible but slower.
     *
     * The following is a simple benchmark test executed 100,000 times for reference：
     * **Manual Mode:** 0.2912 seconds
     * **Auto Mode:** 0.4971 seconds
     *
     * @param array|null $args An array of arguments passed to the function or method, e.g. {@see func_get_args()}.
     * @param array|string|null $func The name of the function or method being called, e.g. `__FUNCTION__` or `[$this, __FUNCTION__]`.
     * @return array Returns an associative array where the keys are the parameter names and the values are the corresponding argument values passed to the function.
     * @throws ReflectionException
     */
    function func_get_args_assoc(array $args = null, array|string|null $func = null): array
    {
        // Use manual mode if args & func are provided
        if ($args !== null && $func !== null) {
            $ref = is_array($func) ? new ReflectionMethod($func[0], $func[1]) : new ReflectionFunction($func);
        } else {
            // Auto mode using debug_backtrace
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $caller = $trace[1];
            $args = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2)[1]['args'];
            $ref = isset($caller['class'])
                ? new ReflectionMethod($caller['class'], $caller['function'])
                : new ReflectionFunction($caller['function']);
        }

        $assoc = [];
        $params = $ref->getParameters();
        foreach ($params as $index => $param) {
            $assoc[$param->getName()] = $args[$index] ?? ($param->isDefaultValueAvailable() ? $param->getDefaultValue() : null);
        }

        return $assoc;
    }
}
