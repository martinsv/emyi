<?php
/*
 * emyi
 *
 * @link http://github.com/douggr/emyi for the canonical source repository
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace Emyi\Db;

/**
 * Singleton to manage any and all database connections.
 */
class ConnectionManager extends Singleton
{
    /**
     * Array of Emyi\Db\Connection objects.
     * @var array
     */
    static private $connections = [];

    /**
     * If $name is null then the default connection will be returned.
     *
     * @see Config
     * @param string $name Optional name of a connection
     * @return Connection
     */
    public static function getConnection($name = null)
    {
        $config = Config::instance();
        $name = $name ? $name : $config->getDefaultConnection();

        //if (!isset(self::$connections[$name]) || !self::$connections[$name]->connection)
        if (!isset(self::$connections[$name])) {
            self::$connections[$name] = Connection::instance($config->getConnection($name));
        }

        return self::$connections[$name];
    }

    /**
     * Closes the connection and drop it from the connection manager.
     *
     * @param string $name Name of the connection to forget about
     */
    public static function dropConnection($name = null)
    {
        if (isset(self::$connections[$name])) {
            unset(self::$connections[$name]);
        }
    }
}
