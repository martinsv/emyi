<?php
/*
 * emyi
 *
 * @link http://github.com/douggr/emyi for the canonical source repository
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace Emyi\Db;

use Emyi\Db\Table;
use Emyi\Db\Model;
use Emyi\Db\SQLBuilder;
use Emyi\Util\String;
use Emyi\Util\ArrayHelper;

/**
 * Interface for a table relationship.
 */
interface InterfaceRelationship
{
    /**
     *
     */
    public function __construct($options = []);

    /**
     *
     */
    public function buildAssociation(
        Model $model,
        $attributes = [],
        $guard_attributes = true
    );

    /**
     *
     */
    public function createAssociation(
        Model $model,
        $attributes = [],
        $guard_attributes = true
    );

    /**
     *
     */
    public function load(Model $model);

}

/**
 * Abstract class that all relationships must extend from.
 */
abstract class AbstractRelationship implements InterfaceRelationship
{
    /**
     * Name to be used that will trigger call to the relationship.
     *
     * @var string
     */
    public $attribute_name;

    /**
     * Class name of the associated model.
     *
     * @var string
     */
    public $class_name;

    /**
     * Name of the foreign key.
     *
     * @var string
     */
    public $foreign_key = [];

    /**
     * Options of the relationship.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Is the relationship single or multi.
     *
     * @var boolean
     */
    protected $poly_relationship = false;

    /**
     * List of valid options for relationships.
     *
     * @var array
     */
    static protected $valid_association_options = [
        'class_name',
        'class',
        'foreign_key',
        'conditions',
        'select',
        'readonly',
        'namespace'
    ];

    /**
     *
     */
    public static function add_condition(
        &$conditions = [],
        $condition,
        $conjuction = ' AND '
    ) {
        if (is_array($condition)) {
            if (empty($conditions)) {
                $conditions = ArrayHelper::flatten($condition);
            } else {
                $conditions[0] .= " $conjuction " . array_shift($condition);
                $conditions[] = ArrayHelper::flatten($condition);
            }
        } elseif (is_string($condition)) {
            $conditions[0] .= " $conjuction $condition";
        }

        return $conditions;
    }

    /**
     * Constructs a relationship.
     *
     * @param array $options Options for the relationship (see {@link valid_association_options})
     * @return mixed
     */
    public function __construct($options=[])
    {
        $this->attribute_name = $options[0];
        $this->options = $this->merge_association_options($options);

        $relationship = strtolower(String::className(get_called_class()));

        if (preg_match('"Many$"', get_called_class()))
            $this->poly_relationship = true;

        if (isset($this->options['conditions']) && !is_array($this->options['conditions']))
            $this->options['conditions'] = array($this->options['conditions']);

        if (isset($this->options['class']))
            $this->set_class_name($this->options['class']);
        elseif (isset($this->options['class_name']))
            $this->set_class_name($this->options['class_name']);

        $this->attribute_name = String::phpize($this->attribute_name);

        if (!$this->foreign_key && isset($this->options['foreign_key']))
            $this->foreign_key = is_array($this->options['foreign_key']) ? $this->options['foreign_key'] : array($this->options['foreign_key']);
    }

    protected function getTable()
    {
        return Table::load($this->class_name);
    }

    /**
     * What is this relationship's cardinality?
     *
     * @return bool
     */
    public function is_poly()
    {
        return $this->poly_relationship;
    }

    /**
     *
     */
    protected static function getKeyName($class)
    {
        return strtolower(String::phpize(String::className($class))) . '_id';
    }

    /**
     * Eagerly loads relationships for $models.
     *
     * This method takes an array of models, collects PRIMARY_KEY or FK
     * (whichever is needed for relationship), then queries the related table
     * by PRIMARY_KEY/FK and attaches the array of returned relationships to
     * the appropriately named relationship on $models.
     *
     * @param Table $table
     * @param $models array of model objects
     * @param $attributes array of attributes from $models
     * @param $includes array of eager load directives
     * @param $query_keys key(s) to be queried for on included/related table
     * @param $model_values_keys key(s)/value(s) to be used in query from model
     *      which is including
     */
    protected function query_and_attach_related_models_eagerly(
        Table $table,
        $models,
        $attributes,
        $includes = [],
        $query_keys = [],
        $model_values_keys = [])
    {
        $values = [];
        $options = $this->options;
        $query_key = $query_keys[0];
        $model_values_key = $model_values_keys[0];

        foreach ($attributes as $column => $value)
            $values[] = $value[String::phpize($model_values_key)];

        $values = array($values);
        $conditions = SQLBuilder::create_conditions_from_underscored_string(
            $table->conn,
            $query_key,
            $values
        );

        if (isset($options['conditions']) && strlen($options['conditions'][0]) > 1)
            static::add_condition($options['conditions'], $conditions);
        else
            $options['conditions'] = $conditions;

        if (!empty($includes))
            $options['include'] = $includes;

        if (!empty($options['through'])) {
            // save old keys as we will be reseting them below for inner
            // join convenience
            $primary_key = $this->primary_key;
            $fk = $this->foreign_key;

            $this->set_keys($this->getTable()->class->getName(), true);

            if (!isset($options['class_name'])) {
                $class = String::classify($options['through'], true);
                if (isset($this->options['namespace']) && !class_exists($class))
                    $class = $this->options['namespace'].'\\'.$class;

                $through_table = $class::table();
            } else {
                $class = $options['class_name'];
                $relation = $class::table()->getRelationship($options['through']);
                $through_table = $relation->getTable();
            }

            $options['joins'] = $this->buildInnerJoin($through_table, true);
            $query_key = $this->primary_key[0];

            // reset keys
            $this->primary_key = $primary_key;
            $this->foreign_key = $fk;
        }

        $options = $this->unset_non_finder_options($options);

        $class = $this->class_name;

        $related_models = $class::find('all', $options);
        $used_models = [];
        $model_values_key = String::phpize($model_values_key);
        $query_key = String::phpize($query_key);

        foreach ($models as $model) {
            $matches = 0;
            $key_to_match = $model->$model_values_key;

            foreach ($related_models as $related) {
                if ($related->$query_key == $key_to_match) {
                    $hash = spl_object_hash($related);

                    if (in_array($hash, $used_models))
                        $model->set_relationship_from_eager_load(clone($related), $this->attribute_name);
                    else
                        $model->set_relationship_from_eager_load($related, $this->attribute_name);

                    $used_models[] = $hash;
                    $matches++;
                }
            }

            if (0 === $matches)
                $model->set_relationship_from_eager_load(null, $this->attribute_name);
        }
    }

    /**
     * Creates a new instance of specified {@link Model} with the attributes pre-loaded.
     *
     * @param Model $model The model which holds this association
     * @param array $attributes Hash containing attributes to initialize the model with
     * @return Model
     */
    public function buildAssociation(Model $model, $attributes=[], $guard_attributes=true)
    {
        $class_name = $this->class_name;
        return new $class_name($attributes, $guard_attributes);
    }

    /**
     * Creates a new instance of {@link Model} and invokes save.
     *
     * @param Model $model The model which holds this association
     * @param array $attributes Hash containing attributes to initialize the model with
     * @return Model
     */
    public function createAssociation(Model $model, $attributes=[], $guard_attributes=true)
    {
        $class_name = $this->class_name;
        $new_record = $class_name::create($attributes, true, $guard_attributes);
        return $this->append_record_to_associate($model, $new_record);
    }

    protected function append_record_to_associate(Model $associate, Model $record)
    {
        $association =& $associate->{$this->attribute_name};

        if ($this->poly_relationship)
            $association[] = $record;
        else
            $association = $record;

        return $record;
    }

    protected function merge_association_options($options)
    {
        $available_options = array_merge(self::$valid_association_options, static::$valid_association_options);
        $valid_options = array_intersect_key(array_flip($available_options), $options);

        foreach ($valid_options as $option => $v)
            $valid_options[$option] = $options[$option];

        return $valid_options;
    }

    protected function unset_non_finder_options($options)
    {
        foreach (array_keys($options) as $option) {
            if (!in_array($option, Model::$VALID_OPTIONS))
                unset($options[$option]);
        }

        return $options;
    }

    /**
     * Infers the $this->class_name based on $this->attribute_name.
     *
     * Will try to guess the appropriate class by singularizing and uppercasing $this->attribute_name.
     *
     * @return void
     * @see attribute_name
     */
    protected function set_inferred_class_name()
    {
        $this->set_class_name(
            String::classify($this->attribute_name, $this instanceOf HasMany)
        );
    }

    protected function set_class_name($class_name)
    {
        if (strpos($class_name, '\\') > 0 && isset($this->options['namespace'])) {
            $class_name = "\\{$this->options['namespace']}\\{$class_name}";
        }
        
        $reflection = Reflections::instance()->add($class_name)->get($class_name);

        if (!$reflection->isSubClassOf('Emyi\\Db\\Model'))
            throw new RelationshipException("'$class_name' must extend from Emyi\\Db\\Model");

        $this->class_name = $class_name;
    }

    protected function create_conditions_from_keys(
        Model $model,
        $condition_keys = [],
        $value_keys = []
    ) {
        $condition_string = implode('_and_', $condition_keys);
        $condition_values = array_values($model->get_values_for($value_keys));

        // return null if all the foreign key values are null so that we
        // don't try to do a query like "id is null"
        foreach ($condition_values as $value) {
            if ($value === null) {
                return null;
            }
        }

        $conditions = SQLBuilder::create_conditions_from_underscored_string(
            Table::load(get_class($model))->conn,
            $condition_string,
            $condition_values
        );

        // DO NOT CHANGE THE NEXT TWO LINES
        // add_condition operates on a reference and will screw the
        // options array up
        if (isset($this->options['conditions'])) {
            $options_conditions = $this->options['conditions'];
        } else {
            $options_conditions = [];
        }

        return static::add_condition($options_conditions, $conditions);
    }

    /**
     * Creates INNER JOIN SQL for associations.
     *
     * @param Table $from_table the table used for the FROM SQL statement
     * @param bool $using_through is this a THROUGH relationship?
     * @param string $alias a table alias for when a table is being joined twice
     * @return string SQL INNER JOIN fragment
     */
    public function buildInnerJoin(Table $from_table, $using_through=false, $alias=null)
    {
        if ($using_through) {
            $join_table = $from_table;
            $join_table_name = $from_table->getQualifiedTableName();
            $from_table_name = Table::load($this->class_name)->getQualifiedTableName();
        } else {
            $join_table = Table::load($this->class_name);
            $join_table_name = $join_table->getQualifiedTableName();
            $from_table_name = $from_table->getQualifiedTableName();
        }

        // need to flip the logic when the key is on the other table
        if ($this instanceof HasMany || $this instanceof HasOne)
        {
            $this->set_keys($from_table->class->getName());

            if ($using_through)
            {
                $foreign_key = $this->primary_key[0];
                $join_primary_key = $this->foreign_key[0];
            }
            else
            {
                $join_primary_key = $this->foreign_key[0];
                $foreign_key = $this->primary_key[0];
            }
        }
        else
        {
            $foreign_key = $this->foreign_key[0];
            $join_primary_key = $this->primary_key[0];
        }

        if (!is_null($alias))
        {
            $aliased_join_table_name = $alias = $this->getTable()->conn->quoteName($alias);
            $alias .= ' ';
        }
        else
            $aliased_join_table_name = $join_table_name;

        return "INNER JOIN $join_table_name {$alias} ON($from_table_name.$foreign_key = $aliased_join_table_name.$join_primary_key)";
    }

    /**
     * This will load the related model data.
     *
     * @param Model $model The model this relationship belongs to
     */
    abstract function load(Model $model);
}

/**
 * One-to-many relationship.
 *
 * <code>
 * # Table: people
 * # Primary key: id
 * # Foreign key: school_id
 * class Person extends Emyi\Db\Model {}
 *
 * # Table: schools
 * # Primary key: id
 * class School extends Emyi\Db\Model {
 *   static $has_many = array(
 *     array('people')
 *   );
 * });
 * </code>
 *
 * Example using options:
 *
 * <code>
 * class Payment extends Emyi\Db\Model {
 *   static $belongs_to = array(
 *     array('person'),
 *     array('order')
 *   );
 * }
 *
 * class Order extends Emyi\Db\Model {
 *   static $has_many = array(
 *     array('people',
 *           'through'    => 'payments',
 *           'select'     => 'people.*, payments.amount',
 *           'conditions' => 'payments.amount < 200')
 *     );
 * }
 * </code>
 */
class HasMany extends AbstractRelationship
{
    /**
     * Valid options to use for a {@link HasMany} relationship.
     *
     * <ul>
     * <li><b>limit/offset:</b> limit the number of records</li>
     * <li><b>primary_key:</b> name of the primary_key of the association (defaults to "id")</li>
     * <li><b>group:</b> GROUP BY clause</li>
     * <li><b>order:</b> ORDER BY clause</li>
     * <li><b>through:</b> name of a model</li>
     * </ul>
     *
     * @var array
     */
    static protected $valid_association_options = [
        'primary_key',
        'order',
        'group',
        'having',
        'limit',
        'offset',
        'through',
        'source'
    ];

    /**
     *
     */
    protected $primary_key;

    /**
     *
     */
    private $has_one = false;

    /**
     *
     */
    private $through;

    /**
     * Constructs a Emyi\Db\Association\HasMany relationship.
     *
     * @param array $options Options for the association
     * @return Emyi\Db\Association\HasMany
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        if (isset($this->options['through'])) {
            $this->through = $this->options['through'];

            if (isset($this->options['source'])) {
                $this->set_class_name($this->options['source']);
            }
        }

        if (!$this->primary_key && isset($this->options['primary_key'])) {
            $this->primary_key = is_array($this->options['primary_key'])
                ? $this->options['primary_key']
                : [$this->options['primary_key']];
        }

        if (!$this->class_name) {
            $this->set_inferred_class_name();
        }
    }

    protected function set_keys($model_class_name, $override = false)
    {
        //infer from class_name
        if (!$this->foreign_key || $override) {
            $this->foreign_key = [static::getKeyName($model_class_name)];
        }

        if (!$this->primary_key || $override) {
            $this->primary_key = Table::load($model_class_name)->primary_key;
        }
    }

    public function load(Model $model)
    {
        $class_name = $this->class_name;
        $this->set_keys(get_class($model));

        // since through relationships depend on other relationships we can't do
        // this initiailization in the constructor since the other relationship
        // may not have been created yet and we only want this to run once
        if (!isset($this->initialized)) {
            if ($this->through) {
                // verify through is a belongs_to or has_many for access of keys
                if (!($through_relationship = $this->getTable()->getRelationship($this->through)))
                    throw new HasManyThroughAssociationException("Could not find the association $this->through in model " . get_class($model));

                if (!($through_relationship instanceof HasMany) && !($through_relationship instanceof BelongsTo))
                    throw new HasManyThroughAssociationException('has_many through can only use a belongs_to or has_many association');

                // save old keys as we will be reseting them below for inner join convenience
                $primary_key = $this->primary_key;
                $fk = $this->foreign_key;

                $this->set_keys($this->getTable()->class->getName(), true);
                
                $class = $this->class_name;
                $relation = $class::table()->getRelationship($this->through);
                $through_table = $relation->getTable();
                $this->options['joins'] = $this->buildInnerJoin($through_table, true);

                // reset keys
                $this->primary_key = $primary_key;
                $this->foreign_key = $fk;
            }

            $this->initialized = true;
        }

        if (!($conditions = $this->create_conditions_from_keys($model, $this->foreign_key, $this->primary_key)))
            return null;

        $options = $this->unset_non_finder_options($this->options);
        $options['conditions'] = $conditions;
        return $class_name::find($this->poly_relationship ? 'all' : 'first', $options);
    }

    /**
     * Get an array containing the key and value of the foreign key for the
     * association
     *
     * @param Model $model
     * @return array
     */
    private function get_foreign_key_for_new_association(Model $model)
    {
        $this->set_keys($model);
        $primary_key = String::phpize($this->foreign_key[0]);

        return array(
            $primary_key => $model->id,
        );
    }

    /**
     *
     */
    private function inject_foreign_key_for_new_association(Model $model, &$attributes)
    {
        $primary_key = $this->get_foreign_key_for_new_association($model);

        if (!isset($attributes[key($primary_key)]))
            $attributes[key($primary_key)] = current($primary_key);

        return $attributes;
    }

    /**
     *
     */
    public function buildAssociation(Model $model, $attributes=[], $guard_attributes=true)
    {
        $relationship_attributes = $this->get_foreign_key_for_new_association($model);

        if ($guard_attributes) {
            // First build the record with just our relationship attributes (unguarded)
            $record = parent::buildAssociation($model, $relationship_attributes, false);

            // Then, set our normal attributes (using guarding)
            $record->set_attributes($attributes);
        } else {
            // Merge our attributes
            $attributes = array_merge($relationship_attributes, $attributes);

            // First build the record with just our relationship attributes (unguarded)
            $record = parent::buildAssociation($model, $attributes, $guard_attributes);
        }

        return $record;
    }

    /**
     *
     */
    public function createAssociation(Model $model, $attributes=[], $guard_attributes=true)
    {
        $relationship_attributes = $this->get_foreign_key_for_new_association($model);

        if ($guard_attributes) {
            // First build the record with just our relationship attributes (unguarded)
            $record = parent::buildAssociation($model, $relationship_attributes, false);

            // Then, set our normal attributes (using guarding)
            $record->set_attributes($attributes);

            // Save our model, as a "create" instantly saves after building
            $record->save();
        } else {
            // Merge our attributes
            $attributes = array_merge($relationship_attributes, $attributes);

            // First build the record with just our relationship attributes (unguarded)
            $record = parent::createAssociation($model, $attributes, $guard_attributes);
        }

        return $record;
    }

    /**
     *
     */
    public function load_eagerly($models=[], $attributes=[], $includes, Table $table)
    {
        $this->set_keys($table->class->name);
        $this->query_and_attach_related_models_eagerly($table, $models, $attributes, $includes, $this->foreign_key, $table->primary_key);
    }
}

/**
 * One-to-one relationship.
 *
 * <code>
 * # Table name: states
 * # Primary key: id
 * class State extends Emyi\Db\Model {}
 *
 * # Table name: people
 * # Foreign key: state_id
 * class Person extends Emyi\Db\Model {
 *   static $has_one = array(array('state'));
 * }
 * </code>
 *
 */
class HasOne extends HasMany
{
}

/**
 * @todo implement
 */
class HasAndBelongsToMany extends AbstractRelationship
{
    public function __construct($options = [])
    {
        /* options =>
         *   join_table - name of the join table if not in lexical order
         *   foreign_key -
         *   association_foreign_key - default is {assoc_class}_id
         *   uniq - if true duplicate assoc objects will be ignored
         *   validate
         */
    }

    public function load(Model $model)
    {

    }
}

/**
 * Belongs to relationship.
 *
 * <code>
 * class School extends Emyi\Db\Model {}
 *
 * class Person extends Emyi\Db\Model {
 *   static $belongs_to = array(
 *     array('school')
 *   );
 * }
 * </code>
 *
 * Example using options:
 *
 * <code>
 * class School extends Emyi\Db\Model {}
 *
 * class Person extends Emyi\Db\Model {
 *   static $belongs_to = array(
 *     array('school', 'primary_key' => 'school_id')
 *   );
 * }
 * </code>
 *
 */
class BelongsTo extends AbstractRelationship
{
    public function __construct($options=[])
    {
        parent::__construct($options);

        if (!$this->class_name)
            $this->set_inferred_class_name();

        //infer from class_name
        if (!$this->foreign_key)
            $this->foreign_key = array(static::getKeyName($this->class_name));
    }

    /**
     *
     */
    public function __get($name)
    {
        if($name === 'primary_key' && !isset($this->primary_key)) {
            $this->primary_key = array(Table::load($this->class_name)->primary_key[0]);
        }

        return $this->$name;
    }

    /**
     *
     */
    public function load(Model $model)
    {
        $keys = [];

        foreach ($this->foreign_key as $key)
            $keys[] = String::phpize($key);

        if (!($conditions = $this->create_conditions_from_keys($model, $this->primary_key, $keys)))
            return null;

        $options = $this->unset_non_finder_options($this->options);
        $options['conditions'] = $conditions;
        $class = $this->class_name;
        return $class::first($options);
    }

    /**
     *
     */
    public function load_eagerly($models = [], $attributes, $includes, Table $table)
    {
        $this->query_and_attach_related_models_eagerly($table, $models, $attributes, $includes, $this->primary_key, $this->foreign_key);
    }
}
