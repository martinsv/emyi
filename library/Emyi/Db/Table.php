<?php
/*
 * emyi
 *
 * @link http://github.com/douggr/emyi for the canonical source repository
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace Emyi\Db;

use Emyi\Util\String;
use Emyi\Util\ArrayHelper;

/**
 * Manages reading and writing to a database table.
 *
 * This class manages a database table and is used by the Model class for
 * reading and writing to its database table. There is one instance of Table
 * for every table you have a model for.
 */
class Table
{
    /**
     *
     */
    public $class;

    /**
     *
     */
    public $conn;

    /**
     *
     */
    public $primary_key;

    /**
     *
     */
    public $last_sql;

    /**
     * Name/value pairs of columns in this table
     */
    public $columns = [];

    /**
     * Name of the columns to use in selects
     * @var array
     */
    public $select_columns = [];

    /**
     * Name of the table.
     */
    public $table;

    /**
     * Name of the database (optional)
     */
    public $db_name;

    /**
     * Name of the sequence for this table (optional). Defaults to {$table}_seq
     */
    public $sequence;

    /**
     * A instance of CallBack for this model/table
     * @static
     * @var object Emyi\Db\CallBack
     */
    public $callback;

    /**
     * List of relationships for this table.
     */
    private $relationships = [];

    /**
     *
     */
    private static $cache = [];

    /**
     *
     */
    public static function load($model)
    {
        if (!isset(self::$cache[$model])) {
            // do not place set_assoc in constructor, it will lead to
            // infinite loop due to relationships requesting the model's table,
            // but the cache hasn't been set yet
            self::$cache[$model] = new Table($model);
            self::$cache[$model]->setAssociations();
        }

        return self::$cache[$model];
    }

    /**
     *
     */
    public static function clearModelCache($model = null)
    {
        if ($model && array_key_exists($model, self::$cache)) {
            unset(self::$cache[$model]);
        } else {
            self::$cache = [];
        }
    }

    /**
     *
     */
    public function __construct($class_name)
    {
        $this->class = Reflections::instance()->add($class_name)->get($class_name);

        $this->reestablish_connection(false);
        $this->set_table_name();
        $this->get_meta_data();
        $this->set_primary_key();
        $this->set_sequence_name();
        $this->setDelegates();

        $this->callback = new CallBack($class_name);
        $this->callback->register('before_save', function (Model $model) {
            $model->set_timestamps();
        }, ['prepend' => true]);

        $this->callback->register('after_save', function (Model $model) {
            $model->resetDirty();
        }, ['prepend' => true]);
    }

    /**
     *
     */
    public function reestablish_connection($close = true)
    {
        // if connection name property is null the connection manager will
        // use the default connection
        $connection = $this->class->getStaticPropertyValue('connection', null);

        if ($close) {
            ConnectionManager::dropConnection($connection);
            static::clearModelCache();
        }

        return ($this->conn = ConnectionManager::getConnection($connection));
    }

    /**
     *
     */
    public function createJoins($joins)
    {
        if (!is_array($joins)) {
            return $joins;
        }

        $self   = $this->table;
        $ret    = '';
        $space  = '';
        $tables = [];

        foreach ($joins as $value) {
            $ret .= $space;

            if (stripos($value, 'JOIN ') === false) {
                if (array_key_exists($value, $this->relationships)) {
                    $rel = $this->getRelationship($value);

                    // if there is more than 1 join for a given table we need
                    // to alias the table names
                    if (array_key_exists($rel->class_name, $tables)) {
                        $alias = $value;
                        $tables[$rel->class_name]++;
                    } else {
                        $tables[$rel->class_name] = true;
                        $alias = null;
                    }

                    $ret .= $rel->buildInnerJoin($this, false, $alias);
                } else {
                    throw new RelationshipException(
                        "Relationship `$value' has not been declared for
                         {$this->class->getName()}"
                    );
                }
            } else {
                $ret .= $value;
            }

            $space = ' ';
        }

        return $ret;
    }

    /**
     *
     */
    public function optionsToSql($options)
    {
        if (array_key_exists('from', $options)) {
            $table = $options['from'];
        } else {
            $table = $this->getQualifiedTableName();
        }

        $sql = new SQLBuilder($this->conn, $table);

        if (array_key_exists('joins', $options)) {
            $sql->joins($this->createJoins($options['joins']));

            // by default, an inner join will not fetch the fields from the
            // joined table
            if (!array_key_exists('select', $options))
                $options['select'] = $this->getQualifiedTableName() . '.*';
        }

        if (array_key_exists('select', $options)) {
            $sql->select($options['select']);
        } else {
            $sql->select(join(', ', $this->select_columns));
        }

        if (array_key_exists('conditions', $options)) {
            if (!ArrayHelper::is_hash($options['conditions'])) {
                if (is_string($options['conditions'])) {
                    $options['conditions'] = [$options['conditions']];
                }

                call_user_func_array([$sql, 'where'], $options['conditions']);
            } else {
                if (!empty($options['mapped_names'])) {
                    $options['conditions'] = $this->mapNames(
                        $options['conditions'],
                        $options['mapped_names']
                    );
                }

                $sql->where($options['conditions']);
            }
        }

        if (array_key_exists('order', $options))
            $sql->order($options['order']);

        if (array_key_exists('limit', $options))
            $sql->limit($options['limit']);

        if (array_key_exists('offset', $options))
            $sql->offset($options['offset']);

        if (array_key_exists('group', $options))
            $sql->group($options['group']);

        if (array_key_exists('having', $options))
            $sql->having($options['having']);

        return $sql;
    }

    /**
     *
     */
    public function find(array $options = [])
    {
        $opt = array_replace([
            'readonly' => false,
            'include'  => null
        ], $options);

        $sql = $this->optionsToSql($options);

        return $this->findBySql(
            $sql->toString(),
            $sql->getWhereValues(),
            $opt['readonly'],
            $opt['include']
        );
    }

    /**
     *
     */
    public function findBySql(
        $sql,
        $values = null,
        $readonly = false,
        $includes = null
    ) {
        $this->last_sql = $sql;

        $collect_attrs_for_includes = is_null($includes) ? false : true;
        $list  = [];
        $attrs = [];
        $sth   = $this->conn->query($sql, $this->processData($values));

        while ($row = $sth->fetch()) {
            $model = new $this->class->name($row, false, true, false);

            if ($readonly) {
                $model->readonly();
            }

            if ($collect_attrs_for_includes) {
                $attrs[] = $model->attributes();
            }

            $list[] = $model;
        }

        if ($collect_attrs_for_includes && !empty($list)) {
            $this->executeEagerLoader($list, $attrs, $includes);
        }

        return $list;
    }

    /**
     * Executes an eager load of a given named relationship for this table.
     *
     * @param $models array found modesl for this table
     * @param $attrs array of attrs from $models
     * @param $includes array eager load directives
     * @return void
     */
    private function executeEagerLoader($models = [], $attrs = [], $includes = [])
    {
        if (!is_array($includes)) {
            $includes = [$includes];
        }

        foreach ($includes as $index => $name) {
            // nested include
            if (is_array($name)) {
                $nested_includes = count($name) > 0 ? $name : $name[0];
                $name = $index;
            } else {
                $nested_includes = [];
            }

            $rel = $this->getRelationship($name, true);
            $rel->load_eagerly($models, $attrs, $nested_includes, $this);
        }
    }

    /**
     *
     */
    public function get_column_by_inflected_name($inflected_name)
    {
        foreach ($this->columns as $raw_name => $column) {
            if ($column->inflected_name == $inflected_name) {
                return $column;
            }
        }

        return null;
    }

    /**
     *
     */
    public function getQualifiedTableName($quote = true)
    {
        $table = $quote ? $this->conn->quoteName($this->table) : $this->table;

        if ($this->db_name) {
            $table = "{$this->conn->quoteName($this->db_name)}.{$table}";
        }

        return $table;
    }

    /**
     * Retrieve a relationship object for this table. Strict as true will throw an error
     * if the relationship name does not exist.
     *
     * @param $name string name of Relationship
     * @param $strict bool
     * @throws RelationshipException
     * @return Relationship or null
     */
    public function getRelationship($name, $strict = false)
    {
        if ($this->has_relationship($name)) {
            return $this->relationships[$name];
        }

        if ($strict) {
            throw new RelationshipException(
                "Relationship `$name' has not been declared for
                 {$this->class->getName()}"
            );
        }

        return null;
    }

    /**
     * Does a given relationship exist?
     *
     * @param $name string name of Relationship
     * @return bool
     */
    public function has_relationship($name)
    {
        return array_key_exists($name, $this->relationships);
    }

    public function insert(&$data, $primary_key=null, $sequence_name=null)
    {
        $data = $this->processData($data);

        $sql = new SQLBuilder($this->conn, $this->getQualifiedTableName());
        $sql->insert($data, $primary_key, $sequence_name);

        $values = array_values($data);
        return $this->conn->query(($this->last_sql = $sql->toString()), $values);
    }

    public function update(&$data, $where)
    {
        $data = $this->processData($data);

        $sql = new SQLBuilder($this->conn, $this->getQualifiedTableName());
        $sql->update($data)->where($where);

        $values = $sql->bindValues();
        return $this->conn->query(($this->last_sql = $sql->toString()), $values);
    }

    public function delete($data)
    {
        $data = $this->processData($data);

        $sql = new SQLBuilder($this->conn, $this->getQualifiedTableName());
        $sql->delete($data);

        $values = $sql->bindValues();
        return $this->conn->query(($this->last_sql = $sql->toString()), $values);
    }

    /**
     * Add a relationship.
     *
     * @param Relationship $relationship a Relationship object
     */
    private function addRelationship($relationship)
    {
        $this->relationships[$relationship->attribute_name] = $relationship;
    }

    /**
     *
     */
    private function get_meta_data()
    {
        // as more adapters are added probably want to do this a better way
        // than using instanceof but gud enuff for now
        $quote_name = !($this->conn instanceof Adapter\Pgsql);

        $table_name = $this->getQualifiedTableName($quote_name);
        $conn = $this->conn;
        $this->columns = Cache::get(
            "get_meta_data-$table_name", function () use ($conn, $table_name) {
                return $conn->columns($table_name);
            }
        );

        if (0 === sizeof($this->columns)) {
            $this->select_columns = "{$this->getQualifiedTableName()}.*";
        } else {
            foreach ($this->columns as $column_name => $column) {
                $this->select_columns[] = "{$this->getQualifiedTableName()}.{$column_name}";
            }
        }
    }

    /**
     * Replaces any aliases used in a hash based condition.
     *
     * @param $hash array A hash
     * @param $map array Hash of used_name => real_name
     * @return array Array with any aliases replaced with their read field name
     */
    private function mapNames(&$hash, &$map)
    {
        $ret = [];

        foreach ($hash as $name => &$value) {
            if (array_key_exists($name, $map))
                $name = $map[$name];

            $ret[$name] = $value;
        }

        return $ret;
    }

    private function &processData($hash)
    {
        if (!$hash) {
            return $hash;
        }

        foreach ($hash as $name => &$value) {
            if ($value instanceof \DateTime) {
                if (isset($this->columns[$name]) &&
                    $this->columns[$name]->type == Column::DATE)
                {
                    $hash[$name] = $this->conn->dateToString($value);
                } else {
                    $hash[$name] = $this->conn->datetimeToString($value);

                }
            } else {
                $hash[$name] = $value;
            }
        }

        return $hash;
    }

    /**
     *
     */
    private function set_primary_key()
    {
        if (($PK = $this->class->getStaticPropertyValue('pk', null)) ||
            ($PK = $this->class->getStaticPropertyValue('primary_key', null)))
        {
            $this->primary_key = is_array($PK) ? $PK : [$PK];
        } else {
            $this->primary_key = [];

            foreach ($this->columns as $c) {
                if ($c->primary_key) {
                    $this->primary_key[] = $c->inflected_name;
                }
            }
        }
    }

    /**
     *
     */
    private function set_table_name()
    {
        if (($table = $this->class->getStaticPropertyValue('table', null)) ||
            ($table = $this->class->getStaticPropertyValue('table_name', null)))
        {
            $this->table = $table;
        } else {
            $this->table = String::pluralize(
                strtolower(String::camelize($this->class->getName()))
            );

            // strip namespaces from the table name if any
            $parts = explode('\\', $this->table);
            $this->table = $parts[count($parts)-1];
        }

        if(($db = $this->class->getStaticPropertyValue('db', null)) ||
           ($db = $this->class->getStaticPropertyValue('db_name', null)))
        {
            $this->db_name = $db;
        }
    }

    /**
     *
     */
    private function set_sequence_name()
    {
        if (!$this->conn->supports_sequences()) {
            return;
        }

        if (!($this->sequence = $this->class->getStaticPropertyValue('sequence'))) {
            $this->sequence = $this->conn->getSequenceName($this->table, $this->primary_key[0]);
        }
    }

    /**
     *
     */
    private function setAssociations()
    {
        require_once 'Relationship.php';
        $namespace = $this->class->getNamespaceName();

        foreach ($this->class->getStaticProperties() as $name => $definitions) {
            if (!$definitions) {
                continue;
            }

            foreach (String::wrapIntoArray($definitions) as $definition) {
                $relationship = null;
                $definition += compact('namespace');

                switch ($name) {
                    case 'has_many':
                        $relationship = new HasMany($definition);
                        break;

                    case 'has_one':
                        $relationship = new HasOne($definition);
                        break;

                    case 'belongs_to':
                        $relationship = new BelongsTo($definition);
                        break;

                    case 'has_and_belongs_to_many':
                        $relationship = new HasAndBelongsToMany($definition);
                        break;
                }

                if ($relationship) {
                    $this->addRelationship($relationship);
                }
            }
        }
    }

    /**
     * Rebuild the delegates array into format that we can more easily work
     * with in Model.
     *
     * Will end up consisting of array of:
     * array('delegate' => ['field1', 'field2', ...],
     *       'to'       => 'delegate_to_relationship',
     *       'prefix'   => 'prefix')
     */
    private function setDelegates()
    {
        $delegates = $this->class->getStaticPropertyValue('delegate', []);
        $new = [];

        if (!array_key_exists('processed', $delegates)) {
            $delegates['processed'] = false;
        }

        if (!empty($delegates) && !$delegates['processed']) {
            foreach ($delegates as &$delegate) {
                if (!is_array($delegate) || !isset($delegate['to'])) {
                    continue;
                }

                if (!isset($delegate['prefix'])) {
                    $delegate['prefix'] = null;
                }

                $new_delegate = [
                    'to'       => $delegate['to'],
                    'prefix'   => $delegate['prefix'],
                    'delegate' => []
                ];

                foreach ($delegate as $name => $value) {
                    if (is_numeric($name)) {
                        $new_delegate['delegate'][] = $value;
                    }
                }

                $new[] = $new_delegate;
            }

            $new['processed'] = true;
            $this->class->setStaticPropertyValue('delegate', $new);
        }
    }
}
