<?php
/*
 * emyi
 *
 * @link http://github.com/douggr/emyi for the canonical source repository
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace Emyi\Db;

use OutOfBoundsException;

/**
 * Templating like class for building SQL statements.
 *
 * Examples:
 * 'name = :name AND author = :author'
 * 'id = IN (:ids)'
 * 'id IN (:subselect)'
 */
class Expressions
{
    ///
    const PARAMETER_MARK = '?';

    /**
     *
     */
    private $expressions;

    /**
     *
     */
    private $values = [];

    /**
     *
     */
    private $connection;

    /**
     *
     */
    public function __construct($connection, $expressions = null /* [, $values ... ] */)
    {
        $values = null;
        $this->connection = $connection;

        if (is_array($expressions)) {
            $glue = func_num_args() > 2 ? func_get_arg(2) : ' AND ';
            list($expressions, $values) = $this->buildSqlFromHash($expressions, $glue);
        }

        if ($expressions != '') {
            if (!$values) {
                $values = array_slice(func_get_args(), 2);
            }

            $this->values = $values;
            $this->expressions = $expressions;
        }
    }

    /**
     * Bind a value to the specific one based index. There must be a bind marker
     * for each value bound or toString() will throw an exception.
     */
    public function bind($parameter, $value)
    {
        if ($parameter <= 0) {
            throw new OutOfBoundsException("Invalid parameter index: $parameter");
        }

        $this->values[$parameter - 1] = $value;
    }

    /**
     *
     */
    public function bindValues($values)
    {
        $this->values = $values;
    }

    /**
     * Returns all the values currently bound.
     */
    public function values()
    {
        return $this->values;
    }

    /**
     * Returns the connection object.
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Sets the connection object. It is highly recommended to set this so we can
     * use the adapter's native escaping mechanism.
     *
     * @param string $connection a Connection instance
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;
    }

    /**
     *
     */
    public function toString($substitute = false, &$options = null)
    {
        if (!$options) {
            $options = [];
        }

        $values = array_key_exists('values', $options) ? $options['values'] : $this->values;

        $ret        = "";
        $replace    = [];
        $num_values = count($values);
        $len        = strlen($this->expressions);
        $quotes     = 0;

        for ($i = 0, $j = 0; $i < $len; ++$i) {
            $ch = $this->expressions[$i];

            if ($ch == self::PARAMETER_MARK) {
                if ($quotes % 2 == 0) {
                    if ($j > $num_values-1) {
                        throw new OutOfBoundsException(
                            "No bound parameter for index $j"
                        );
                    }

                    $ch = $this->substitute($values, $substitute, $i, $j++);
                }
            } elseif ($ch == '\'' && $i > 0 && $this->expressions[$i-1] != '\\') {
                ++$quotes;
            }

            $ret .= $ch;
        }

        return $ret;
    }

    private function buildSqlFromHash(&$hash, $glue)
    {
        $sql = $g = "";

        foreach ($hash as $name => $value) {
            if ($this->connection) {
                $name = $this->connection->quoteName($name);
            }

            if (is_array($value)) {
                // @TODO change to use OR instead of IN
                $sql .= "$g$name IN (?)";
            } elseif (is_null($value)) {
                $sql .= "$g$name IS ?";
            } else {
                $sql .= "$g$name=?";
            }

            $g = $glue;
        }

        return [$sql, array_values($hash)];
    }

    /**
     *
     */
    private function substitute(&$values, $substitute, $pos, $parameter_index)
    {
        $value = $values[$parameter_index];

        if (is_array($value)) {
            $count = count($value);

            if ($substitute) {
                $ret = '';

                for ($i = 0, $n = $count; $i < $n; ++$i) {
                    $ret .= ($i > 0 ? ', ' : '') . $this->stringify($value[$i]);
                }

                return $ret;
            }
    
            return join(', ', array_fill(0, $count, self::PARAMETER_MARK));
        }

        if ($substitute) {
            return $this->stringify($value);
        }

        return $this->expressions[$pos];
    }

    /**
     *
     */
    private function stringify($value)
    {
        if (is_null($value)) {
            return "NULL";
        }

        return is_string($value) ? $this->quote($value) : $value;
    }

    /**
     *
     */
    private function quote($value)
    {
        if ($this->connection) {
            return $this->connection->escape($value);
        }

        return "'" . str_replace("'", "''", $value) . "'";
    }
}
