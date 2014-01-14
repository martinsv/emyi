<?php
/*
 * emyi
 *
 * @link http://github.com/douggr/emyi for the canonical source repository
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace Emyi\Util;

use ArrayObject;

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
    private static $is_read = false;

    /**
     * config defaults
     * @var array
     */
    private static $defaults = [
        'application' => [
            'author'        => 'Emyi',
            'name'          => '',
            'maintenance'   => false,
            'environment'   => 'development',
            'timezone'      => 'UTC',

            // Language code for this installation. All choices can be found here:
            // http://www.i18nguy.com/unicode/language-identifiers.html
            'language_code' => 'en-us',
            'version'       => '1.0.0',
            'hash'          => '',
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
    public static function get($index)
    {
        $data  = self::readConfigFile();
        $index = explode('/', $index);
        $count = count($index);

        if (!$data->offsetExists($index[0])) {
            return null;
        }

        $value = $data->offsetGet($index[0]);

        switch ($count) {
            case 2:
                if ('*' === $index[1]) {
                    $return = [];

                    foreach ($data as $idx => $params) {
                        if (preg_match("@^{$index[0]}+@", $idx)) {
                            $return[$idx] = $params;
                        }
                    }

                    return $return;
                } else {
                    return $value[$index[1]];
                }

            case 3:
                return $value[$index[1]][$index[2]];

            default:
                return $value;
        }
    }

    /**
     *
     */
    public static function getAll()
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
            if (file_exists($file = EAPP_PATH . self::INI_FILE)) {
                $properties = parse_ini_file($file, true);
            } elseif (file_exists($file = EAPP_PATH . self::PHP_FILE)) {
                $properties = (array) require_once $file;
            } else {
                $properties = [];
            }

            self::$is_read = true;
            self::$defaults = new ArrayObject(
                array_replace_recursive(self::$defaults, $properties)
            );
        }

        return self::$defaults;
    }
}
