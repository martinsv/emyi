<?php
/*
 * emyi
 *
 * @link http://github.com/douggr/emyi for the canonical source repository
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace Emyi\Db\Adapter;

use Emyi\Util\String;
use Emyi\Db\Column;
use Emyi\Db\Connection;

/**
 * Adapter for MySQL.
 */
class Mysql extends Connection
{
    /**
     *
     */
    static $DEFAULT_PORT = 3306;

    /**
     *
     */
    public function limit($sql, $offset, $limit)
    {
        $offset = is_null($offset) ? '' : intval($offset) . ',';
        $limit = intval($limit);
        return "$sql LIMIT {$offset}$limit";
    }

    /**
     *
     */
    public function queryForColumn($table)
    {
        return $this->query("SHOW COLUMNS FROM $table");
    }

    /**
     *
     */
    public function queryForTables()
    {
        return $this->query('SHOW TABLES');
    }

    /**
     *
     */
    public function createColumn(&$column)
    {
        $c = new Column();
        $c->inflected_name = String::phpize($column['field']);
        $c->name = $column['field'];
        $c->nullable = ($column['null']  === 'YES' ? true : false);
        $c->primary_key = ($column['key']   === 'PRI' ? true : false);
        $c->auto_increment = ($column['extra'] === 'auto_increment' ? true : false);

        if ($column['type'] == 'timestamp' || $column['type'] == 'datetime') {
            $c->raw_type = 'datetime';
            $c->length = 19;
        } elseif ($column['type'] == 'date') {
            $c->raw_type = 'date';
            $c->length = 10;
        } elseif ($column['type'] == 'time') {
            $c->raw_type = 'time';
            $c->length = 8;
        } else {
            preg_match('/^([A-Za-z0-9_]+)(\(([0-9]+(,[0-9]+)?)\))?/', $column['type'], $matches);

            $c->raw_type = (count($matches) > 0 ? $matches[1] : $column['type']);

            if (count($matches) >= 4)
                $c->length = intval($matches[3]);
        }

        $c->mapRawType();
        $c->default = $c->cast($column['default'], $this);

        return $c;
    }

    /**
     *
     */
    public function setEncoding($charset)
    {
        $params = array($charset);
        $this->query('SET NAMES ?', $params);
    }

    /**
     *
     */
    public function accepts_limit_and_order_for_update_and_delete()
    {
        return true;
    }

    /**
     *
     */
    public function native_database_types()
    {
        return [
            'primary_key'   => 'INT(11) UNSIGNED DEFAULT NULL AUTO_INCREMENT PRIMARY KEY',
            'string'        => array('name' => 'varchar', 'length' => 255),
            'text'          => array('name' => 'text'),
            'integer'       => array('name' => 'int', 'length' => 11),
            'float'         => array('name' => 'float'),
            'datetime'      => array('name' => 'datetime'),
            'timestamp'     => array('name' => 'datetime'),
            'time'          => array('name' => 'time'),
            'date'          => array('name' => 'date'),
            'binary'        => array('name' => 'blob'),
            'boolean'       => array('name' => 'tinyint', 'length' => 1)
        ];
    }
}
