<?php
/*
 * emyi
 *
 * @link http://github.com/douggr/emyi for the canonical source repository
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace EmyiTest\Util;

use Emyi\Util\String;

class Test extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Emyi\Util\String::pluralize
     */
    public function test_pluralize()
    {
        $this->assertEquals('aliases', String::pluralize('alias'));
        $this->assertEquals('hives', String::pluralize('hive'));
    }

    /**
     * @covers Emyi\Util\String::singularize
     */
    public function test_singularize()
    {
        $this->assertEquals('bus', String::singularize('buses'));
        $this->assertEquals('leaf', String::singularize('leaves'));
    }

    /**
     * @covers Emyi\Util\String::phpize
     */
    public function test_phpize()
    {
        $this->assertEquals('foo_bar', String::phpize('FooBar'));
        $this->assertEquals('foo_bar', String::phpize('fooBar'));
    }

    /**
     * @covers Emyi\Util\String::camelize
     */
    public function test_camelize()
    {
        $this->assertEquals('FooBar', String::camelize('foo_bar', true));
        $this->assertEquals('fooBar', String::camelize('foo-bar'));
        $this->assertEquals('fooBar', String::camelize('foo bar'));
    }

    /**
     * @covers Emyi\Util\String::isupper
     */
    public function test_isupper()
    {
        $this->assertTrue(String::isupper('UPPER'));
        $this->assertFalse(String::isupper('UPper'));
    }

    /**
     * @covers Emyi\Util\String::islower
     */
    public function test_islower()
    {
        $this->assertTrue(String::islower('lower'));
        $this->assertFalse(String::islower('Lower'));
    }

    /**
     * @covers Emyi\Util\String::escape
     */
    public function test_escape()
    {
        $this->assertEquals('No new line\rhere', String::escape("No new line\rhere"));
    }

    /**
     * @covers Emyi\Util\String::removeponctuation
     */
    public function test_removeponctuation()
    {
        $this->assertEquals('aeious', String::removeponctuation("aeiou's"));
    }

    /**
     * @covers Emyi\Util\String::accentremove
     */
    public function test_accentremove()
    {
        $this->assertEquals('aeiou\'s', String::accentremove("áéíóú's"));
    }

    /**
     * @covers Emyi\Util\String::strlen
     */
    public function test_strlen()
    {
        $this->assertTrue(5 === String::strlen('áéíóú'));
    }

    /**
     * @covers Emyi\Util\String::random
     */
    public function test_random()
    {
        $rand1 = String::random(5);
        $rand2 = String::random(5);
        $rand3 = String::random(7);

        // same length
        $this->assertTrue(String::strlen($rand1) == String::strlen($rand2));
        $this->assertFalse(String::strlen($rand1) == String::strlen($rand3));

        $this->assertTrue($rand1 != $rand2);
        $this->assertTrue($rand3 != $rand2);
        $this->assertTrue($rand1 != $rand3);
    }
}
