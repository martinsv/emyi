<?php
/*
 * emyi
 *
 * @link http://github.com/douggr/emyi for the canonical source repository
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace Emyi\Http;

use Traversable;
use InvalidArgumentException;

/**
 * Base for HTTP Request and Response classes
 *
 * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4
 * @author Douglas G. Rodrigues <http://github.com/douggr>
 */
abstract class Base extends Message {
    // The supported versions of the HTTP message 
    const VERSION_10 = '1.0';
    const VERSION_11 = '1.1';

    /**
     * @var string
     */
    protected $version = self::VERSION_11;

    /**
     * @var Message
     */
    protected $headers;

    /**
     *
     */
    final public function __construct()
    {
        $this->headers = new Message();
    }

    /**
     * Set the HTTP version for this object, one of 1.0 or 1.1
     *
     * @param string $version either 1.0 or 1.1
     * @return Emyi\Http\Base
     * @throws InvalidArgumentException
     */
    public function setVersion($version)
    {
        if (self::VERSION_10 != $version && self::VERSION_11 != $version) {
            throw new InvalidArgumentException(
                "Invalid or unsupported HTTP version: {$version}"
            );
        }

        $this->version = $version;
        return $this;
    }

    /**
     * Return the HTTP version for this object
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param string header name
     * @param mixed $value
     * @throws InvalidArgumentException
     * @return Emyi\Http\Message
     */
    public function addHeader($header, $value = null)
    {
        $this->headers->setMetadata($header, $value);
        return $this;
    }

    /**
     * @param array|Traversable|Message $headers
     * @throws InvalidArgumentException
     * @return Emyi\Http\Message
     */
    public function setHeaders($headers)
    {
        if ($headers instanceof Message) {
            return $this->addHeader($headers->getMetadata());
        }

        return $this->addHeader($headers);
    }

    /**
     * Return the given header value or null if not set
     *
     * @param string header name
     * @return mixed $value
     */
    public function getHeader($header)
    {
        return $this->headers->getMetadata($this->formatName($header), null);
    }

    /**
     * Return the header container responsible for headers
     *
     * @return Emyi\Http\Message
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @return Emyi\Http\Base
     */
    protected function getallheaders()
    {
        if (function_exists('apache_request_headers')) {
            $this->setHeaders(apache_request_headers());
        } else {
            foreach ($_SERVER as $key => $value) {
                if (preg_match('"^HTTP_(?P<header>\S+)"', $key, $match)) {
                    $this->addHeader($match['header'], $value);
                } elseif (preg_match('"^X[\s+|_|-]\S+"', $key)) {
                    $this->addHeader($key, $value);
                }
            }
        }

        return $this;
    }
}
