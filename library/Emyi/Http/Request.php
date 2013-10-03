<?php
/*
 * emyi
 *
 * @link http://github.com/douggr/emyi for the canonical source repository
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace Emyi\Http;

use InvalidArgumentException;

/**
 * HTTP Request abstraction.
 * This class is an abstraction only, and is not intended to adhere with the
 * {@link specification http://www.w3.org/Protocols/rfc2616/rfc2616-sec5.html#sec5}
 *
 * @author Douglas G. Rodrigues <http://github.com/douggr>
 */
class Request extends Base
{
    const METHOD_OPTIONS = 'OPTIONS';
    const METHOD_GET     = 'GET';
    const METHOD_HEAD    = 'HEAD';
    const METHOD_POST    = 'POST';
    const METHOD_PUT     = 'PUT';
    const METHOD_DELETE  = 'DELETE';
    const METHOD_TRACE   = 'TRACE';
    const METHOD_CONNECT = 'CONNECT';
    const METHOD_PATCH   = 'PATCH';

    /**
     * @var string
     */
    protected $uri = '/';

    /**
     *
     */
    protected $path_info = '/';

    /**
     * @var string one of the METHOD_* constants
     */
    protected $method = self::METHOD_GET;

    /**
     *
     */
    protected static $base_href = '/';

    /**
     *
     */
    protected $is_secure = false;

    /**
     *
     */
    private $request_hash;

    /**
     * Build a Request object direct from the HTTP server
     * @return Emyi\Http\Request
     */
    public static function fromServer()
    {
        $DS = DIRECTORY_SEPARATOR;
        $BH = rtrim(str_replace($DS, '/', dirname($_SERVER['SCRIPT_NAME'])), $DS) . '/';

        if (isset($_SERVER['PATH_INFO'])) {
            $PI = $_SERVER['PATH_INFO'];
        } else {
            $PI = parse_url(str_replace(
                dirname($_SERVER['SCRIPT_NAME']), '',
                $_SERVER['REQUEST_URI']
            ), PHP_URL_PATH);
        }

        return (new static())
            ->getallheaders()
            ->setHash()
            ->setMethod($_SERVER['REQUEST_METHOD'])
            ->setBaseHref($BH)
            ->setPathInfo($PI)
            ->setRequestUri($_SERVER['REQUEST_URI'])
            ->setVersion(substr($_SERVER['SERVER_PROTOCOL'], 5));
    }

    /**
     * Build a Request object from a well-formed Http Request string
     *
     * @param string $string
     * @return Emyi\Http\Request
     * @throws InvalidArgumentException
     */
    public static function fromString($string)
    {
        $methods = implode(
            '|', [
            self::METHOD_OPTIONS,
            self::METHOD_GET,
            self::METHOD_HEAD,
            self::METHOD_POST,
            self::METHOD_PUT,
            self::METHOD_DELETE,
            self::METHOD_TRACE,
            self::METHOD_CONNECT,
            self::METHOD_PATCH
        ]);

        $lines = explode('\r\n', $string);

        // first line must be Method/Uri/Version string
        $matches   = null;
        $regex     = '"^(?P<method>{$methods})\s(?P<uri>[^ ]*)(?:\sHTTP\/(?P<version>\d+\.\d+)){0,1}"';
        $firstLine = array_shift($lines);

        if (!preg_match($regex, $firstLine, $matches)) {
            throw new InvalidArgumentException(
                'A valid request line was not found in the provided string'
            );
        }

        $request = new static();
        $request->setMethod($matches['method']);
        $request->setRequestUri($matches['uri']);

        if ($matches['version']) {
            $request->setVersion($matches['version']);
        }

        if (count($lines) == 0) {
            return $request;
        }

        $isHeader = true;
        $headers  = $rawBody = [];

        while ($lines) {
            $nextLine = array_shift($lines);
            if ('' == $nextLine) {
                $isHeader = false;
                continue;
            }

            if ($isHeader) {
                call_user_func_array([$request, 'addHeader'], explode(': ', $nextLine));
                $headers[] = $nextLine;
            } else {
                $rawBody[] = $nextLine;
            }
        }

        if ($headers) {
            // todo
        }

        if ($rawBody) {
            $request->setContent(implode("\r\n", $rawBody));
        }

        return $request;
    }

    /**
     *
     */
    public function setHash()
    {
        $this->request_hash = sha1($this->getHeader('user-agent') . $_SERVER['REMOTE_ADDR']);

        return $this;
    }

    /**
     *
     */
    public function getHash()
    {
        return $this->request_hash;
    }

    /**
     * Return the request query parameters or a single query parameter
     *
     * @param string parameter name to retrieve, or null to get the whole
     *      container
     * @return mixed
     */
    function get($param)
    {
        if (array_key_exists($param, $_GET)) {
            return $_GET[$param];
        }
    }

    /**
     * Set the path info for this request
     *
     * @param string
     * @return Emyi\Http\Request
     */
    public function setPathInfo($path_info)
    {
        if ('' === trim($path_info)) {
            $path_info = '/';
        }

        $this->path_info = $path_info;
        return $this;
    }

    /**
     * Return the path info for this request
     *
     * @return string
     */
    public function getPathInfo()
    {
        return $this->path_info;
    }

    /**
     * Set the base href for this request
     *
     * @param string
     * @return Emyi\Http\Request
     */
    public function setBaseHref($base_href)
    {
        self::$base_href = $base_href;
        return $this;
    }

    /**
     * Return the base href for this request
     *
     * @return string
     */
    public function getBaseHref()
    {
        return self::baseHref();
    }

    /**
     *
     */
    public static function baseHref()
    {
        return self::$base_href;
    }

    /**
     * Return the URI for this request
     *
     * @return string
     */
    public function getRequestUri()
    {
        return $this->uri;
    }

    /**
     * Set the URI for this request
     *
     * @param string
     * @return Emyi\Http\Request
     */
    public function setRequestUri($uri)
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * Return the method for this request
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Set the method for this request
     *
     * @param string one of the METHOD_* constants
     * @return Emyi\Http\Request
     * @throws InvalidArgumentException
     */
    public function setMethod($method)
    {
        $method = strtoupper($method);

        if (!defined("static::METHOD_{$method}")) {
            throw new InvalidArgumentException('Invalid HTTP method passed');
        }

        $this->method = $method;

        return $this;
    }

    /**
     * @return bool
     */
    public function isOptions()
    {
        return 'OPTIONS' === $this->getMethod();
    }

    /**
     * @return bool
     */
    public function isGet()
    {
        return 'GET' === $this->getMethod();
    }

    /**
     * @return bool
     */
    public function isHead()
    {
        return 'HEAD' === $this->getMethod();
    }

    /**
     * @return bool
     */
    public function isPost()
    {
        return 'POST' === $this->getMethod();
    }

    /**
     * @return bool
     */
    public function isPut()
    {
        return 'PUT' === $this->getMethod();
    }

    /**
     * @return bool
     */
    public function isDelete()
    {
        return 'DELETE' === $this->getMethod();
    }

    /**
     * @return bool
     */
    public function isTrace()
    {
        return 'TRACE' === $this->getMethod();
    }

    /**
     * @return bool
     */
    public function isConnect()
    {
        return 'CONNECT' === $this->getMethod();
    }

    /**
     * @return bool
     */
    public function isPatch()
    {
        return 'PATCH' === $this->getMethod();
    }

    /**
     * Is the request done with XMLHttpRequest?
     * @return bool
     */
    public function isXmlHttpRequest()
    {
        return 'XMLHttpRequest' === $this->getHeader('X_REQUESTED_WITH');
    }

    /**
     * Is the request secure?
     * @return bool
     */
    public function isSecure()
    {
        return $this->is_secure;
    }

    /**
     * Return the formatted request line for this http request
     *
     * @return string
     */
    public function getRequestLine()
    {
        return "{$this->method} {$this->uri} HTTP/{$this->getVersion()}";
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return "{$this->getRequestLine()}\r\n{$this->getHeaders()}{$this->getContent()}";
    }
}
