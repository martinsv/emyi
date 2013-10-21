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
 * HTTP standard message (Request/Response)
 *
 * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec5.html#sec5
 * @author Douglas G. Rodrigues <http://github.com/douggr>
 */
class Message {
    /**
     * @var string
     */
    protected $content = '';
    
    /**
     * @var array
     */
    protected $metadata = [];

    /**
     * Set message metadata
     *
     * Non-destructive setting of message metadata; always adds to the
     * metadata, never overwrites the entire metadata container.
     *
     * @param string|int|array|Traversable $spec
     * @param mixed $value
     * @throws InvalidArgumentException
     * @return Emyi\Http\Message
     */
    public function setMetadata($spec, $value = null)
    {
        if (is_scalar($spec)) {
            $this->metadata[$this->formatName($spec)] = $value;
            return $this;
        }

        if (!is_array($spec) && !$spec instanceof Traversable) {
            $type = is_object($spec) ? get_class($spec) : gettype($spec);
            throw new InvalidArgumentException(
                "Expected a string, array, or Traversable argument 
                 in first position; received `$type'");
        }

        foreach ($spec as $key => $value) {
            $this->metadata[$this->formatName($key)] = $value;
        }

        return $this;
    }

    /**
     * Retrieve all metadata or a single metadatum as specified by key
     *
     * @param null|string|int $key
     * @param null|mixed $default
     * @throws InvalidArgumentException
     * @return mixed
     */
    public function getMetadata($key = null, $default = null)
    {
        if (null === $key) {
            return $this->metadata;
        }

        if (!is_scalar($key)) {
            throw new InvalidArgumentException('Non-scalar argument provided for key');
        }

        if (array_key_exists($key, $this->metadata)) {
            return $this->metadata[$key];
        }

        return $default;
    }

    /**
     * Set message content
     *
     * @param mixed $value
     * @return Emyi\Http\Message
     */
    public function setContent($value)
    {
        $this->content = $value;
        return $this;
    }

    /**
     * Get message content
     *
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $headers = '';

        foreach ($this->getMetadata() as $key => $value) {
            $headers .= sprintf(
                "%s: %s\r\n",
                (string) $key,
                (string) $value
            );
        }

        return "{$headers}\r\n{$this->getContent()}";
    }

    /**
     * Formats the field-name
     *
     * @param string the message in any format
     * @return string formated name
     */
    protected function formatName($name)
    {
        $name = strtolower($name);

        return ucfirst(
            preg_replace_callback(
                '/([\s+|_|-]+([a-z0-9]))/',
                function ($m) {
                    return '-' . strtoupper($m[2]);
                }, $name)
        );
    }
}
