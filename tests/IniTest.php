<?php

declare(strict_types=1);

/*
 * This file is part of a Camelot Project package.
 *
 * (c) The Camelot Project
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Camelot\Common\Tests;

use Camelot\Common\Ini;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Camelot\Common\Ini
 *
 * @internal
 */
final class IniTest extends TestCase
{
    public const STR_KEY = 'user_agent';
    public const BOOL_KEY = 'assert.bail';
    public const NUMERIC_KEY = 'date.default_latitude';
    public const BYTES_KEY = 'memory_limit';
    public const VALIDATED_KEY = 'precision';

    public const READ_ONLY_KEY = 'allow_url_fopen';
    public const TRIGGERS_ERROR_KEY = 'session.name';
    public const NONEXISTENT_KEY = 'herp.derp';
    public const SILENT_ERROR_KEY = 'session.gc_maxlifetime';

    private array $backup;

    protected function setUp(): void
    {
        $this->backup = [
            static::STR_KEY => ini_get(static::STR_KEY),
            static::BOOL_KEY => ini_get(static::BOOL_KEY),
            static::NUMERIC_KEY => ini_get(static::NUMERIC_KEY),
            static::BYTES_KEY => ini_get(static::BYTES_KEY),
        ];
    }

    protected function tearDown(): void
    {
        foreach ($this->backup as $key => $value) {
            ini_set($key, $value);
        }
    }

    public function testHas(): void
    {
        static::assertTrue(Ini::has(static::STR_KEY));
        static::assertFalse(Ini::has(static::NONEXISTENT_KEY));
    }

    public function testGetStr(): void
    {
        ini_set(static::STR_KEY, '');
        static::assertSame('default', Ini::getStr(static::STR_KEY, 'default'));

        ini_set(static::STR_KEY, 'foo');
        static::assertSame('foo', Ini::getStr(static::STR_KEY));

        static::assertNull(Ini::getStr(static::NONEXISTENT_KEY));
    }

    public function testGetBool(): void
    {
        ini_set(static::BOOL_KEY, '0');
        static::assertFalse(Ini::getBool(static::BOOL_KEY));

        ini_set(static::BOOL_KEY, '');
        static::assertFalse(Ini::getBool(static::BOOL_KEY));

        ini_set(static::BOOL_KEY, '1');
        static::assertTrue(Ini::getBool(static::BOOL_KEY));

        static::assertFalse(Ini::getBool(static::NONEXISTENT_KEY));
    }

    public function testGetNumeric(): void
    {
        if (!\defined('HHVM_VERSION')) {
            ini_set(static::NUMERIC_KEY, '');
            static::assertSame(4.0, Ini::getNumeric(static::NUMERIC_KEY, 4.0));
        }

        ini_set(static::NUMERIC_KEY, '2');
        static::assertSame(2, Ini::getNumeric(static::NUMERIC_KEY));

        ini_set(static::NUMERIC_KEY, '3.2');
        static::assertSame(3.2, Ini::getNumeric(static::NUMERIC_KEY));

        static::assertNull(Ini::getNumeric(static::NONEXISTENT_KEY));
    }

    public function provideBytes(): array
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
            [null, null, self::NONEXISTENT_KEY],
        ];
    }

    /** @dataProvider provideBytes */
    public function testGetBytes(?string $value, mixed $expected, string $key = self::BYTES_KEY): void
    {
        if ($value !== null) {
            Ini::set($key, $value);
        }
        static::assertSame($expected, Ini::getBytes($key));
    }

    public function testSet(): void
    {
        Ini::set(static::STR_KEY, 'foo');
        static::assertSame('foo', ini_get(static::STR_KEY));
    }

    public function testSetBoolean(): void
    {
        Ini::set(static::BOOL_KEY, false);
        static::assertSame('0', ini_get(static::BOOL_KEY));

        Ini::set(static::BOOL_KEY, true);
        static::assertSame('1', ini_get(static::BOOL_KEY));
    }

    public function testSetInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ini values must be scalar or null. Got: array');

        Ini::set(static::NUMERIC_KEY, []);
    }

    public function testSetInvalidValue(): void
    {
        if (\defined('HHVM_VERSION')) {
            static::markTestSkipped('HHVM does not disallow this.');
        }

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf('Unable to change ini option "%s" to -2.', static::VALIDATED_KEY));

        Ini::set(static::VALIDATED_KEY, -2);
    }

    /** @doesNotPerformAssertions */
    public function testSetInvalidValueSilentError(): void
    {
        // PHP allows setting floats on int keys, HHVM does not.
        if (!\defined('HHVM_VERSION')) {
            return;
        }

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf('Unable to change ini option "%s" to 5.5.', static::SILENT_ERROR_KEY));

        Ini::set(static::SILENT_ERROR_KEY, 5.5);
    }

    public function testSetInvalidValueErrorTriggered(): void
    {
        if (\defined('HHVM_VERSION')) {
            static::markTestSkipped('HHVM does not trigger error.');
        }

        try {
            Ini::set(static::TRIGGERS_ERROR_KEY, '');
        } catch (\Exception $e) {
        }
        if (!isset($e)) {
            static::fail('Exception should be thrown');

            return;
        }

        static::assertInstanceOf(
            \ErrorException::class,
            $e->getPrevious(),
            'set() should have caught a triggered error and thrown it as an ErrorException.'
        );
    }

    public function testSetNewKey(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf("The ini option '%s' does not exist. New ini options cannot be added.", static::NONEXISTENT_KEY));

        Ini::set(static::NONEXISTENT_KEY, 'foo');
    }

    public function testSetUnauthorized(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf("Unable to change ini option '%s', because it is not editable at runtime.", static::READ_ONLY_KEY));

        Ini::set(static::READ_ONLY_KEY, true);
    }
}
