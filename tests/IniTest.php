<?php

namespace Bolt\Common\Tests;

use Bolt\Common\Ini;
use PHPUnit\Framework\TestCase;

class IniTest extends TestCase
{
    const STR_KEY = 'session.save_path';
    const BOOL_KEY = 'assert.bail';
    const INT_KEY = 'precision';
    const BYTES_KEY = 'memory_limit';
    const READ_ONLY_KEY = 'allow_url_fopen';
    const TRIGGERS_ERROR_KEY = 'session.name';
    const NONEXISTENT_KEY = 'herp.derp';

    private $backup;

    protected function setUp()
    {
        $this->backup = [
            static::STR_KEY   => ini_get(static::STR_KEY),
            static::BOOL_KEY  => ini_get(static::BOOL_KEY),
            static::INT_KEY   => ini_get(static::INT_KEY),
            static::BYTES_KEY => ini_get(static::BYTES_KEY),
        ];
    }

    protected function tearDown()
    {
        foreach ($this->backup as $key => $value) {
            ini_set($key, $value);
        }
    }

    public function testHas()
    {
        $this->assertTrue(Ini::has(static::STR_KEY));
        $this->assertFalse(Ini::has(static::NONEXISTENT_KEY));
    }

    public function testGetStr()
    {
        ini_set(static::STR_KEY, 'foo');
        $this->assertSame('foo', Ini::getStr(static::STR_KEY));

        ini_set(static::STR_KEY, '');
        $this->assertSame('default', Ini::getStr(static::STR_KEY, 'default'));

        $this->assertNull(Ini::getStr(static::NONEXISTENT_KEY));
    }

    public function testGetBool()
    {
        ini_set(static::BOOL_KEY, '0');
        $this->assertFalse(Ini::getBool(static::BOOL_KEY));

        ini_set(static::BOOL_KEY, '');
        $this->assertFalse(Ini::getBool(static::BOOL_KEY));

        ini_set(static::BOOL_KEY, '1');
        $this->assertTrue(Ini::getBool(static::BOOL_KEY));

        $this->assertFalse(Ini::getBool(static::NONEXISTENT_KEY));
    }

    public function testGetNumeric()
    {
        ini_set(static::INT_KEY, '2');
        $this->assertSame(2, Ini::getNumeric(static::INT_KEY));

        ini_set(static::INT_KEY, '3.2');
        $this->assertSame(3.2, Ini::getNumeric(static::INT_KEY));

        ini_set(static::INT_KEY, '');
        $this->assertSame(4.0, Ini::getNumeric(static::INT_KEY, 4.0));

        $this->assertNull(Ini::getNumeric(static::NONEXISTENT_KEY));
    }

    public function provideBytes()
    {
        return [
            ['500000', 500000],
            ['5K', 5120],
            ['5k', 5120],
            ['5KB', 5120],
            ['5kb', 5120],
            ['5.5K', 5120],
            ['0.25M', 0],
            ['5M', 5242880],
            ['5G', 5368709120],
            ['-1', -1],
            ['', null],
            [null, null, self::NONEXISTENT_KEY]
        ];
    }

    /**
     * @dataProvider provideBytes
     *
     * @param string|null $value
     * @param mixed       $expected
     * @param string      $key
     */
    public function testGetBytes($value, $expected, $key = self::BYTES_KEY)
    {
        if ($value !== null) {
            Ini::set($key, $value);
        }
        $this->assertSame($expected, Ini::getBytes($key));
    }

    public function testSet()
    {
        Ini::set(static::STR_KEY, 'foo');
        $this->assertSame('foo', ini_get(static::STR_KEY));
    }

    public function testSetBoolean()
    {
        Ini::set(static::BOOL_KEY, false);
        $this->assertSame('0', ini_get(static::BOOL_KEY));

        Ini::set(static::BOOL_KEY, true);
        $this->assertSame('1', ini_get(static::BOOL_KEY));
    }

    public function testSetInvalidType()
    {
        $this->setExpectedException(
            \InvalidArgumentException::class,
            'ini values must be scalar or null. Got: array'
        );

        Ini::set(static::INT_KEY, []);
    }

    public function testSetInvalidValue()
    {
        $this->setExpectedException(
            \RuntimeException::class,
            sprintf('Unable to change ini option "%s" to -2.', static::INT_KEY)
        );

        Ini::set(static::INT_KEY, -2);
    }

    public function testSetInvalidValueErrorTriggered()
    {
        try {
            Ini::set(static::TRIGGERS_ERROR_KEY, '');
            $this->fail('Exception should be thrown');
        } catch (\Exception $e) {
        }

        $this->assertInstanceOf(
            \ErrorException::class,
            $e->getPrevious(),
            'set() should have caught a triggered error and thrown it as an ErrorException.'
        );
    }

    public function testSetNewKey()
    {
        $this->setExpectedException(
            \RuntimeException::class,
            sprintf('The ini option "%s" does not exist. New ini options cannot be added.', static::NONEXISTENT_KEY)
        );

        Ini::set(static::NONEXISTENT_KEY, 'foo');
    }

    public function testSetUnauthorized()
    {
        $this->setExpectedException(
            \RuntimeException::class,
            sprintf('Unable to change ini option "%s", because it is not editable at runtime.', static::READ_ONLY_KEY)
        );

        Ini::set(static::READ_ONLY_KEY, true);
    }
}
