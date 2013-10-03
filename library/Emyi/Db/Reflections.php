<?php
/*
 * emyi
 *
 * @link http://github.com/douggr/emyi for the canonical source repository
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace Emyi\Db;

use Emyi\Util\ReflectionClass;

/**
 * Simple class that caches reflections of classes.
 */
class Reflections extends Singleton
{
    /**
     * Current reflections.
     *
     * @var array
     */
    private $reflections = [];

    /**
     * Instantiates a new ReflectionClass for the given class.
     *
     * @param string $class Name of a class
     * @return Emyi\Db\Reflections
     */
    public function add($class = null)
    {
        $class = $this->getClass($class);

        if (!isset($this->reflections[$class])) {
            $this->reflections[$class] = new ReflectionClass($class);
        }

        return $this;
    }

    /**
     * Destroys the cached ReflectionClass.
     *
     * @param string $class Name of a class.
     * @return Emyi\Db\Reflections
     */
    public function destroy($class)
    {
        if (isset($this->reflections[$class])) {
            $this->reflections[$class] = null;
        }

        return $this;
    }
    
    /**
     * Get a cached ReflectionClass.
     *
     * @param string $class Optional name of a class
     * @return mixed null or a ReflectionClass instance
     * @throws Exception if class was not found
     */
    public function get($class=null)
    {
        $class = $this->getClass($class);

        if (isset($this->reflections[$class])) {
            return $this->reflections[$class];
        }

        throw new Exception("Class not found: $class");
    }

    /**
     * Retrieve a class name to be reflected.
     *
     * @param mixed $mixed An object or name of a class
     * @return string
     */
    private function getClass($mixed = null)
    {
        if (is_object($mixed))
            return getClass($mixed);

        if (!is_null($mixed))
            return $mixed;

        return $this->get_called_class();
    }
}
