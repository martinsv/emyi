<?php
/*
 * emyi
 *
 * @link http://github.com/douggr/emyi for the canonical source repository
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace Emyi\Db;


/**
 * MVC abstract Model
 */
abstract class Model
{
    /**
     * The table class to use
     * @var string
     */
    protected $table_class = 'Emyi\\Db\\Table';

    /**
     * Set to the name of the connection this Model should use.
     * @var string
     */
    protected $connection;

    /**
     * Allow the programmer to switch between connections using the same
     * model
     *
     * @param string The name of the connection this Model should use.
     * @return self
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Returns the name of the connection this Model is using.
     *
     * @return string
     */
    public function getConnection()
    {
        return $this->connection;
    }
}
