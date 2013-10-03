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
 * Adapter for Postgres
 */
class Pgsql extends Connection
{
    /**
     *
     */
    public static $QUOTE_CHARACTER = '"';

    /**
     *
     */
    public static $DEFAULT_PORT = 5432;

    /**
     *
     */
    public function supports_sequences()
    {
        return true;
    }

    /**
     *
     */
    public function getSequenceName($table, $column_name)
    {
        return "{$table}_{$column_name}_seq";
    }

    /**
     *
     */
    public function nextSequenceValue($sequence_name)
    {
        return "nextval('" . str_replace("'", "\\'", $sequence_name) . "')";
    }

    /**
     *
     */
    public function limit($sql, $offset, $limit)
    {
        return "{$sql} LIMIT {$limit} OFFSET {$offset}";
    }

    /**
     *
     */
    public function queryForColumn($table)
    {
        if (false === strpos($table, '.')) {
            $values = [$table, $table];
            $addon = '';
        } else {
            $values = explode('.', str_replace('"', '', $table));
            $values[] = $values[1];
            $addon = ' JOIN pg_namespace AS n ON c.relnamespace = n.oid AND n.nspname = ?';
        }

        $sql = "
        SELECT
            a.attname AS field,
            a.attlen,
            REPLACE(
                pg_catalog.format_type(a.atttypid, a.atttypmod),
                'character varying',
                'varchar'
            ) AS type,
            a.attnotnull AS not_nullable,
            i.indisprimary as primary_key,
            REGEXP_REPLACE(
                REGEXP_REPLACE(
                    REGEXP_REPLACE(
                        s.column_default, '::[a-z_ ]+', ''), '''$', ''), '^''', '') AS default,
            a.attnum
        FROM
            pg_catalog.pg_attribute a
        LEFT JOIN
            pg_catalog.pg_class c ON(a.attrelid = c.oid)
        $addon
        LEFT JOIN
            pg_catalog.pg_index i ON(c.oid = i.indrelid AND a.attnum = any(i.indkey))
        LEFT JOIN
            information_schema.columns s ON(s.table_name = ? AND a.attname = s.column_name)
        WHERE
            a.attrelid = (
                SELECT
                    c.oid
                FROM
                    pg_catalog.pg_class c
                INNER JOIN
                    pg_catalog.pg_namespace n
                        ON (n.oid = c.relnamespace)
                WHERE
                    c.relname = ?
                LIMIT 1 OFFSET 0
            )
            AND a.attnum > 0
            AND NOT a.attisdropped
        ORDER BY a.attnum";

        return $this->query($sql, $values);
    }

    /**
     *
     */
    public function queryForTables()
    {
        $dml = "
        SELECT
            schemaname ||'.'|| tablename as tablename
        FROM
            pg_tables
        WHERE
            schemaname      <> 'information_schema'
            AND schemaname  <> 'pg_catalog'

        UNION

        SELECT
            schemaname ||'.'|| viewname as tablename
        FROM
            pg_views
        WHERE
            schemaname      <> 'information_schema'
            AND schemaname  <> 'pg_catalog'";

        return $this->query($dml);
    }

    /**
     *
     */
    public function createColumn(&$column)
    {
        $c = new Column();
        $c->inflected_name = String::phpize($column['field']);
        $c->name = $column['field'];
        $c->nullable = ($column['not_nullable'] ? false : true);
        $c->primary_key = ($column['primary_key'] ? true : false);
        $c->auto_increment = false;

        if (substr($column['type'], 0, 9) == 'timestamp') {
            $c->raw_type = 'datetime';
            $c->length = 19;
        } elseif ($column['type'] == 'date') {
            $c->raw_type = 'date';
            $c->length = 10;
        } else {
            preg_match('/^([A-Za-z0-9_]+)(\(([0-9]+(,[0-9]+)?)\))?/', $column['type'], $matches);

            $c->raw_type = (count($matches) > 0 ? $matches[1] : $column['type']);
            $c->length = count($matches) >= 4 ? intval($matches[3]) : intval($column['attlen']);

            if ($c->length < 0) {
                $c->length = null;
            }
        }

        $c->mapRawType();

        if ($column['default']) {
            preg_match("/^nextval\('(.*)'\)$/", $column['default'], $matches);

            if (count($matches) == 2)
                $c->sequence = $matches[1];
            else
                $c->default = $c->cast($column['default'], $this);
        }

        return $c;
    }

    /**
     *
     */
    public function setEncoding($charset)
    {
        $this->query("SET NAMES '$charset'");
    }

    /**
     *
     */
    public function native_database_types()
    {
        return array(
            'primary_key' => 'serial primary key',
            'string' => array('name' => 'character varying', 'length' => 255),
            'text' => array('name' => 'text'),
            'integer' => array('name' => 'integer'),
            'float' => array('name' => 'float'),
            'datetime' => array('name' => 'datetime'),
            'timestamp' => array('name' => 'timestamp'),
            'time' => array('name' => 'time'),
            'date' => array('name' => 'date'),
            'binary' => array('name' => 'binary'),
            'boolean' => array('name' => 'boolean')
        );
    }
}
