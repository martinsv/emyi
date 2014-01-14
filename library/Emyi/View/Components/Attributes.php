<?php
/*
 * Emyi
 *
 * @link http://github.com/douggr/Emyi for the canonical source repository
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace Emyi\View\Components;

use UnexpectedValueException;
use InvalidArgumentException;
use Emyi\Util\String;
use Emyi\Http\Request;

/**
 * A simple trait to build HTML5 attributes
 */
trait Attributes {
    //-------------------------------------------------------------- constants

    //------------------------------------------------------------- properties
    /**
     * @var string
     */
    public $id;

    /**
     * @var boolean
     */
    protected $auto_id = true;

    /**
     * Attributes for this input
     * @var array
     */
    protected $attributes = [];

    /**
     * @var array
     */
    protected $classes = [];

    /**
     * IDs vector
     * @var array
     */
    private static $ids = [];

    //----------------------------------------------------------------- public
    /**
     *
     */
    public function __call($method, $args = [])
    {
        return call_user_func_array(
            [$this, 'setAttribute'],
            array_merge([String::phpize($method, '-')], $args)
        );
    }

    /**
     * Get the value of an attribute of this object
     *
     * @param string The name of the attribute to get
     */
    public function __get($attribute)
    {
        $attribute = preg_replace('"[^a-z0-9]+"', '-', strtolower($attribute));

        if (array_key_exists($attribute, $this->attributes)) {
            return $this->attributes[$attribute];
        }

        return null;
    }

    /**
     * Set one attribute for this object
     *
     * @param string The name of the attribute to set
     * @param mixed
     */
    public function __set($attribute, $value)
    {
        $this->setAttribute($attribute, $value);
    }

    /**
     *
     */
    public function data($id, $value = '')
    {
        return $this->setAttribute("data-$id", $value);
    }

    /**
     * Adds the specified class(es) to this object.
     *
     * @param string One or more class names to be added to the class attribute
     *        of the element.
     * @return Emyi\View\Components\Attributes
     */
    public function addClass($class)
    {
        if (!is_array($class))
            $class = explode(' ', $class);

        $this->classes = array_merge($this->classes, $class);
        return $this;
    }

    /**
     * Remove a single class, multiple classes, or all classes from this object.
     *
     * @param string One or more class names to be added to the class attribute
     *        of the element.
     * @return Emyi\View\Components\Attributes
     */
    public function removeClass($class)
    {
        if (false !== $key = array_search($class, $this->classes)) {
            unset($this->classes[$key]);
        }

        return $this;
    }

    /**
     * Remove one attribute from this object
     *
     * @param string The name of the attribute to remove
     * @return Emyi\View\Components\Attributes
     */
    public function removeAttribute($attribute)
    {
        if (array_key_exists($attribute, $this->attributes)) {
            unset($this->attributes[$attribute]);
        }

        return $this;
    }

    /**
     * Set one attribute for this object
     *
     * @param string The name of the attribute to set
     * @param mixed
     * @return Emyi\View\Components\Attributes
     */
    public function setAttribute($attribute, $value = '')
    {
        if (is_array($attribute)) {
            foreach ($attribute as $attr => $value)
                $this->setAttribute($attr, $value);
        } else {
            $attribute = preg_replace('"[^a-z0-9]+"', '-', strtolower($attribute));

            if ('class' === $attribute) {
                throw new UnexpectedValueException(
                    'Cannot set the `class\' attribute within setAttribute.
                    Use addClass instead'
                );
            } elseif (null === $value) {
                return $this->removeAttribute($attribute);
            } elseif ('id' === $attribute) {
                if (in_array($value, self::$ids)) {
                    throw new InvalidArgumentException("The ID `$value' is already in use");
                }

                self::$ids[] = $value;
                $this->id = String::htmlentities($value);
            } else {
                switch ($attribute) {
                    case 'src':
                    case 'href':
                    case 'action':
                    case 'data-url':
                        if (false === strpos($value, '://')&& '//'  !== substr($value, 0, 2)) {
                            $value = Request::baseHref() . ltrim($value, '/');
                        }

                    default:
                        $this->attributes[$attribute] = String::htmlentities($value);
                }
            }
        }

        return $this;
    }

    /**
     * Reset the attributes vector
     * @return Emyi\View\Components\Attributes
     */
    public function reset()
    {
        $this->attributes = [];

        return $this;
    }

    /**
     *
     */
    final public function toString()
    {
        $return = '';

        if (sizeof($this->classes) > 0) {
            if ('' !== trim($data = implode(' ', array_unique($this->classes)))) {
                $return = " class=\"$data\"";
            }
        }

        if (null === $this->id && $this->auto_id) {
            $this->setAttribute('id', sprintf(':%d', sizeof(self::$ids)));
        }

        if ($this->id) {
            $return .= " id=\"{$this->id}\"";
        }

        foreach ($this->attributes as $attribute => $value) {
            if ('' === $value) {
                $return .= " {$attribute}";
            } else {
                $return .= " {$attribute}=\"{$value}\"";
            }
        }

        return $return;
    }
}
