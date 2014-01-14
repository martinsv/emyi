<?php
/*
 * emyi
 *
 * @link http://github.com/douggr/emyi for the canonical source repository
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace Emyi\Util;

/**
 * Representation of date and time.
 */
class DateTime extends \DateTime
{
    /**
     * Format accepted by PHP's date()
     * @var string
     */
    private $format = 'Y-m-d H:i:s';

    /**
     * Returns date formatted according to given format
     */
    public function __toString()
    {
        return $this->format($this->getFormat());
    }

    /**
     * Returns date formatted according to given format. If the format is
     * NULL, DateTime::$format is used.
     *
     * @param string $format Format accepted by date().
     * @return string Returns the formatted date string on success or NULL
     *      on failure. 
     */
    public function format($format = null)
    {
        if (null === $format) {
            $format = $this->getFormat();
        } else {
            $this->setFormat($format);
        }

        return parent::format($format);
    }

    /**
     * Set the DateTime format
     *
     * @param string $format Any format accepted by date()
     * @return void
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }

    /**
     * Returns the current DateTime format
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }
}
