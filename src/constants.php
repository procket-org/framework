<?php

if (!defined('APP_BASE_PATH')) {
    /**
     * Application base path
     */
    define('APP_BASE_PATH', getcwd() . '/..');
}

if (!defined('APP_PATH')) {
    /**
     * Application path
     */
    define('APP_PATH', APP_BASE_PATH . '/app');
}

if (!defined('CONFIG_PATH')) {
    /**
     * Configuration path
     */
    define('CONFIG_PATH', APP_BASE_PATH . '/config');
}

if (!defined('DATABASE_PATH')) {
    /**
     * Database path
     */
    define('DATABASE_PATH', APP_BASE_PATH . '/database');
}

if (!defined('LANG_PATH')) {
    /**
     * Language path
     */
    define('LANG_PATH', APP_BASE_PATH . '/lang');
}

if (!defined('PUBLIC_PATH')) {
    /**
     * Public path
     */
    define('PUBLIC_PATH', APP_BASE_PATH . '/public');
}

if (!defined('STORAGE_PATH')) {
    /**
     * Storage path
     */
    define('STORAGE_PATH', APP_BASE_PATH . '/storage');
}

if (!defined('CACHE_PATH')) {
    /**
     * Cache path
     */
    define('CACHE_PATH', STORAGE_PATH . '/cache');
}

if (!defined('LOGS_PATH')) {
    /**
     * Logs path
     */
    define('LOGS_PATH', STORAGE_PATH . '/logs');
}

if (!defined('SESSIONS_PATH')) {
    /**
     * Sessions path
     */
    define('SESSIONS_PATH', STORAGE_PATH . '/sessions');
}

if (!defined('TEMPLATES_PATH')) {
    /**
     * Templates path
     */
    define('TEMPLATES_PATH', APP_BASE_PATH . '/templates');
}

if (!defined('APP_NS_PREFIX')) {
    /**
     * Application namespace prefix
     */
    define('APP_NS_PREFIX', '\\App');
}
