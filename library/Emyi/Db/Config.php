<?php
/*
 * emyi
 *
 * @link http://github.com/douggr/emyi for the canonical source repository
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace Emyi\Db;

use Closure;

/**
 * Manages configuration options for ActiveRecord.
 *
 * <code>
 * ActiveRecord::initialize(function($cfg) {
 *   $cfg->set_model_home('models');
 *   $cfg->setConnections(array(
 *     'development' => 'mysql://user:pass@development.com/awesome_development',
 *     'production' => 'mysql://user:pass@production.com/awesome_production'));
 * });
 * </code>
 *
 * @todo migrate to Utils\Config
 */
class Config extends Singleton
{
    /**
     * Name of the connection to use by default.
     *
     * <code>
     * Emyi\Db\Config::initialize(function($cfg) {
     *   $cfg->setModelDirectory('/your/app/models');
     *   $cfg->setConnections(array(
     *     'development' => 'mysql://user:pass@development.com/awesome_development',
     *     'production' => 'mysql://user:pass@production.com/awesome_production'));
     * });
     * </code>
     *
     * This is a singleton class so you can retrieve the {@link Singleton} instance by doing:
     *
     * <code>
     * $config = Emyi\Db\Config::instance();
     * </code>
     * @var string
     */
    private $default_connection = 'development';

    /**
     * Contains the list of database connection strings.
     * @var array
     */
    private $connections = [];

    /**
     * Directory for the auto_loading of model classes.
     * @var string
     */
    private $model_directory;

    /**
     * Switch for logging.
     * @var bool
     */
    private $logging = false;

    /**
     * Contains a Logger object that must impelement a log() method.
     * @var object
     */
    private $logger;

    /**
     * The format to serialize DateTime values into.
     * @var string
     */
    private $date_format = \DateTime::ISO8601;

    /**
     * Allows config initialization using a closure.
     *
     * This method is just syntatic sugar.
     * <code>
     * Emyi\Db\Config::initialize(function($cfg) {
     *   $cfg->setModelDirectory('/path/to/your/model_directory');
     *   $cfg->setConnections(array(
     *     'development' => 'mysql://username:password@127.0.0.1/database_name'));
     * });
     * </code>
     *
     * You can also initialize by grabbing the singleton object:
     * <code>
     * $cfg = Emyi\Db\Config::instance();
     * $cfg->setModelDirectory('/path/to/your/model_directory');
     * $cfg->setConnections(array('development' =>
     *   'mysql://username:password@localhost/database_name'));
     * </code>
     *
     * @param Closure
     */
    public static function initialize(Closure $initializer)
    {
        $initializer(parent::instance());
    }

    /**
     * Sets the list of database connection strings.
     *
     * <code>
     * $config->setConnections(array(
     *     'development' => 'mysql://username:password@127.0.0.1/database_name'));
     * </code>
     *
     * @param array $connections Array of connections
     * @param string $default_connection Optionally specify the default_connection
     * @return void
     * @throws Emyi\Db\ConfigException
     */
    public function setConnections($connections, $default_connection=null)
    {
        if (!is_array($connections))
            throw new ConfigException("Connections must be an array");

        if ($default_connection)
            $this->setDefaultConnection($default_connection);

        $this->connections = $connections;
    }

    /**
     * Returns the connection strings array.
     *
     * @return array
     */
    public function getConnections()
    {
        return $this->connections;
    }

    /**
     * Returns a connection string if found otherwise null.
     *
     * @param string $name Name of connection to retrieve
     * @return string connection info for specified connection name
     */
    public function getConnection($name)
    {
        if (array_key_exists($name, $this->connections)) {
            return $this->connections[$name];
        }

        return null;
    }

    /**
     * Returns the default connection string or null if there is none.
     *
     * @return string
     */
    public function getDefaultConnectionString()
    {
        return array_key_exists($this->default_connection, $this->connections) ?
            $this->connections[$this->default_connection] : null;
    }

    /**
     * Returns the name of the default connection.
     *
     * @return string
     */
    public function getDefaultConnection()
    {
        return $this->default_connection;
    }

    /**
     * Set the name of the default connection.
     *
     * @param string $name Name of a connection in the connections array
     * @return void
     */
    public function setDefaultConnection($name)
    {
        $this->default_connection = $name;
    }

    /**
     * Sets the directory where models are located.
     *
     * @param string $dir Directory path containing your models
     * @return void
     */
    public function setModelDirectory($dir)
    {
        if ($this->model_directory && !file_exists($this->model_directory))
            throw new ConfigException(
                "Invalid or non-existent directory: {$this->model_directory}"
            );

        $this->model_directory = $dir;
    }

    /**
     * Returns the model directory.
     *
     * @return string
     * @throws ConfigException if specified directory was not found
     */
    public function getModelDirectory()
    {
        return $this->model_directory;
    }

    /**
     * Turn on/off logging
     *
     * @param boolean $bool
     * @return void
     */
    public function setLogging($bool)
    {
        $this->logging = $bool;
    }

    /**
     * Sets the logger object for future SQL logging
     *
     * @param object $logger
     * @return void
     * @throws ConfigException if Logger objecct does not implement public log()
     */
    public function setLogger($logger)
    {
        $class = Reflections::instance()->add($logger)->get($logger);

        if (!$class->getMethod('log') || !$class->getMethod('log')->isPublic())
            throw new ConfigException("Logger object must implement a public log method");

        $this->logger = $logger;
    }

    /**
     * Return whether or not logging is on
     *
     * @return boolean
     */
    public function getLogging()
    {
        return $this->logging;
    }

    /**
     * Returns the logger
     *
     * @return object
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Sets the url for the cache server to enable query caching.
     *
     * Only table schema queries are cached at the moment. A general query cache
     * will follow.
     *
     * Example:
     *
     * <code>
     * $config->setCache("memcached://localhost");
     * $config->setCache("memcached://localhost", array("expire" => 60));
     * </code>
     *
     * @param string $url Url to your cache server.
     * @param array $options Array of options
     */
    public function setCache($url, $options=[])
    {
        Cache::initialize($url, $options);
    }
}
