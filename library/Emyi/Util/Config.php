<?php
/*
 * emyi
 *
 * @link http://github.com/douggr/emyi for the canonical source repository
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace Emyi\Util;

use ArrayObject;
use Emyi\Db;

/**
 * Main config class
 */
class Config
{
    /**
     *
     */
    const INI_FILE = '/etc/application.ini';

    /**
     *
     */
    const PHP_FILE = '/etc/application.php';

    /**
     *
     */
    static private $is_read = false;

    /**
     * config defaults
     * @var array
     */
    static private $defaults = [
        'application' => [
            'author'        => 'Emyi',
            'name'          => '',
            'maintenance'   => false,
            'environment'   => 'development',
            'timezone'      => 'UTC',

            // Language code for this installation. All choices can be found here:
            // http://www.i18nguy.com/unicode/language-identifiers.html
            'language_code' => 'pt-br',
            'version'       => '1.0.0',
            'hash'          => '91e95be6b6634e3c21072dfcd661146728694326',
            'build'         => ''
        ]
        // -application

        , 'encrypt' => [
            'salt'          => 'da39a3ee5e6b4b0d3255bfef95601890afd80709',
            'algorithm'     => MCRYPT_RIJNDAEL_256,
            'mode'          => MCRYPT_MODE_ECB,
        ]
        // -encrypt

        , 'auth' => [
            'enabled'       => false,
        ]
        // -auth

        // -database

        , 'view' => [
            'engine'        => '\\Emyi\\Mvc\\View',
        ]
        // -view

        , 'logging' => [
            'enabled'       => false,
        ]
        // -logging

        , 'mail' => [
            'mta'           => 'sendmail',
            'admin'         => '',
            'username'      => '',
            'password'      => '',
            'port'          => '',
            'from'          => '',
        ]
        // -mail

        , 'cookie' => [
        // :)
        ]
        // -cookie

        , 'memcache' => [
            'enabled'       => false,
            'host'          => 'localhost',
            'port'          => 11211,
            'namespace'     => 'emy',
            'expire'        => 36000,
        ]
        // -memcache
    ];

    /**
     * Returns the value at the specified index null if index does not exists
     *
     * @param string The index with the value.
     * @return The value at the specified index or null
     */
    static public function get($index)
    {
        $index = explode('/', $index);

        switch (count($index)) {
            case 2:
                if ('*' === $index[1]) {
                    $return = [];

                    foreach (self::getAll() as $idx => $params) {
                        if (preg_match('@^{$index[0]}+@', $idx)) {
                            $return[$idx] = $params;
                        }
                    }

                    return $return;
                } else {
                    return self::readConfigFile()
                        ->offsetGet($index[0])[$index[1]];
                }
            case 3:
                return self::readConfigFile()
                    ->offsetGet($index[0])[$index[1]][$index[2]];

            default:
                return self::readConfigFile()
                    ->offsetGet($index[0]);
        }
    }

    /**
     *
     */
    static public function getAll()
    {
        return self::readConfigFile();
    }

    //-------------------------------------------------------------- protected
    /**
     *
     */
    static protected function readConfigFile()
    {
        if (!self::$is_read) {
            if (file_exists($file = EAPP_PATH . self::PHP_FILE)) {
                $properties = (array) require_once $file;
            } elseif (file_exists($file = EAPP_PATH . self::INI_FILE)) {
                $properties = parse_ini_file($file, true);
            } else {
                $properties = [];
            }

            self::$is_read = true;
            self::$defaults = new ArrayObject(
                array_replace_recursive(self::$defaults, $properties)
            );

            $connections = [];
            foreach (static::get('database/*') as $name => $conn) {
                if ('' === $conn['name'] && '' === $conn['host']) {
                    continue;
                }

                $name = substr($name, 9);

                if (array_key_exists('dsn', $conn) && '' !== $conn['dsn']) {
                    $connections[$name] = $conn['dsn'];
                } else {
                    /*!@
                    $connections[$name] = http_build_url('a://b', [
                        'scheme'    => $conn['engine'],
                        'username'  => $conn['username'],
                        'password'  => $conn['password'],
                        'host'      => $conn['host'],
                        'path'      => $conn['name'],
                    ]);
                    // */
                    continue;
                }
            }

            Db\Config::initialize(function ($cfg) use ($connections) {
              $cfg->setConnections($connections);
            });
        }

        return self::$defaults;
    }
}
