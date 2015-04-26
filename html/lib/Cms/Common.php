<?php
/**
 * kkCms: Content Management System
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are PROHIBITED without prior written permission from
 * the author. If you received this code occasionally and without
 * intent to use it, please report this incident to the author.
 *
 * @category Data
 * @package  Library
 * @author   Kanstantsin A Kamkou (2ka.by)
 * @license  http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode
 * @version  $Id$
 */

// who we are?
define('CMS_COMMON', true);

// debug mode
define('CMS_DEBUG', @getenv('APPLICATION_ENV') == 'development');

// Cli or not to cli
if (defined('CMS_CLI')) {
    // current machine's host
    define('CMS_HOST', php_uname('n'));

    // set https
    define('CMS_HTTPS', false);
} else {
    // set cli mode to false
    define('CMS_CLI', false);

    // REMOTE_ADDR and HTTP_HOST check
    if (!isset($_SERVER['REMOTE_ADDR']) || !isset($_SERVER['HTTP_HOST'])) {
        throw new Exception(
            "System can't find an environment variable: REMOTE_ADDR or HTTP_HOST"
        );
    }

    // Timelimit
    if (ini_get('max_execution_time') < 30) {
        @set_time_limit(30);
    }

    // http(s)://domain.com
    define('CMS_HTTPS', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off');
    define('CMS_HOST', 'http' . (CMS_HTTPS ? 's' : '') . "://{$_SERVER['HTTP_HOST']}");
}

// Debug timer
if (CMS_DEBUG) {
    $_ENV['CMS_TIME_START'] = microtime(true);
}

// Define set
$path = substr(dirname(__FILE__), 0, -8);
define('CMS_SEP', DIRECTORY_SEPARATOR);
define('CMS_ROOT', $path . CMS_SEP);
define('CMS_HTDOCS', CMS_ROOT . 'htdocs/');
define('CMS_TMP', CMS_ROOT . 'tmp/');
define('CMS_UPL', CMS_TMP . 'uploads/');
unset($path);

// Errors handling
error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE);

ini_set('display_errors', CMS_DEBUG);
ini_set('error_log', CMS_TMP . 'logs/errors.log');
ini_set('log_errors', true);
ini_set('ignore_repeated_errors', true);
ini_set('ignore_repeated_source', true);
ini_set('error_prepend_string', '<pre>');
ini_set('error_append_string', '</pre>');

// Default timezone
if (!ini_get('date.timezone')) {
    ini_set('date.timezone', 'Europe/Minsk');
}

// include paths
ini_set('include_path', CMS_ROOT . 'lib' . PATH_SEPARATOR . ini_get('include_path'));

// Zend Autoloader
if (!is_readable(CMS_ROOT . 'lib/Zend/Loader/Autoloader.php')) {
    throw new Exception('System requires "Zend Framework"');
}

require CMS_ROOT . 'lib/Zend/Loader/Autoloader.php';


// Namespace of the engine
Zend_Loader_Autoloader::getInstance()->registerNamespace(array('Cms_', 'Debug_'));

// Utf8 support
require CMS_ROOT . 'lib/ThirdParty/Utf8/ReflectionTypehint.php';
if (version_compare(PHP_VERSION, '5.3') === -1) {
    require CMS_ROOT . 'lib/ThirdParty/Utf8/Utf8_52.php';
} else {
    require CMS_ROOT . 'lib/ThirdParty/Utf8/Utf8.php';
}


// Default locale to use
Zend_Registry::set('Zend_Locale', new Cms_Locale());
