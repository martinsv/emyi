<?php
/*
 * emyi
 *
 * @link http://github.com/douggr/emyi for the canonical source repository
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace Emyi\Db;

use Closure;
use Emyi\Util\String;

/**
 * Cache::get('the-cache-key', function() {
 *  # this gets executed when cache is stale
 *  return "your cacheable datas";
 * });
 *
 * @todo This module must be fully rewritten
 */
class Cache
{
    /**
     *
     */
    public static $adapter = null;

    /**
     *
     */
    public static $options = [];

    /**
     * Initializes the cache.
     *
     * With the $options array it's possible to define:
     * - expiration of the key, (time in seconds)
     * - a namespace for the key
     *
     * this last one is useful in the case two applications use
     * a shared key/store (for instance a shared Memcached db)
     *
     * Ex:
     * $cfg_ar = Emyi\Db\Config::instance();
     * $cfg_ar->setCache('memcache://localhost:11211',array('namespace' => 'my_cool_app',
     *  'expire'  => 120
     *  ));
     *
     * In the example above all the keys expire after 120 seconds, and the
     * all get a postfix 'my_cool_app'.
     *
     * (Note: expiring needs to be implemented in your cache store.)
     *
     * @param string $url URL to your cache server
     * @param array $options Specify additional options
     */
    public static function initialize($url, $options = [])
    {
        if ($url) {
            $url = parse_url($url);
            $file = String::camelize($url['scheme'], true);
            $class = "Emyi\\Db\\Cache\\$file";
            static::$adapter = new $class($url);
        } else {
            static::$adapter = null;
        }

        static::$options = array_merge(['expire' => 30, 'namespace' => ''], $options);
    }

    /**
     *
     */
    public static function flush()
    {
        if (static::$adapter) {
            static::$adapter->flush();
        }
    }

    /**
     *
     */
    public static function get($key, $closure)
    {
        $key = static::get_namespace() . $key;
        
        if (!static::$adapter) {
            return $closure();
        }

        if (!$value = static::$adapter->read($key)) {
            static::$adapter->write($key, $value = $closure(), static::$options['expire']);
        }

        return $value;
    }

    /**
     *
     */
    private static function get_namespace()
    {
        return (isset(static::$options['namespace']) && strlen(static::$options['namespace']) > 0)
            ? (static::$options['namespace'] . "::")
            : '';
    }
}
