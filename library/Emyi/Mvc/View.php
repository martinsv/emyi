<?php
/*
 * Emyi
 *
 * @link http://github.com/douggr/Emyi for the canonical source repository
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace Emyi\Mvc;

use Emyi\Util\String;
use Emyi\Util\Config;

/**
 * MVC abstract View. This is the default template engine for Emyi
 *
 * @protected
 */
class View
{
    /**
     * The template for this view
     * @var string
     */
    public $template;

    /**
     *
     */
    private $template_is_file = false;

    /**
     * The main layout file for this view, the template is injected into
     * the $content variable
     * @var string
     */
    private $layout = 'default.tpl';

    /**
     * Root directory for "source" templates
     * @var string
     */
    private $template_directory;

    /**
     * Variables for substitution
     * @var array
     */
    private $variables = [];

    /**
     * List of callback functions specified by the user
     * @var array
     */
    static $callbacks = [];

    //----------------------------------------------------------------- public
    /**
     * ctor
     */
    public function __construct()
    {
        $this->registerCallback('a', '\Emyi\View\Components\A::create_link');
        $this->registerCallback('e', '\Emyi\View\Components\Element::create');
        $this->registerCallback('escape', '\Emyi\Util\String::escape');
        $this->registerCallback('config', '\Emyi\Util\Config::get');
    }

    /**
     * Assign variables/objects to the templates
     *
     * @see setVariable
     */
    public function __set($var, $value)
    {
        return $this->setVariable($var, $value);
    }

    /**
     * Retrieve the variables/objects from templates
     *
     * @param $name
     */
    public function __get($name)
    {
        if (array_key_exists($name, $this->variables)) {
            return $this->variables[$name];
        }
    }

    /**
     * Fetch the output of a template into a string
     *
     * @return string
     */
    public function __toString()
    {
        try {
            $content = $this->render();
        } catch (\Exception $e) {
            $content = (string) $e;
        }

        return $content;
    }

    /**
     * Fetch the output of a template into a string
     *
     * @return string
     */
    public function render()
    {
        extract($this->variables);
        ob_start();

        if ($this->template_is_file) {
            require_once $this->template;
        } else {
            echo $this->template;
        }

        $content = ob_get_clean();

        if (null !== $this->layout) {
            $content = (new self())
                ->setLayout(null)
                ->setTemplateDirectory(EAPP_PATH . '/layout')
                ->loadTemplate($this->layout)
                ->setVariable($this->variables)
                ->setVariable('content', $content)
                ->render();
        }

        return $content;
    }

    /**
     * Assign variables/objects to templates
     *
     * @param mixed
     * @param mixed
     */
    public function setVariable($variable, $value = null)
    {
        if (is_array($variable) || $variable instanceof \Iterator) {
            foreach ($variable as $var => $value) {
                $this->setVariable($var, $value);
            }
        } else {
            $this->variables[$variable] = $value;
        }

        return $this;
    }

    /**
     * Registers a new function to be available within templates as a callback.
     *
     * @param string The alias to be used inside templates
     * @param Closure|string The callback function
     */
    public function registerCallback($alias, $name_or_closure)
    {
        if (!is_callable($name_or_closure)) {
            throw new Exception("The callback `$alias' must be callable");
        }

        self::$callbacks[$alias] = $name_or_closure;
        return $this;
    }

    /**
     * Set a template as string
     *
     * @param string
     */
    public function setTemplate($string)
    {
        $this->template_is_file = false;
        $this->template = $string;
        return $this;
    }

    /**
     *
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;
        return $this;
    }

    /**
     * Load a template file
     *
     * @return self
     */
    public function loadTemplate($template, $internal = false)
    {
        if ($internal) {
            $this->template = $template;
        } else {
            $this->template = "{$this->template_directory}/{$template}";
        }

        if (!file_exists($this->template)) {
            throw new Exception("No such file {$this->template}");
        }

        $this->template_is_file = true;
        return $this;
    }

    /**
     * @return self
     */
    public function setTemplateDirectory($dir)
    {
        if (!is_dir($dir)) {
            throw new Exception("No such directory $dir");
        }

        $this->template_directory = $dir;
        return $this;
    }

    /**
     * Triggered when invoking inaccessible methods in an object context.
     * @internal
     */
    final public function __call($method, array $args = [])
    {
        if (isset(self::$callbacks[$method])) {
            return call_user_func_array(self::$callbacks[$method], $args);
        }
    }
}
