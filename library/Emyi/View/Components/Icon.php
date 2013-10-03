<?php
/*
 * Emyi
 *
 * @link http://github.com/douggr/Emyi for the canonical source repository
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace Emyi\View\Components;

use Emyi\Util\String;

/**
 * A simple class to build and output HTML5 icons (based on FontAwesome)
 */
class Icon extends Element
{
    /**
     * @var boolean
     */
    protected $auto_id = false;

    /**
     * Create a new Element instance statically using $method as a icon class
     * and arguments as additional classes.
     *
     * @param string class name
     * @param array additional classes
     * @return Emyi\View\Components\Element
     * @internal
     */
    public static function __callStatic($class, array $additional_classes = [])
    {
        return (new static('i'))
            ->addClass('icon-' . strtolower(String::phpize($class, '-')))
            ->addClass($additional_classes);
    }
}
