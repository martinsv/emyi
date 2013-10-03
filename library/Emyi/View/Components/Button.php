<?php
/*
 * Emyi
 *
 * @link http://github.com/douggr/Emyi for the canonical source repository
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace Emyi\View\Components;

/**
 * A simple class to build and output HTML5 buttons
 */
class Button extends Element
{
    /**
     * Create a new Element instance statically using $method as a tag
     * and arguments as attributes.
     *
     * @param string tag name
     * @param array attributes to set
     * @return Emyi\View\Components\Element
     * @internal
     */
    public static function __callStatic($tag, array $attributes = [])
    {
        return parent::__callStatic('button', [])
            ->addClass("btn btn-$tag")
            ->addContent($attributes[0]);
    }
}
