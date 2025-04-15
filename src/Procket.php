<?php

namespace Procket\Framework;

use Closure;
use Composer\InstalledVersions;
use Countable;
use Dotenv\Dotenv;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Database\Query\Expression as DbExpression;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Capsule\Manager as DbManager;
use Illuminate\Database\Connection as DbConnection;
use Illuminate\Database\Query\Builder as DbQueryBuilder;
use Illuminate\Database\Schema\Builder as DbSchemaBuilder;
use Illuminate\Events\Dispatcher as EventDispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Client\PendingRequest as HttpClientRequest;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Env;
use Illuminate\Support\Str;
use Illuminate\Translation\FileLoader as TranslationFileLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as Validation;
use Illuminate\Validation\Validator;
use InvalidArgumentException;
use League\Flysystem\Filesystem as Flysystem;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\WebProcessor;
use Procket\Framework\Disk\DiskManager;
use Procket\Framework\Extensions\Twig\TranslationExtension;
use Predis\Client as PredisClient;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;
use stdClass;
use Symfony\Component\Cache\Adapter\FilesystemTagAwareAdapter;
use Symfony\Component\Cache\Adapter\RedisTagAwareAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\Psr16Cache as SimpleCache;
use Symfony\Component\Console\Application as ConsoleApp;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\SharedLockInterface;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Lock\Store\RedisStore;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Throwable;
use Twig\Environment as TwigEngine;
use Twig\Loader\FilesystemLoader as TwigFileLoader;
use Twig\TemplateWrapper;

/**
 * The Procket Framework
 */
class Procket
{
    use ClassPropertiesAware;

    /**
     * Whether to enable debug
     * @var bool
     */
    public bool $debug = false;

    /**
     * Whether to enable multiple groups
     * @var bool
     */
    public bool $multipleGroups = false;

    /**
     * Locale code
     * @var string|null
     */
    public ?string $locale = null;

    /**
     * Fallback locale code
     * @var string|null
     */
    public ?string $fallbackLocale = null;

    /**
     * Route query parameter name
     * @var string
     */
    public string $routeName = 'route';

    /**
     * Registered middleware
     * @var array|null
     */
    public ?array $middleware = null;

    /**
     * Default group name
     * @var string
     */
    public string $defaultGroup = 'Home';

    /**
     * Default service name
     * @var string
     */
    public string $defaultService = 'Index';

    /**
     * Default action name
     * @var string
     */
    public string $defaultAction = 'index';

    /**
     * Magic service name
     * @var string
     */
    public string $magicServiceName = 'MagicService';

    /**
     * Default database connection name
     * @var string
     */
    public string $defaultDbConnection = 'default';

    /**
     * Database connection configurations
     * @var array|null
     */
    public ?array $dbConnections = null;

    /**
     * Default redis connection name
     * @var string
     */
    public string $defaultRedisConnection = 'default';

    /**
     * Redis connection configurations
     * @var array|null
     */
    public ?array $redisConnections = null;

    /**
     * Cache driver
     *
     * Supported drivers: "file", "redis"
     * @var string
     */
    public string $cacheDriver = 'file';

    /**
     * Redis cache connection name
     * @var string
     */
    public string $redisCacheConnection = 'cache';

    /**
     * Lock driver
     *
     * Supported drivers: "file", "redis"
     * @var string
     */
    public string $lockDriver = 'file';

    /**
     * Redis lock connection name
     * @var string
     */
    public string $redisLockConnection = 'lock';

    /**
     * Twig template engine options
     * @var array|null
     */
    public ?array $twigOptions = null;

    /**
     * Disk configurations
     * @var array|null
     */
    public ?array $diskConfigs = null;

    /**
     * Routed group
     * @var string|null
     */
    protected ?string $routedGroup = null;

    /**
     * Routed service
     * @var string|null
     */
    protected ?string $routedService = null;

    /**
     * Routed action
     * @var string|null
     */
    protected ?string $routedAction = null;

    /**
     * Routed path
     * @var string|null
     */
    protected ?string $routedPath = null;

    /**
     * Routed segments
     * @var array
     */
    protected array $routedSegments = [];

    /**
     * Routed service is magic service or not
     * @var bool
     */
    protected bool $routedServiceIsMagic = false;

    /**
     * Routed action is magic action or not
     * @var bool
     */
    protected bool $routedActionIsMagic = false;

    /**
     * Routed service instance
     * @var ServiceInterface|null
     */
    protected ?ServiceInterface $routedServiceInstance = null;

    /**
     * Stack of staged routed properties
     * @var array[]
     */
    protected array $stagedRoutedProperties = [];

    /**
     * Config repository instance
     * @var ConfigRepository|null
     */
    protected ?ConfigRepository $configRepository = null;

    /**
     * Translator instance
     * @var Translator|null
     */
    protected ?Translator $translator = null;

    /**
     * Logger instances
     * @var Logger[]
     */
    protected array $loggers = [];

    /**
     * Current HTTP request
     * @var HttpRequest|null
     */
    protected ?HttpRequest $httpRequest = null;

    /**
     * Database manager
     * @var DbManager|null
     */
    protected ?DbManager $dbManager = null;

    /**
     * Twig template engine
     * @var TwigEngine|null
     */
    protected ?TwigEngine $twig = null;

    /**
     * Filesystem
     * @var Filesystem|null
     */
    protected ?Filesystem $filesystem = null;

    /**
     * Disk manager
     * @var DiskManager|null
     */
    protected ?DiskManager $diskManager = null;

    /**
     * Validator factory
     * @var Validation|null
     */
    protected ?Validation $validation = null;

    /**
     * Cache instance
     * @var TagAwareAdapterInterface|TagAwareCacheInterface|null
     */
    protected TagAwareAdapterInterface|TagAwareCacheInterface|null $cache = null;

    /**
     * Simple cache instance
     * @var SimpleCache|null
     */
    protected ?SimpleCache $simpleCache = null;

    /**
     * Lock factory instance
     * @var LockFactory|null
     */
    protected ?LockFactory $lockFactory = null;

    /**
     * Console application
     * @var ConsoleApp|null
     */
    protected ?ConsoleApp $consoleApp = null;

    /**
     * Memory cache of predis client instances
     * @var PredisClient[]
     */
    private array $predisClients = [];

    /**
     * Singleton
     * @var static|null
     */
    private static ?Procket $instance = null;

    /**
     * To prevent multiple instances from being created
     */
    private function __construct()
    {
    }

    /**
     * To prevent the instance from being cloned
     */
    private function __clone()
    {
    }

    /**
     * Get the Procket singleton
     *
     * @return self
     */
    public static function instance(): Procket
    {
        if (isset(self::$instance)) {
            return self::$instance;
        }

        self::$instance = new self();
        self::$instance->init();

        return self::$instance;
    }

    /**
     * Initialize the application
     *
     * @return void
     */
    protected function init(): void
    {
        try {
            $this->loadDotEnv();
            $this->loadBootstrap();
        } catch (ServiceApiException $e) {
            $httpMsg = $e->getMessage();
            $httpStatus = $e->getCode();
            $this->makeHttpResponse($httpMsg, $httpStatus)->send();
            exit(255);
        } catch (Throwable $e) {
            if ($this->debug()) {
                $httpMsg = (string)$e;
            } else {
                $httpMsg = 'An error occurred while booting';
                $this->getLogger()->error((string)$e);
            }
            $this->makeHttpResponse($httpMsg, 500)->send();
            exit(255);
        }
    }

    /**
     * Load the dot environment file
     *
     * @return void
     */
    protected function loadDotEnv(): void
    {
        Dotenv::create(Env::getRepository(), APP_BASE_PATH)->safeLoad();
    }

    /**
     * Load the bootstrap
     *
     * @return void
     * @throws Throwable
     */
    protected function loadBootstrap(): void
    {
        if (class_exists(APP_NS_PREFIX . '\\Bootstrap')) {
            $class = APP_NS_PREFIX . '\\Bootstrap';
            $bootstrap = new $class();
            $rfBootstrap = new ReflectionClass($bootstrap);
            $rfMethods = $rfBootstrap->getMethods(ReflectionMethod::IS_PUBLIC);
            foreach ($rfMethods as $rfMethod) {
                if (Str::startsWith($rfMethod->getName(), 'init')) {
                    $rfMethod->invoke($bootstrap);
                }
            }
        }
    }

    /**
     * Stage routed properties
     *
     * @return array[]
     */
    protected function stageRoutedProperties(): array
    {
        $this->stagedRoutedProperties[] = [
            $this->routedGroup,
            $this->routedService,
            $this->routedAction,
            $this->routedPath,
            $this->routedSegments,
            $this->routedServiceIsMagic,
            $this->routedActionIsMagic,
            $this->routedServiceInstance
        ];

        return $this->stagedRoutedProperties;
    }

    /**
     * Restore the staged routed properties
     *
     * @return array[]
     */
    protected function restoreRoutedProperties(): array
    {
        $routedProperties = array_pop($this->stagedRoutedProperties);

        if (!is_null($routedProperties)) {
            [
                $this->routedGroup,
                $this->routedService,
                $this->routedAction,
                $this->routedPath,
                $this->routedSegments,
                $this->routedServiceIsMagic,
                $this->routedActionIsMagic,
                $this->routedServiceInstance
            ] = $routedProperties;
        }

        return $this->stagedRoutedProperties;
    }

    /**
     * Parse the route string
     *
     * @param string|null $route The route string, obtained from request parameters by default
     * @return array{
     *     group: string,
     *     service: string,
     *     action: string,
     *     path: string,
     *     segments: string[],
     *     service_class: string|null,
     *     service_is_magic: bool
     * }
     */
    protected function parseRoute(?string $route = null): array
    {
        $default = implode(
            '/',
            $this->multipleGroups() ?
                [$this->defaultGroup, $this->defaultService, $this->defaultAction] :
                [$this->defaultService, $this->defaultAction]
        );
        $inputRoute = $this->getHttpRequest()->input($this->routeName);
        $route = trim(($route ?: $inputRoute) ?: $default, '/');

        $parts = [];
        foreach (explode('/', $route) as $part) {
            if (trim($part) !== '') {
                $parts[] = $part;
            }
        }

        if ($this->multipleGroups()) {
            // If there are two parts, we will consider them to be a group and a service
            if (count($parts) === 2) {
                $parts = [$parts[0], $parts[1], $this->defaultAction];
            } // If there is only one part, we will consider it a group
            else if (count($parts) === 1) {
                $parts = [$parts[0], $this->defaultService, $this->defaultAction];
            }
            $group = $parts[0];
            $service = $parts[1];
            $action = $parts[2];
            $path = implode('/', array_slice($parts, 0, 3));
            $segments = array_slice($parts, 3);
        } else {
            // If there is only one part, we will consider it a service
            if (count($parts) === 1) {
                $parts = [$parts[0], $this->defaultAction];
            }
            $group = $this->defaultGroup;
            $service = $parts[0];
            $action = $parts[1];
            $path = implode('/', array_slice($parts, 0, 2));
            $segments = array_slice($parts, 2);
        }

        $serviceClass = $this->getServiceClassName($group, $service);
        // if the service class does not exist and the magic service exists
        $magicServiceClass = $this->getServiceNamespace($group) . '\\' . $this->formatServiceName($this->magicServiceName);
        if (!class_exists($serviceClass) && class_exists($magicServiceClass)) {
            $serviceIsMagic = true;
            $serviceClass = $magicServiceClass;
        } else {
            $serviceIsMagic = false;
        }

        return [
            'group' => $group,
            'service' => $service,
            'action' => $action,
            'path' => $path,
            'segments' => $segments,
            'service_class' => $serviceClass,
            'service_is_magic' => $serviceIsMagic
        ];
    }

    /**
     * Resolve route into current route properties
     *
     * @param string|null $route The route string, obtained from request parameters by default
     * @param array|null $constructorParams Constructor parameters, obtained from request parameters by default
     * @return void
     * @throws ReflectionException
     * @throws ServiceApiException
     */
    protected function route(?string $route = null, ?array $constructorParams = null): void
    {
        $routeProperties = $this->parseRoute($route);
        $this->routedGroup = $routeProperties['group'];
        $this->routedService = $routeProperties['service'];
        $this->routedAction = $routeProperties['action'];
        $this->routedPath = $routeProperties['path'];
        $this->routedSegments = $routeProperties['segments'];

        $serviceClass = $routeProperties['service_class'];
        $this->routedServiceIsMagic = $routeProperties['service_is_magic'];
        if (!$this->getRoutedGroup() || !$this->getRoutedService()) {
            throw new ServiceApiException(sprintf(
                "Parameter '%s' is invalid",
                $this->routeName
            ));
        }
        if (!class_exists($serviceClass)) {
            throw new ServiceApiException(sprintf(
                "Service class '%s' not found", $serviceClass
            ), 404);
        }
        if (!is_subclass_of($serviceClass, ServiceInterface::class)) {
            throw new RuntimeException(sprintf(
                "Class '%s' is not a service, should implements '%s'",
                $serviceClass,
                ServiceInterface::class
            ));
        }

        /** @var ServiceInterface $serviceInstance */
        $rfService = new ReflectionClass($serviceClass);
        if ($rfService->hasMethod('__construct')) {
            $invokeArgs = $this->getClassMethodArgsFromArray($serviceClass, '__construct', $constructorParams);
            $serviceInstance = $rfService->newInstanceArgs($invokeArgs);
        } else {
            $serviceInstance = $rfService->newInstance();
        }
        $this->routedServiceInstance = $serviceInstance;
        $this->routedActionIsMagic = false;

        if (!method_exists($serviceInstance, $this->getRoutedAction())) {
            if (method_exists($serviceInstance, '__call')) {
                $this->routedActionIsMagic = true;
            } else {
                throw new ServiceApiException(sprintf(
                    "Resource '%s' not found", $this->getRoutedPath()
                ), 404);
            }
        }
    }

    /**
     * Initialize paginator
     *
     * @return void
     */
    protected function initPaginator(): void
    {
        Paginator::currentPathResolver(function () {
            return $this->getHttpRequest()->url();
        });
        Paginator::currentPageResolver(function ($pageName = 'page') {
            $page = $this->getHttpRequest()->input($pageName);
            if (filter_var($page, FILTER_VALIDATE_INT) !== false && (int)$page >= 1) {
                return (int)$page;
            }
            return 1;
        });
        Paginator::queryStringResolver(function () {
            return $this->getHttpRequest()->query();
        });
        CursorPaginator::currentCursorResolver(function ($cursorName = 'cursor') {
            return Cursor::fromEncoded($this->getHttpRequest()->input($cursorName));
        });
    }

    /**
     * Get or switch DEBUG status
     *
     * @param bool|null $enable Defaults to null to get the status
     * @return bool|$this
     */
    public function debug(?bool $enable = null): bool|static
    {
        if (is_null($enable)) {
            return $this->debug;
        } else {
            $this->debug = $enable;
            return $this;
        }
    }

    /**
     * Get or switch multiple groups status
     *
     * @param bool|null $enable Defaults to null to get the status
     * @return bool|$this
     */
    public function multipleGroups(?bool $enable = null): bool|static
    {
        if (is_null($enable)) {
            return $this->multipleGroups;
        } else {
            $this->multipleGroups = $enable;
            return $this;
        }
    }

    /**
     * Get or switch locale
     *
     * @param string|null $locale Locale code, defaults to null to get the locale
     * @return string|null|$this
     */
    public function locale(?string $locale = null): string|static|null
    {
        if (is_null($locale)) {
            return $this->locale;
        } else {
            $this->locale = $locale;
            return $this;
        }
    }

    /**
     * Get or switch fallback locale
     *
     * @param string|null $fallbackLocale Fallback locale code, defaults to null to get the fallback locale
     * @return string|null|$this
     */
    public function fallbackLocale(?string $fallbackLocale = null): string|static|null
    {
        if (is_null($fallbackLocale)) {
            return $this->fallbackLocale;
        } else {
            $this->fallbackLocale = $fallbackLocale;
            return $this;
        }
    }

    /**
     * Get the locale choice
     *
     * @return string
     */
    public function localeChoice(): string
    {
        return ($this->locale() ?: $this->fallbackLocale()) ?: 'en';
    }

    /**
     * Get routed path
     *
     * @param bool $normalize Normalize the routed path
     * @return string|null
     */
    public function getRoutedPath(bool $normalize = true): ?string
    {
        if ($normalize && $this->routedPath) {
            $parts = explode('/', $this->routedPath);
            if ($this->multipleGroups()) {
                [$routedGroup, $routedService, $routedAction] = $parts;
                return implode('/', [
                    Str::studly($routedGroup),
                    $this->formatServiceName($routedService, true),
                    Str::camel($routedAction)
                ]);
            } else {
                [$routedService, $routedAction] = $parts;
                return implode('/', [
                    $this->formatServiceName($routedService, true),
                    Str::camel($routedAction)
                ]);
            }
        }

        return $this->routedPath;
    }

    /**
     * Get routed segments
     *
     * @return array
     */
    public function getRoutedSegments(): array
    {
        return $this->routedSegments;
    }

    /**
     * Get routed group
     *
     * @param bool $studlyCase get studly-case group
     * @return string|null
     */
    public function getRoutedGroup(bool $studlyCase = true): ?string
    {
        if ($studlyCase && $this->routedGroup) {
            return Str::studly($this->routedGroup) ?: null;
        }

        return $this->routedGroup;
    }

    /**
     * Get routed service
     *
     * @param bool $studlyCase get studly-case service
     * @return string|null
     */
    public function getRoutedService(bool $studlyCase = true): ?string
    {
        if ($studlyCase && $this->routedService) {
            return $this->formatServiceName($this->routedService);
        }

        return $this->routedService;
    }

    /**
     * Get routed action
     *
     * @param bool $camelCase get camel-case action
     * @return string|null
     */
    public function getRoutedAction(bool $camelCase = true): ?string
    {
        if ($camelCase && $this->routedAction) {
            return Str::camel($this->routedAction) ?: null;
        }

        return $this->routedAction;
    }

    /**
     * Routed service is magic service or not
     *
     * @return bool
     */
    public function routedServiceIsMagic(): bool
    {
        return $this->routedServiceIsMagic;
    }

    /**
     * Routed action is magic action or not
     *
     * @return bool
     */
    public function routedActionIsMagic(): bool
    {
        return $this->routedActionIsMagic;
    }

    /**
     * Get routed service instance
     *
     * @return ServiceInterface|null
     */
    public function getRoutedServiceInstance(): ?ServiceInterface
    {
        return $this->routedServiceInstance;
    }

    /**
     * Get configuration
     *
     * @param array|string|null $key Configuration key, when null, you can get all configurations.
     * @param mixed|null $default Default value
     * @return array|mixed
     */
    public function getConfig(array|string $key = null, mixed $default = null): mixed
    {
        if (!isset($this->configRepository)) {
            $this->configRepository = new ConfigRepository();
            $filesystem = new Filesystem();
            if ($filesystem->isDirectory(CONFIG_PATH)) {
                foreach ($filesystem->allFiles(CONFIG_PATH) as $file) {
                    $relPath = $file->getRelativePathname();
                    $pathInfo = pathinfo($relPath);
                    $dirname = data_get($pathInfo, 'dirname');
                    $filename = data_get($pathInfo, 'filename');
                    $fileExt = data_get($pathInfo, 'extension');
                    if (strtolower($fileExt) !== 'php') {
                        continue;
                    }
                    if ($dirname === '.') {
                        $fileKey = $filename;
                    } else {
                        $fileKey = str_replace('/', '.', $dirname) . '.' . $filename;
                    }
                    $fileConfig = include CONFIG_PATH . '/' . $relPath;
                    $this->configRepository->set($fileKey, (array)$fileConfig);
                }
            }
        }

        if (!is_null($key)) {
            return $this->configRepository->get($key, $default);
        }

        return $this->configRepository->all();
    }

    /**
     * Get translator instance
     *
     * @return Translator
     */
    public function getTranslator(): Translator
    {
        if (isset($this->translator)) {
            return $this->translator;
        }

        $loader = new TranslationFileLoader($this->getFilesystem(), LANG_PATH);
        $translator = new Translator($loader, $this->localeChoice());

        return $this->translator = $translator;
    }

    /**
     * Get the translation for the given key.
     *
     * @param string $key Translation key
     * @param array $replace Replacements
     * @param string|null $locale Locale
     * @return string|array
     */
    public function trans(string $key, array $replace = [], string $locale = null): array|string
    {
        return $this->getTranslator()->get($key, $replace, $locale);
    }

    /**
     * Get a translation according to an integer value.
     *
     * @param string $key Translation key
     * @param Countable|float|int|array $number Number
     * @param array $replace Replacements
     * @param string|null $locale Locale
     * @return string
     */
    public function transChoice(string $key, Countable|float|int|array $number, array $replace = [], string $locale = null): string
    {
        return $this->getTranslator()->choice($key, $number, $replace, $locale);
    }

    /**
     * Get logger
     *
     * @param string $channel The logging channel
     * @return Logger
     */
    public function getLogger(string $channel = 'app'): Logger
    {
        if (isset($this->loggers[$channel])) {
            return $this->loggers[$channel];
        }

        $logsDir = LOGS_PATH . '/' . $channel;
        if (!$this->ensureDirectory($logsDir)) {
            $logsDir = LOGS_PATH;
        }
        $logFile = $logsDir . '/' . date('Y_m_d') . '.log';
        $logger = new Logger($channel);
        $logger->pushHandler(new StreamHandler($logFile));
        if (php_sapi_name() === 'cli') {
            $logger->pushHandler(new StreamHandler('php://stdout'));
        } else {
            $logger->pushProcessor(new WebProcessor());
        }

        return $this->loggers[$channel] = $logger;
    }

    /**
     * Get current HTTP request
     *
     * @return HttpRequest
     */
    public function getHttpRequest(): HttpRequest
    {
        if (isset($this->httpRequest)) {
            return $this->httpRequest;
        }

        return $this->httpRequest = HttpRequest::capture();
    }

    /**
     * Get the session associated with the current request
     *
     * @return Session
     */
    public function getHttpSession(): Session
    {
        return $this->getHttpRequest()->session();
    }

    /**
     * Create a new HTTP response
     *
     * @param mixed $content response content
     * @param int $status response status code，default is 200
     * @param array $headers response headers
     * @return HttpResponse
     */
    public function makeHttpResponse(mixed $content = '', int $status = 200, array $headers = []): HttpResponse
    {
        return new HttpResponse($content, $status, $headers);
    }

    /**
     * Create a new HTTP client request
     *
     * @return HttpClientRequest
     */
    public function makeHttpClientRequest(): HttpClientRequest
    {
        return new HttpClientRequest();
    }

    /**
     * Start and get database manager
     *
     * @return DbManager
     */
    public function getDbManager(): DbManager
    {
        if (isset($this->dbManager)) {
            return $this->dbManager;
        }

        if (is_null($connections = $this->dbConnections)) {
            $connections = [
                $this->defaultDbConnection => [
                    'driver' => 'mysql',
                    'host' => env('DB_HOST', '127.0.0.1'),
                    'port' => env('DB_PORT', '3306'),
                    'database' => env('DB_DATABASE', 'test'),
                    'username' => env('DB_USERNAME', 'root'),
                    'password' => env('DB_PASSWORD', 'root'),
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                ]
            ];
        }

        $capsule = new DbManager();
        foreach ($connections as $name => $connection) {
            $capsule->addConnection($connection, $name);
        }
        $capsule->getDatabaseManager()->setDefaultConnection($this->defaultDbConnection);

        $dispatcher = new EventDispatcher(new Container());
        $capsule->setEventDispatcher($dispatcher);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $this->initPaginator();

        return $this->dbManager = $capsule;
    }

    /**
     * Get database connection
     *
     * @param string|null $connection Connection name
     * @return DbConnection
     */
    public function getDbConnection(string $connection = null): DbConnection
    {
        if (is_null($connection)) {
            $connection = $this->defaultDbConnection;
        }

        return $this->getDbManager()->getConnection($connection);
    }

    /**
     * Get database schema builder
     *
     * @param string|null $connection Connection name
     * @return DbSchemaBuilder
     */
    public function getDbSchema(string $connection = null): DbSchemaBuilder
    {
        return $this->getDbConnection($connection)->getSchemaBuilder();
    }

    /**
     * Get all table names in database
     *
     * @param string|null $connection Connection name
     * @return array
     */
    public function getDbTableNames(string $connection = null): array
    {
        $allTablesInfo = collect($this->getDbSchema($connection)->getTables());

        return $allTablesInfo->pluck('name')->all();
    }

    /**
     * Create a database table query
     *
     * @param Closure|string|DbExpression|DbQueryBuilder $table Table Name
     * @param string|null $as Table alias, defaults to null
     * @param string|null $connection Connection name
     * @return DbQueryBuilder
     */
    public function makeDbQuery(Closure|string|DbQueryBuilder|DbExpression $table, string $as = null, string $connection = null): DbQueryBuilder
    {
        return $this->getDbConnection($connection)->table($table, $as);
    }

    /**
     * Get predis client instance
     *
     * @param string|null $connection Connection name
     * @return PredisClient
     */
    public function getRedis(string $connection = null): PredisClient
    {
        if (is_null($connection)) {
            $connection = $this->defaultRedisConnection;
        }
        if (isset($this->predisClients[$connection])) {
            return $this->predisClients[$connection];
        }

        if (is_null($connections = $this->redisConnections)) {
            $connections = [
                $this->defaultRedisConnection => [
                    'parameters' => [
                        'host' => env('REDIS_HOST', '127.0.0.1'),
                        'port' => env('REDIS_PORT', '6379'),
                        'username' => env('REDIS_USERNAME'),
                        'password' => env('REDIS_PASSWORD'),
                        'database' => env('REDIS_DB', '0'),
                    ],
                    'options' => [
                        'prefix' => 'procket_database:'
                    ]
                ],
                'cache' => [
                    'parameters' => [
                        'host' => env('REDIS_HOST', '127.0.0.1'),
                        'port' => env('REDIS_PORT', '6379'),
                        'username' => env('REDIS_USERNAME'),
                        'password' => env('REDIS_PASSWORD'),
                        'database' => env('REDIS_CACHE_DB', '1'),
                    ],
                    'options' => [
                        'prefix' => 'procket_cache:'
                    ]
                ],
                'lock' => [
                    'parameters' => [
                        'host' => env('REDIS_HOST', '127.0.0.1'),
                        'port' => env('REDIS_PORT', '6379'),
                        'username' => env('REDIS_USERNAME'),
                        'password' => env('REDIS_PASSWORD'),
                        'database' => env('REDIS_LOCK_DB', '2'),
                    ],
                    'options' => [
                        'prefix' => 'procket_lock:'
                    ]
                ]
            ];
        }

        if (!isset($connections[$connection])) {
            throw new InvalidArgumentException(sprintf(
                "Redis connection '%s' not configured",
                $connection
            ));
        }

        $predisConfig = $connections[$connection];
        $parameters = Arr::get($predisConfig, 'parameters');
        $options = Arr::get($predisConfig, 'options');
        $redis = new PredisClient($parameters, $options);

        return $this->predisClients[$connection] = $redis;
    }

    /**
     * Get twig template engine
     *
     * @return TwigEngine
     */
    public function getTwig(): TwigEngine
    {
        if (isset($this->twig)) {
            return $this->twig;
        }

        $cacheFolderName = basename(TEMPLATES_PATH) ?: 'templates';
        $defaultOptions = [
            'debug' => $this->debug(),
            'cache' => CACHE_PATH . '/' . $cacheFolderName
        ];
        $options = array_merge($defaultOptions, (array)$this->twigOptions);

        $loader = new TwigFileLoader(TEMPLATES_PATH, APP_BASE_PATH);
        $twig = new TwigEngine($loader, $options);
        $twig->addExtension(new TranslationExtension());

        return $this->twig = $twig;
    }

    /**
     * Render a twig template
     *
     * @param string|TemplateWrapper $name The template name
     * @param array $context Context parameters
     * @return string
     */
    public function render(TemplateWrapper|string $name, array $context = []): string
    {
        if (
            is_string($name) &&
            file_exists($name) &&
            !$this->getTwig()->getLoader()->exists($name)
        ) {
            return $this->renderString(file_get_contents($name), $context);
        } else {
            return $this->getTwig()->render($name, $context);
        }
    }

    /**
     * Render a twig template string
     *
     * @param string $templateString Template string
     * @param array $context Context parameters
     * @return string
     */
    public function renderString(string $templateString, array $context = []): string
    {
        $templateWrapper = $this->getTwig()->createTemplate($templateString);

        return $this->render($templateWrapper, $context);
    }

    /**
     * Render a php template file
     *
     * @param string $file The php file that needs to be rendered
     * @param array $context Context parameters
     * @return string
     */
    public function renderPhp(string $file, array $context = []): string
    {
        $isAbsolute = strspn($file, '/\\', 0, 1)
            || (strlen($file) > 3 && ctype_alpha($file[0])
                && ':' === $file[1]
                && strspn($file, '/\\', 2, 1)
            )
            || null !== parse_url($file, PHP_URL_SCHEME);
        $file = $isAbsolute ? $file : TEMPLATES_PATH . '/' . $file;
        if (!file_exists($file)) {
            throw new InvalidArgumentException(sprintf('Unable to find php template "%s"', $file));
        }

        $content = (function () use ($file, $context) {
            ob_start();
            if ($context) {
                extract($context, EXTR_SKIP);
            }
            include $file;
            return ob_get_clean();
        })();
        if ($content === false) {
            throw new RuntimeException('Output buffering is not active');
        }

        return $content;
    }

    /**
     * Get filesystem instance
     *
     * @return Filesystem
     */
    public function getFilesystem(): Filesystem
    {
        if (isset($this->filesystem)) {
            return $this->filesystem;
        }

        return $this->filesystem = new Filesystem();
    }

    /**
     * Ensure the directory exists
     *
     * @param string $path Directory path
     * @param int $mode Directory permission
     * @param bool $recursive Whether to recursively create directories
     * @return bool
     */
    public function ensureDirectory(string $path, int $mode = 0755, bool $recursive = true): bool
    {
        if (!$this->getFilesystem()->isDirectory($path)) {
            return $this->getFilesystem()->makeDirectory($path, $mode, $recursive, true);
        }

        return true;
    }

    /**
     * Get disk manager
     *
     * @return DiskManager
     */
    public function getDiskManager(): DiskManager
    {
        if (isset($this->diskManager)) {
            return $this->diskManager;
        }

        if (is_null($configs = $this->diskConfigs)) {
            $configs = [
                'local' => [
                    'driver' => 'local',
                    'root' => STORAGE_PATH,
                ]
            ];
        }

        $diskManager = new DiskManager();
        foreach ($configs as $name => $config) {
            $diskManager->register($name, $config);
        }

        return $this->diskManager = $diskManager;
    }

    /**
     * Get the registered disk driver
     *
     * @param string $name The registered name
     * @return Flysystem
     */
    public function getDisk(string $name): Flysystem
    {
        return $this->getDiskManager()->disk($name);
    }

    /**
     * Get validator factory
     *
     * @return Validation
     */
    public function getValidation(): Validation
    {
        if (isset($this->validation)) {
            return $this->validation;
        }

        $validation = new Validation($this->getTranslator(), new Container());

        return $this->validation = $validation;
    }

    /**
     * Create a validator instance
     *
     * @param array $data Data to be verified
     * @param array $rules Validation rules
     * @param array $messages Validation message
     * @param array $customAttributes Custom attributes
     * @return Validator
     */
    public function makeValidator(
        array $data,
        array $rules,
        array $messages = [],
        array $customAttributes = []
    ): Validator
    {
        return $this->getValidation()->make($data, $rules, $messages, $customAttributes);
    }

    /**
     * Determine if the value fails the validation rule.
     *
     * @param string $attribute Attribute name
     * @param mixed $value Value to be verified
     * @param string|array $rule Validation rule
     * @return string|null Return an error message if the validation fails. otherwise, return null.
     */
    public function validationFailed(string $attribute, mixed $value, string|array $rule): ?string
    {
        $validator = $this->makeValidator(
            [$attribute => $value],
            [$attribute => $rule]
        );

        if ($validator->fails()) {
            return $validator->errors()->first($attribute) ?: 'Attribute validation failed.';
        }

        return null;
    }

    /**
     * Determine if the value passes the validation rule.
     *
     * @param string $attribute Attribute name
     * @param mixed $value Value to be verified
     * @param string|array $rule Validation rule
     * @return bool
     */
    public function validationPassed(string $attribute, mixed $value, string|array $rule): bool
    {
        return !$this->validationFailed($attribute, $value, $rule);
    }

    /**
     * Get cache instance
     *
     * @return TagAwareAdapterInterface|TagAwareCacheInterface
     */
    public function getCache(): TagAwareAdapterInterface|TagAwareCacheInterface
    {
        if (isset($this->cache)) {
            return $this->cache;
        }

        if ($this->cacheDriver === 'file') {
            $instance = new FilesystemTagAwareAdapter(
                '', 0, CACHE_PATH . '/app'
            );
        } else if ($this->cacheDriver === 'redis') {
            $instance = new RedisTagAwareAdapter(
                $this->getRedis($this->redisCacheConnection)
            );
        } else {
            throw new InvalidArgumentException(sprintf(
                "Cache driver '%s' not supported",
                $this->cacheDriver
            ));
        }

        return $this->cache = $instance;
    }

    /**
     * Get simple cache instance
     *
     * @return SimpleCache
     */
    public function getSimpleCache(): SimpleCache
    {
        if (isset($this->simpleCache)) {
            return $this->simpleCache;
        }

        return $this->simpleCache = new SimpleCache($this->getCache());
    }

    /**
     * Create and get a lock instance for the given resource
     *
     * @param string $resource The resource to lock
     * @param float|null $ttl Maximum expected lock duration in seconds
     * @param bool $autoRelease Whether to automatically release the lock or not when the lock instance is destroyed
     * @return LockInterface|SharedLockInterface
     */
    public function getLock(string $resource, ?float $ttl = 300.0, bool $autoRelease = true): LockInterface|SharedLockInterface
    {
        if (!isset($this->lockFactory)) {
            if ($this->lockDriver === 'file') {
                $store = new FlockStore(CACHE_PATH . '/locks');
            } else if ($this->lockDriver === 'redis') {
                $store = new RedisStore($this->getRedis($this->redisLockConnection));
            } else {
                throw new InvalidArgumentException(sprintf(
                    "Lock driver '%s' not supported",
                    $this->lockDriver
                ));
            }
            $this->lockFactory = new LockFactory($store);
        }

        return $this->lockFactory->createLock($resource, $ttl, $autoRelease);
    }

    /**
     * Get console application
     *
     * @return ConsoleApp
     */
    public function getConsoleApp(): ConsoleApp
    {
        if (isset($this->consoleApp)) {
            return $this->consoleApp;
        }

        $version = InstalledVersions::getVersion('procket/framework');
        $this->consoleApp = new ConsoleApp('procket', $version);

        // Add built-in commands
        $allCommandFiles = [
            'built_in' => $this->getFilesystem()->files(__DIR__ . '/Commands')
        ];
        // Add app commands
        if (is_dir(APP_PATH . '/Commands')) {
            $allCommandFiles['app'] = $this->getFilesystem()->files(APP_PATH . '/Commands');
        }
        foreach ($allCommandFiles as $type => $files) {
            if ($type === 'built_in') {
                $nsPrefix = '\\Procket\\Framework\\Commands\\';
            } else if ($type === 'app') {
                $nsPrefix = APP_NS_PREFIX . '\\Commands\\';
            } else {
                continue;
            }
            foreach ($files as $file) {
                if (strtolower($file->getExtension()) !== 'php') {
                    continue;
                }
                $class = $nsPrefix . $file->getFilenameWithoutExtension();
                if (is_subclass_of($class, Command::class)) {
                    $this->consoleApp->add(new $class);
                }
            }
        }

        return $this->consoleApp;
    }

    /**
     * Run console command
     *
     * @param string $name Command name, e.g. 'demo:greet'
     * @param array|null $arguments Command arguments
     * @param array|null $options Command options
     * @param OutputInterface|null $output Command output, defaults to {@see ConsoleOutput}
     * @return int The command exit code
     * @throws ExceptionInterface
     */
    public function runConsoleCommand(
        string           $name,
        ?array           $arguments = null,
        ?array           $options = null,
        ?OutputInterface &$output = null
    ): int
    {
        if (is_null($output)) {
            $output = new ConsoleOutput();
        }

        $command = $this->getConsoleApp()->find($name);

        $parameters = [];
        if (is_array($arguments)) {
            foreach ($arguments as $argName => $argValue) {
                $parameters[$argName] = $argValue;
            }
        }
        if (is_array($options)) {
            foreach ($options as $optionName => $optionValue) {
                $parameters[Str::start($optionName, '--')] = $optionValue;
            }
        }

        return $command->run(new ArrayInput($parameters), $output);
    }

    /**
     * Get the called arguments of the class method
     *
     * ```
     * Note: {{namedParams}} has higher priority than {{segments}}
     *
     * If method is magic method '__call', the return array will be:
     * [
     *      'the routed action',
     *      [
     *          'name' => original name,
     *          'arguments' => segments array,
     *          'options' => key-value pairs
     *      ]
     * ]
     * ```
     *
     * @param object|string $class class name or class instance
     * @param string $method method name
     * @param array|null $namedParams Method parameter key-value pair, obtained from request parameters by default
     * @param array|null $segments Method parameter segments, obtained from route segments by default
     * @return array
     * @throws ReflectionException
     * @throws InvalidArgumentException
     */
    public function getClassMethodArgs(
        object|string $class,
        string        $method,
        ?array        $namedParams = null,
        ?array        $segments = null
    ): array
    {
        if (is_null($namedParams)) {
            $namedParams = $this->getHttpRequest()->toArray();
            unset($namedParams[$this->routeName]);
        }
        if (is_null($segments)) {
            $segments = $this->getRoutedSegments();
        }

        if ($method === '__call') {
            return [$this->getRoutedAction(), [
                'name' => $this->getRoutedAction(false),
                'arguments' => $segments,
                'options' => $namedParams
            ]];
        }

        $camelParams = [];
        foreach ($namedParams as $paramName => $paramValue) {
            $camelParams[Str::camel($paramName)] = $paramValue;
        }

        $rfActionMethod = new ReflectionMethod($class, $method);
        $rfActionParams = $rfActionMethod->getParameters();

        $sortedActionArgs = [];
        foreach ($rfActionParams as $index => $rfActionParam) {
            $actionParamName = $rfActionParam->name;
            $camelActionParamName = Str::camel($actionParamName);
            if (array_key_exists($camelActionParamName, $camelParams)) {
                $argValue = $camelParams[$camelActionParamName];
            } else if (array_key_exists($actionParamName, $namedParams)) {
                $argValue = $namedParams[$actionParamName];
            } else if (array_key_exists($index, $segments)) {
                $argValue = $segments[$index];
            } else {
                if ($rfActionParam->isDefaultValueAvailable()) {
                    $argValue = $rfActionParam->getDefaultValue();
                } else {
                    throw new InvalidArgumentException(sprintf(
                        "Missing value of argument '%s' for %s::%s()",
                        $actionParamName,
                        is_object($class) ? get_class($class) : $class,
                        $method
                    ));
                }
            }
            $sortedActionArgs[] = $argValue;
        }

        return $sortedActionArgs;
    }

    /**
     * Get the called arguments of the class method from an array
     *
     * @param object|string $class class name or class instance
     * @param string $method method name
     * @param array|null $array associative array or sequential array, obtained from request by default
     * @return array
     * @throws ReflectionException
     * @see Procket::getClassMethodArgs()
     */
    public function getClassMethodArgsFromArray(object|string $class, string $method, ?array $array = null): array
    {
        if (is_null($array)) {
            $invokeArgs = $this->getClassMethodArgs($class, $method);
        } else {
            if (Arr::isAssoc($array)) {
                $invokeArgs = $this->getClassMethodArgs($class, $method, $array, []);
            } else {
                $invokeArgs = $this->getClassMethodArgs($class, $method, [], $array);
            }
        }

        return $invokeArgs;
    }

    /**
     * Format service name (studly case)
     *
     * @param string|null $service Service name, supporting dot notation
     * @param bool $dotNotation whether to return the dot notation name, defaults to false
     * @return string|null
     */
    public function formatServiceName(?string $service, bool $dotNotation = false): ?string
    {
        if (!$service) {
            return null;
        }

        $dottedService = str_replace('\\', '.', $service);
        $serviceParts = array_filter(explode('.', $dottedService));
        if (!$serviceParts) {
            return null;
        }

        $formattedService = implode(
            $dotNotation ? '.' : '\\',
            array_map(function ($part) {
                return Str::studly($part);
            }, $serviceParts)
        );

        return $formattedService ?: null;
    }

    /**
     * Get the namespace of the service class
     *
     * @param string|null $group Group name, obtained from request parameters by default
     * @return string|null
     */
    public function getServiceNamespace(?string $group = null): ?string
    {
        $group = ($group ? Str::studly($group) : null) ?: $this->getRoutedGroup();

        if ($this->multipleGroups()) {
            $classNsPrefix = $group ? APP_NS_PREFIX . '\\Services\\' . $group : null;
        } else {
            $classNsPrefix = APP_NS_PREFIX . '\\Services';
        }

        return $classNsPrefix;
    }

    /**
     * Get the full class name of the service class
     *
     * @param string|null $group Group name, obtained from request parameters by default
     * @param string|null $service Service name, supports dot notation, obtained from request parameters by default
     * @return string|null
     */
    public function getServiceClassName(?string $group = null, ?string $service = null): ?string
    {
        $group = ($group ? Str::studly($group) : null) ?: $this->getRoutedGroup();
        $service = $this->formatServiceName($service) ?: $this->getRoutedService();
        if (!$service) {
            return null;
        }

        $classNsPrefix = $this->getServiceNamespace($group);
        $class = $classNsPrefix . '\\' . $service;
        if (!class_exists($class)) {
            $class = $classNsPrefix . '\\' . Str::singular($service);
        }

        return $class;
    }

    /**
     * Get registered middleware
     *
     * @return array
     */
    public function getMiddleware(): array
    {
        return (array)$this->middleware;
    }

    /**
     * Call service API
     *
     * @param string|null $route The route string, obtained from request parameters by default
     * @param array|null $params Action parameters, obtained from request parameters by default
     * @param array|null $constructorParams Constructor parameters, obtained from request parameters by default
     * @return mixed
     * @throws ServiceApiException
     * @throws Throwable
     */
    public function callServiceApi(?string $route = null, ?array $params = null, ?array $constructorParams = null): mixed
    {
        $this->route($route, $constructorParams);

        $serviceInstance = $this->getRoutedServiceInstance();
        $method = $this->routedActionIsMagic() ? '__call' : $this->getRoutedAction();
        $rfAction = new ReflectionMethod($serviceInstance, $method);
        if (!$rfAction->isPublic()) {
            throw new RuntimeException(sprintf(
                "Unable to call non-public resource '%s' through API mode",
                $this->getRoutedPath()
            ));
        }
        if (Str::contains($rfAction->getDocComment(), '@internal')) {
            throw new RuntimeException(sprintf(
                "Unable to call internal resource '%s' through API mode",
                $this->getRoutedPath()
            ));
        }

        $invokeArgs = $this->getClassMethodArgsFromArray($serviceInstance, $method, $params);

        return $rfAction->invokeArgs($serviceInstance, $invokeArgs);
    }

    /**
     * Create a response instance from the given content
     *
     * @param mixed $content
     * @return HttpResponse
     */
    public function toResponse(mixed $content): HttpResponse
    {
        if ($content instanceof HttpResponse) {
            $response = $content;
        } else if ($content instanceof stdClass) {
            $response = $this->makeHttpResponse((array)$content);
        } else {
            $response = $this->makeHttpResponse($content);
        }

        if ($response->getStatusCode() === SymfonyResponse::HTTP_NOT_MODIFIED) {
            $response->setNotModified();
        }

        return $response;
    }

    /**
     * Provide API HTTP Response content
     *
     * @return HttpResponse
     * @throws ServiceApiException
     * @throws Throwable
     */
    public function provideApi(): HttpResponse
    {
        if ($this->getMiddleware()) {
            $response = (new Pipeline())
                ->send($this->getHttpRequest())
                ->through($this->getMiddleware())
                ->then(function () {
                    return $this->toResponse($this->callServiceApi());
                });
        } else {
            $response = $this->toResponse($this->callServiceApi());
        }

        return $response;
    }

    /**
     * Start the app
     *
     * @return void
     */
    public function run(): void
    {
        try {
            $this->provideApi()->send();
        } catch (ServiceApiException $e) {
            $httpMsg = $e->getMessage();
            $httpStatus = $e->getCode();
            $this->makeHttpResponse($httpMsg, $httpStatus)->send();
        } catch (Throwable $e) {
            if ($this->debug()) {
                $httpMsg = (string)$e;
            } else {
                $httpMsg = 'An error occurred while calling the service API';
                $this->getLogger()->error((string)$e);
            }
            $this->makeHttpResponse($httpMsg, 500)->send();
        }
    }

    /**
     * Run console application
     *
     * @param InputInterface|null $input
     * @param OutputInterface|null $output
     * @return void
     */
    public function runConsoleApp(InputInterface $input = null, OutputInterface $output = null): void
    {
        try {
            if (php_sapi_name() !== 'cli') {
                throw new RuntimeException('Not in cli mode');
            }
            $this->getConsoleApp()->setAutoExit(false);
            $exitCode = $this->getConsoleApp()->run($input, $output);
            exit($exitCode ?: 0);
        } catch (Throwable $e) {
            if ($this->debug()) {
                print "Exception: " . $e->getMessage() . "\n";
            } else {
                $this->getLogger()->error((string)$e);
            }
            exit(255);
        }
    }

    /**
     * Call the service API directly
     *
     * @param string|null $route The route string, obtained from request parameters by default
     * @param array|null $params Action parameters, obtained from request parameters by default
     * @param array|null $constructorParams Constructor parameters, obtained from request parameters by default
     * @return mixed Return the original content of the API
     * @throws ServiceApiException
     * @throws Throwable
     */
    public static function api(?string $route = null, ?array $params = null, ?array $constructorParams = null): mixed
    {
        $instance = static::instance();
        $instance->stageRoutedProperties();

        try {
            return $instance->callServiceApi($route, $params, $constructorParams);
        } catch (ServiceApiException $e) {
            throw $e;
        } catch (Throwable $e) {
            if ($instance->debug()) {
                throw $e;
            } else {
                $httpMsg = 'An error occurred while calling the service API';
                $instance->getLogger()->error((string)$e);
                throw new ServiceApiException($httpMsg);
            }
        } finally {
            // Restore the routed properties
            $instance->restoreRoutedProperties();
        }
    }
}
