<?php
/*
 * Emyi
 *
 * @link http://github.com/douggr/Emyi for the canonical source repository
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace Emyi\Util;

use LogicException;
use RuntimeException;
use Emyi\Mvc\Controller;
use Emyi\Util\String;
use Emyi\Http\Request;

/**
 * MVC Router
 */
class Router {
    /**
     *
     */
    const DEFAULT_CONTROLLER = 'index';

    /**
     *
     */
    const DEFAULT_ACTION = 'GET';

    /**
     * Maps the routes from urls.ini
     * @var array
     */
    static private $routes = [];

    
    /**
     *
     */
    protected $matched_route = null;

    /**
     *
     */
    protected $controller = self::DEFAULT_CONTROLLER;

    /**
     *
     */
    protected $action = self::DEFAULT_ACTION;

    /**
     *
     */
    protected $params = [];

    /**
     *
     */
    protected $conditions = [];

    /**
     * Dispatch the given Request
     *
     * @param Request
     * @return Emyi\Mvc\Router
     */
    public static function dispatch(Request $request)
    {
        $uri = str_replace(dirname($_SERVER['PHP_SELF']), '', self::getPathInfo());
        return (new static($request))->execute($uri);
    }

    /**
     * Route to a diffent URI and dispatch the current Request
     *
     * @param string
     * @return Emyi\Mvc\Router
     */
    public static function route_to($uri)
    {
        return (new static(new Request()))->execute($uri);
    }

    /**
     *
     */
    public function execute($request_uri = null)
    {
        $class = $this->findMatch($request_uri)->findFile();

        if (!class_exists($class, false)) {
            // should've been included in findFile
            throw new LogicException(
                "Class `$class' not found", 503);
        }

        if (preg_match('/^[^a-z]/i', $this->action)) {
            throw new RuntimeException(
                "Action names must start with a letter, `{$this->action}' given", 503);
        }

        $controller = new $class($this->request, $this->action, $this->controller);

        if ($controller instanceof Controller) {
            return call_user_func_array([$controller, '_execute'], $this->params);
        }

        throw new LogicException(
            "$class must be an instance of Emyi\\Mvc\\Controller", 503);
    }

    /**
     *
     */
    public function map($rule, array $target = [], array $conditions = [])
    {
        self::$routes[$rule] = [
            'url'        => $rule,
            'target'     => $target,
            'conditions' => $conditions
        ];

        return $this;
    }

    /**
     *
     */
    protected function __construct(Request $request)
    {
        $this->request = $request;

        if (file_exists(EAPP_PATH . '/etc/routes.ini') && 0 === sizeof(self::$routes)) {
            $this->mapDefaultRoutes();
            $routes = parse_ini_file(EAPP_PATH . '/etc/routes.ini', true);

            foreach ($routes as $rule => $route) {
                $route = array_replace([
                    'controller' => self::DEFAULT_CONTROLLER,
                    'action'     => self::DEFAULT_ACTION,
                    'conditions' => [],
                ], $route);

                foreach ($route as $index => $value) {
                    if ('condition.' === substr($index, 0, 10)) {
                        $route['conditions'][substr($index, 10)] = $value;
                    }
                }

                $target = ['action' => $route['action']];

                if (array_key_exists('controller', $route)) {
                    $target['controller'] = $route['controller'];
                }

                $this->map('/' . ltrim($rule, '/'), $target, $route['conditions']);
            }

            self::$routes = array_reverse(self::$routes);
        }
    }

    /**
     *
     */
    protected function findFile()
    {
        $controller = trim($this->controller, '/');

        if (false !== strpos($controller, '/')) {
            // controllers in modules (modules/MODULE/controllers/CONTROLLER
            $filename = [];

            foreach (explode('/', $controller) as $part)
                $filename[] = String::camelize($part, true);

            $class = '\\Modules\\' . implode('\\Controllers\\', $filename);
        } else {
            $class = '\\Controllers\\' . String::camelize($controller, true);
        }

        $filename = str_replace('\\', '/', $class);

        if (!file_exists($filename = EAPP_PATH . "{$filename}.php")) {
            throw new RuntimeException(
                "As of the matched route `{$this->matched_route}', `{$this->controller}'
                 was expected to be found in $filename. But file not found", 503);
        }

        require_once $filename;
        return $class;
    }

    /**
     *
     */
    protected function findMatch($request_uri = null)
    {
        if ('' === trim($request_uri)) {
            $request_uri = self::getPathInfo();
        }

        $request_uri = '/' . ltrim($request_uri, '/');

        foreach (self::$routes as $rule => $route) {
            $this->params     = [];
            $this->conditions = $route['conditions'];
            $target           = $route['target'];
            $param_names      = [];
            $param_values     = [];

            preg_match_all('@:([\w]+)@', $rule, $param_names, PREG_PATTERN_ORDER);
            $param_names = $param_names[0];
            $regex_url = preg_replace_callback('":[\w]+"', [$this, 'getRegexUrl'], $rule) . '/?';

            if (preg_match("@^{$regex_url}$@", $request_uri, $param_values)) {
                array_shift($param_values);

                foreach ($param_names as $index => $value) {
                    $this->params[substr($value, 1)] = urldecode($param_values[$index]);
                }

                foreach ($target as $key => $value) {
                    $this->params[$key] = $value;
                }

                if (isset($this->params['action'])) {
                    if ('$' === $this->params['action'][0]) {
                        $this->action = $param_values[$this->params['action'][1]];
                    } else {
                        $this->action = $this->params['action'];
                    }

                    unset($this->params['action']);
                }

                if (!array_key_exists('controller', $this->params)) {
                    $this->controller = self::DEFAULT_CONTROLLER;
                } else {
                    if ('$' === $this->params['controller'][0]) {
                        $this->controller = $param_values[$this->params['controller'][1]];
                    } else {
                        $this->controller = $this->params['controller'];
                    }

                    unset($this->params['controller']);
                }

                $this->matched_route = $rule;
                break;
            }
        }

        if (null === $this->matched_route) {
            throw new RuntimeException(
                "There wasn't any route matching `{$request_uri}'", 404);
        }

        return $this;
    }

    /**
     *
     */
    protected function mapDefaultRoutes()
    {
        // this would problably map any type of controller
        // you'd use routes.ini to map special URLS to different controllers
        $this
            // home page controller
            ->map('/', ['controller' => self::DEFAULT_CONTROLLER])

            // any controller
            ->map('/:controller')

            // any controller followed by an action
            ->map('/:controller/:action')
        ;
    }

    /**
     *
     */
    private function getRegexUrl($matches)
    {
        if (array_key_exists($matches[0], $this->conditions)) {
            return "({$this->conditions[$matches[0]]})";
        } else {
            return '([a-zA-Z0-9-/]+)';
        }
    }

    /**
     *
     */
    private static function getPathInfo()
    {
        if (isset($_SERVER['PATH_INFO'])) {
            return $_SERVER['PATH_INFO'];
        } else {
            return parse_url(
                str_replace(
                    dirname($_SERVER['SCRIPT_NAME']),
                    '',
                    $_SERVER['REQUEST_URI']),
                PHP_URL_PATH);
        }
    }
}
