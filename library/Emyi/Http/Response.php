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
 * HTTP Response abstraction.
 * This class is an abstraction only, and is not intended to adhere with the
 * {@link specification http://www.w3.org/Protocols/rfc2616/rfc2616-sec6.html#sec6}
 *
 * @author Douglas G. Rodrigues <http://github.com/douggr>
 */
class Response extends Base
{
    /**
     * Standard HTTP response codes
     * @var array
     */
    private static $http_response_codes = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'Ok',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported'
    ]; //$_codes

    /**
     * @var int status code
     */
    protected $status_code = 200;

    /**
     * Build a Response object from a well-formed Http Response string.
     * This method is based on Zend\Http\Response
     *
     * @param string $string
     * @return Emyi\Http\Response
     * @throws InvalidArgumentException
     */
    public static function fromString($string)
    {
        $response = new static();
        $lines = explode("\r\n", $string);

        if (!is_array($lines) || count($lines) == 1) {
            $lines = explode("\n", $string);
        }

        $firstLine = array_shift($lines);
        $regex     = '/^HTTP\/(?P<version>1\.[01]) (?P<status>\d{3})(?:[ ]+(?P<reason>.*))?$/';
        $matches   = [];

        if (!preg_match($regex, $firstLine, $matches)) {
            throw new InvalidArgumentException(
                'A valid response status line was not found in the provided string'
            );
        }

        $response->setVersion($matches['version']);
        $response->setStatusCode($matches['status']);

        if (count($lines) == 0) {
            return $response;
        }

        $isHeader = true;
        $headers  = $content = [];

        while ($lines) {
            $nextLine = array_shift($lines);

            if ($isHeader && '' == $nextLine) {
                $isHeader = false;
                continue;
            }

            if ($isHeader) {
                // I need to fix this one
                call_user_func_array([$response, 'addHeader'], explode(': ', $nextLine));
                $headers[] = $nextLine;
            } else {
                $content[] = $nextLine;
            }
        }

        if ($content) {
            $response->setContent(implode("\r\n", $content));
        }

        return $response;
    }

    /**
     * Set message content
     *
     * @param mixed $value
     * @return Emyi\Http\Message
     */
    public function setContent($value)
    {
        if (is_array($value) || $value instanceof Traversable) {
            $value = json_encode($value);
        }

        parent::setContent($value);
        return $this;
    }

    /**
     * @param string header name
     * @param mixed $value
     * @throws InvalidArgumentException
     * @return Emyi\Http\Message
     */
    public function addHeader($header, $value = null)
    {
        $this->headers->setMetadata($header, $value, true);
        return $this;
    }

    /**
     * Set HTTP status code and (optionally) message
     *
     * @param int $code
     * @throws InvalidArgumentException
     * @return Emyi\Http\Response
     */
    public function setStatusCode($code)
    {
        if (!array_key_exists($code, self::$http_response_codes)) {
            throw new InvalidArgumentException(
                "Invalid status code provided: `$code'"
            );
        }

        $this->status_code = (int) $code;
        return $this;
    }

    /**
     * Retrieve HTTP status code
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->status_code;
    }

    /**
     * Get HTTP status message
     *
     * @return string
     */
    public function getStatusPhrase()
    {
        return self::$http_response_codes[$this->getStatusCode()];
    }

    /**
     * Send this response
     */
    public function sendResponse()
    {
        header_remove();
        http_response_code($this->getStatusCode());

        foreach ($this->headers->getMetadata() as $header => $value) {
            header("{$header}: {$value}");
        }

        echo $this->getContent();
    }

    /**
     * Return the formatted request line for this response
     *
     * @return string
     */
    public function renderStatusLine()
    {
        return "HTTP/{$this->getVersion()} {$this->getStatusCode()} {$this->getStatusPhrase()}";
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return "{$this->renderStatusLine()}\r\n{$this->getHeaders()}{$this->getContent()}";
    }
}
