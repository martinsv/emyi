<?php
/*
 * emyi
 *
 * @link http://github.com/douggr/emyi for the canonical source repository
 * @license http://opensource.org/licenses/MIT MIT License
 */

if (defined('EMYI_VERSION_ID')) {
    return;
}

// Emy version
define('EMYI_VERSION_ID', '2.0.2');

// Ensure mcrypt constants are defined even if mcrypt extension is not loaded
if (!defined('MCRYPT_MODE_CBC')) {
    define('MCRYPT_MODE_CBC', 'cbc');
    define('MCRYPT_RIJNDAEL_256', 'rijndael-256');
}

// Ensure PHP session IDs only use the characters (0-9, a-f)
ini_set('session.hash_bits_per_character', 4);

// Ensure PHP session hash algorithm uses SHA-1
ini_set('session.hash_function', 1);

// this is the WEB ROOT, not the APPLICATION ROOT (EAPP_PATH)
if (!defined('EAPP_ROOT')) {
    // Try to guess the web-root
    $dirname = dirname(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file']);
    define('EAPP_ROOT', $dirname);
}

// this is the APPLICATION ROOT, not the WEB ROOT (EAPP_ROOT)
if (!defined('EAPP_PATH')) {
    // Try to guess
    $dirname = dirname(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file']);

    if (!is_dir($dirname)) {
        throw new RuntimeException(
            '`EAPP_PATH\' should be a valid directory pointing to your application\'s directory root'
        );
    }

    define('EAPP_PATH', $dirname);
}

// le error handler
set_exception_handler(
    function (Exception $e) {
        header_remove();

        if (file_exists($error_file = EAPP_PATH . "/errors/{$e->getCode()}.html")) {
            $error_code = $e->getCode();
        } else {
            $error_file = EAPP_PATH . '/errors/500.html';
            $error_code = 500;
        }

        http_response_code($error_code);
        header('Content-Type: text/html; charset=utf-8');

        include $error_file;
        exit;
    }
);
// !set_exception_handler */

if (!file_exists($loader = __DIR__ . '/../vendor/autoload.php') &&
    !file_exists($loader = __DIR__ . '/../../../autoload.php'))
{
    echo <<<ERR
You must set up the project dependencies, run the following commands:
    \$ curl -sS https://getcomposer.org/installer | php
    \$ php composer.phar install
ERR;

    exit(1);
}

// Ensure calls to a date/time function do not generate a E_NOTICE 
date_default_timezone_set(Emyi\Util\Config::get('application/timezone'));
