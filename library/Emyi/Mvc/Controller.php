<?php
/*
 * Emyi
 *
 * @link http://github.com/douggr/Emyi for the canonical source repository
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace Emyi\Mvc;

use Traversable;
use LogicException;
use ReflectionClass;
use Emyi\Util\Config;
use Emyi\Util\String;
use Emyi\Util\Router;
use Emyi\Http\Request;
use Emyi\Http\Response;

/**
 * MVC abstract Controller
 * You'll probably never have to hack from here directly.
 *
 * @protected
 */
abstract class Controller
{
    /**
     * Acceptable request methods defined per Controller
     * @var array
     */
    protected static $accepts = [
        Request::METHOD_OPTIONS,
        Request::METHOD_GET,
        Request::METHOD_HEAD,
        Request::METHOD_POST,
        Request::METHOD_PUT,
        Request::METHOD_DELETE,
        Request::METHOD_TRACE,
        Request::METHOD_CONNECT,
        Request::METHOD_PATCH
    ];

    /**
     *
     */
    public $action;

    /**
     *
     */
    public $controller;

    /**
     * A reflection for this object
     * @var ReflectionClass
     */
    protected $reflection;

    /**
     * The view to use alongside with this Controller
     * @var string|array|object Any string, array (will be send as JSON) or
     *      template engine
     */
    protected $view;

    /**
     * The request we are processing
     * @var Emyi\Http\Request
     */
    private $request;

    /**
     * The response we will send
     * @var Emyi\Http\Response
     */
    private $response;

    /**
     * Hash for this controller
     */
    private $hash;

    /**
     * ctor
     */
    final public function __construct(Request $request, $action, $controller = Router::DEFAULT_CONTROLLER)
    {
        $this->action     = $action;
        $this->controller = $controller;
        $this->request    = $request;
        $this->response   = new Response();
        $this->reflection = new ReflectionClass($this);
        $this->setHash(md5($request->getRequestUri()));

        // execute any pre-init callback
        $this->init();
    }

    /**
     *
     */
    final public function _execute(/* params */)
    {
        try {
            if (!$this->isValidRequest()) {
                // Method Not Allowed
                throw new Exception(
                    "Method Not Allowed: {$this->request->getMethod()}", 405
                );
            }

            if ($this->action === Router::DEFAULT_ACTION) {
                $this->action = $this->request->getMethod();
            }

            $this->action = String::camelize($this->action);
            $action = $this->reflection->getMethod($this->action);

            if (!$action->isPublic()) {
                throw new Exception(
                    "`{$this->action}' must have public access (or maybe not an action?)", 500);
            }

            if (!method_exists($this, $this->action)) {
                // Not Found - method or action does not exists
                throw new Exception("Not Found {$this}::{$this->action}", 404);
            }

            // auth module does his job (contrib)
            if (true === Config::get('auth/enabled')) {
                $this->checkAuth();
            }

            // after AUTHENTICATION module
            $this->beforeExecute();

            // execute the ACTION itself
            $action->invokeArgs($this, func_get_args());
            //call_user_func_array([$this, $this->action], func_get_args());

            // process any callbacks before send the response
            $this->beforeSendResponse();

            // send the response
            $this->send()
                // process any callback after the response is sent
                ->afterSendResponse();

            // all good :)
        } catch (\ReflectionException $re) {
            throw new Exception($re->getMessage(), 404);
        } catch (Exception $mvc_exception) {
            //print_r($mvc_exception);
            throw $mvc_exception;
        } catch (\Exception $unknown_exception) {
            //print_r($unknown_exception);
            throw $unknown_exception;
        }
    }

    /**
     * Returns the name of the class we are on
     *
     * @return string
     */
    final public function __toString()
    {
        return get_called_class();
    }

    /**
     * Create a new View for this controller
     *
     * @return object The view instance
     */
    protected function createView()
    {
        $class = Config::get('view/engine');
        $this->view = (new $class())
            ->setTemplateDirectory(dirname($this->reflection->getFileName()) . '/../Views')
            ->setVariable([
                'controller' => $this->controller,
                'action'     => $this->action,
                'requestUri' => $this->request->getRequestUri(),
                'baseHref'   => $this->request->getBaseHref(),
            ]);

        return $this->view;
    }

    /**
     * Returns the current Response instance
     *
     * @return Emyi\Http\Response or null if the Response has not been created
     */
    protected function &getResponse()
    {
        return $this->response;
    }

    /**
     * Return the Request we are processing
     *
     * @return Emyi\Http\Request
     */
    protected function getRequest()
    {
        return $this->request;
    }

    /**
     * Send the Response.
     *
     * @return Emyi\Mvc\Controller
     */
    protected function send($exit_on_send = false)
    {
        if (is_string($this->view) || null === $this->view) {
            $content = '' . $this->view; // force type cast
        } elseif (is_object($this->view)) {
            $content = (string) $this->view;
        } elseif (is_array($this->view) || !$this->view instanceof Traversable) {
            $content = json_encode($this->view);
        }

        if ($this->request->isXmlHttpRequest()) {
            if (null === $this->response->getHeader('content-type')) {
                if (false !== strpos($this->request->getHeader('accept'), 'application/json')) {
                    $this->response->addHeader('content-type', 'application/json; charset=utf-8');
                } else {
                    $this->response->addHeader('content-type', 'text/plain; charset=utf-8');
                }
            }
        } else {
            if (null === $this->response->getHeader('content-type')) {
                $this->response->addHeader('content-type', 'text/html; charset=utf-8');
            }
        }

        $this->response->addHeader('vary', 'Accept');

        if ('' == $this->response->getContent()) {
            $this->response->setContent($content);
        }

        /// send the response
        $this->response->sendResponse();

        // note that after_send callbacks are not executed
        if ($exit_on_send) {
            exit;
        }

        return $this;
    }

    // callbacks, the sequence is:
    // 0: init() is called right after __construct
    // 1: AUTHENTICATION MODULE
    // 2: beforeExecute()
    // 2: ACTION
    // 2: beforeSendResponse()
    // 2: afterSendResponse()
    /**
     * Executes BEFORE the Authentication module
     * @return void
     */
    protected function init()
    {
        //
    }

    /**
     * Checks for user authentication and authorization
     *
     * @return void
     * @throws Emyi\Mvc\Exception as default behaviour
     * @throws Emyi\Auth\Exception if the user is not authenticated
     * @throws Emyi\Auth\Exception if the user cannot access the requested
     *      handler
     */
    protected function checkAuth()
    {
        throw new Exception('checkAuth is not implemented', 501);
    }

    /**
     * Executes BEFORE the ACTION is executed
     * @return void
     */
    protected function beforeExecute()
    {
        //
    }

    /**
     * Executes AFTER the ACTION is executed, but BEFORE the Response is send
     * @return void
     */
    protected function beforeSendResponse()
    {
        //
    }

    /**
     * Executes AFTER the ACTION is executed, AFTER the Response is sent
     * @return void
     */
    protected function afterSendResponse()
    {
        //
    }

    /**
     * Set the hash for this Controller
     * @param string $hash
     * @return self
     */
    protected function setHash($hash)
    {
        $this->hash = $hash;
    }

    /**
     * Return the hash for this Controller
     * @return string $hash
     */
    protected function getHash()
    {
        return $this->hash;
    }

    /**
     * Validates the request method. Only methods mapped on static::$accepts
     * are accepted
     *
     * @return boolean true if the Request is acceptable
     */
    private function isValidRequest()
    {
        return in_array($this->request->getMethod(), static::$accepts);
    }
}
