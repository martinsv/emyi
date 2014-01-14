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

/**
 * A simple class to build and output HTML5 elements
 */
class Element
{
    // PHP fake-ish multiple-inheritance
    use Attributes;

    /**
     * The HTML tag to use
     * @var string
     */
    protected $tag;

    /**
     * The content for this element node.
     * @var string
     */
    private $content = '';

    /**
     * Certain end tags can be omitted, does not mean the element is not
     * present; it is implied, but it is still there <3 HTML5
     * @var boolean
     */
    private $omitted;

    /**
     * Void elements can't have any contents (since there's no end tag, no
     * content can be put between the start tag and the end tag).
     * @var boolean
     */
    private $is_void;
    
    //----------------------------------------------------------------- public
    /**
     *
     */
    final public function __construct($tag /* attributes? */)
    {
        $this->tag     = $tag;

        // do not need to make these next 2 arrays static since the spec won't
        // suffer big changes regarding them
        $this->is_void = in_array($this->tag, [
            'area',
            'base',
            'br',
            'col',
            'command',
            'embed',
            'hr',
            'img',
            'input',
            'keygen',
            'link',
            'meta',
            'param',
            'source',
            'track',
            'wbr'
        ]);

        $this->omitted = in_array($this->tag, [
            'li',
            'dt',
            'dd',
            'rt',
            'rp',
            'optgroup',
            'option',
            'colgroup',
            'colgroup',
            'thead',
            'tbody',
            'tfoot',
            'tr',
            'td',
            'th'
        ]);
    }

    /**
     * @return Emyi\View\Components\Element
     */
    public function addContent($content, $escape = false)
    {
        if (!$this->is_void) {
            $this->content .= $escape ? String::htmlentities($content) : $content;
        }

        return $this;
    }

    /**
     * @return Emyi\View\Components\Element
     */
    public function clearContent()
    {
        $this->content = '';
        return $this;
    }

    /**
     * Returns this object as string
     * @return string
     */
    public function __toString()
    {
        $return = "<{$this->tag}{$this->toString()}>{$this->content}";

        if (!$this->omitted && !$this->is_void) {
            $return .= "</{$this->tag}>";
        }

        return $return;
    }

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
        $html = new Element($tag);
        $html->auto_id = false;

        foreach ($attributes as $attribute) {
            $html->setAttribute($attribute);
        }

        return $html;
    }


    /**
     * Create a new Element instance statically using $method as a tag
     * and arguments as attributes.
     *
     * @param string tag name
     * @param array attributes to set
     * @return Emyi\View\Components\Element
     * @internal
     */
    public static function create($tag, array $attributes = [])
    {
        return static::__callStatic($tag, $attributes);
    }
}
