<?php
/*
 * emyi
 *
 * @link http://github.com/douggr/emyi for the canonical source repository
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace Emyi\Util;

/**
 * Reports information about a class. 
 */
class ReflectionClass extends \ReflectionClass {
    /**
     *
     */
    public function getDirName()
    {
        return dirname($this->getFileName());
    }
}
