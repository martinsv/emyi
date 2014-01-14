<?php
/*
 * Emyi
 *
 * @link http://github.com/douggr/Emyi for the canonical source repository
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace Emyi\View\Components;

use Emyi\Util\String;
use Emyi\Http\Request;

/**
 * A simple class to build and output HTML5 icons (based on FontAwesome)
 */
class A extends Element
{
    /**
     * @var boolean
     */
    protected $auto_id = false;

    /**
     * Create a new Element instance statically.
     *
     * @param string url
     * @param array href
     * @return Emyi\View\Components\Element
     */
    public static function create_link($url, $text = null, $appendDataHref = false)
    {
        if (null === $text) {
            $text = $url;
        }

        $component = (new static('a'))
            ->addContent($text)
            ->href(ltrim($url, '/'));

        if ($appendDataHref) {
            $component->data('href', $url);
        }

        return $component;
    }

    /**
     * Alias for A::create
     *
     * @param string class name
     * @param array additional classes
     * @return Emyi\View\Components\Element
     * @see create
     */
    public static function link($url, $text = null, $appendDataHref = false)
    {
        return self::create($url, $text, $appendDataHref);
    }

    /**
     *
     */
    public function pjax($container = '')
    {
        return $this->data('pjax', $container);
    }
}
