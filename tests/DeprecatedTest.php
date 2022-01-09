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

use Camelot\Common\Deprecated;
use Camelot\Common\Tests\Fixtures\TestDeprecatedClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Camelot\Common\Deprecated
 *
 * @author Carson Full <carsonfull@gmail.com>
 *
 * @internal
 */
final class DeprecatedTest extends TestCase
{
    protected $deprecations = [];

    public function testMethod(): void
    {
        Deprecated::method(3.0, 'baz', 'Foo::bar');
        $this->assertDeprecation('Foo::bar() is deprecated since 3.0 and will be removed in 4.0. Use baz() instead.');
    }

    public function testMethodSentenceSuggestion(): void
    {
        Deprecated::method(null, 'Do it this way instead.', 'Foo::bar');
        $this->assertDeprecation('Foo::bar() is deprecated. Do it this way instead.');
    }

    public function testMethodSuggestClass(): void
    {
        TestDeprecatedClass::foo();
        $this->assertDeprecation(TestDeprecatedClass::class . '::foo() is deprecated. Use ArrayObject instead.');
    }

    public function testMethodSuggestClassWithMatchingMethod(): void
    {
        TestDeprecatedClass::getArrayCopy();
        $this->assertDeprecation(TestDeprecatedClass::class . '::getArrayCopy() is deprecated. Use ArrayObject::getArrayCopy() instead.');
    }

    public function testMethodConstructor(): void
    {
        new TestDeprecatedClass(true);
        $this->assertDeprecation(TestDeprecatedClass::class . ' is deprecated. Use ArrayObject instead.');
    }

    public function testMethodMagicCall(): void
    {
        /* @noinspection PhpUndefinedMethodInspection */
        TestDeprecatedClass::magicStatic();
        $this->assertDeprecation(TestDeprecatedClass::class . '::magicStatic() is deprecated. Use ArrayObject instead.');

        /* @noinspection PhpUndefinedMethodInspection */
        TestDeprecatedClass::append();
        $this->assertDeprecation(TestDeprecatedClass::class . '::append() is deprecated. Use ArrayObject::append() instead.');

        $cls = new TestDeprecatedClass();
        /* @noinspection PhpUndefinedMethodInspection */
        $cls->magic();
        $this->assertDeprecation(TestDeprecatedClass::class . '::magic() is deprecated. Use ArrayObject instead.');
        /* @noinspection PhpUndefinedMethodInspection */
        $cls->append();
        $this->assertDeprecation(TestDeprecatedClass::class . '::append() is deprecated. Use ArrayObject::append() instead.');
    }

    public function testMethodFunction(): void
    {
        eval('namespace Camelot\Common { function deprecatedFunction() { Deprecated::method(); }; deprecatedFunction(); }');
        $this->assertDeprecation('Camelot\Common\deprecatedFunction() is deprecated.');
    }

    public function testMethodIndex(): void
    {
        TestDeprecatedClass::someMethod();
        $this->assertDeprecation(TestDeprecatedClass::class . '::someMethod() is deprecated. Use ArrayObject instead.');
    }

    public function testMethodIndexNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a value greater than or equal to 0. Got: -1');

        Deprecated::method(null, null, -1);
    }

    public function testMethodIndexOutOfBounds(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('9000 is greater than the current call stack');

        Deprecated::method(null, null, 9000);
    }

    public function testMethodNotIntOrString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a non-empty string. Got: boolean');

        Deprecated::method(null, null, false);
    }

    public function testMethodEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a non-empty string. Got: ""');

        Deprecated::method(null, null, '');
    }

    public function testMethodNotFunction(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Camelot\\Common\\Deprecated::method() must be called from within a function/method.');

        // Using eval here because it is the easiest, but this also applies to require(_once)/include(_once)
        eval('\Camelot\Common\Deprecated::method();');
    }

    public function testClass(): void
    {
        Deprecated::cls('Foo\Bar');
        $this->assertDeprecation('Foo\Bar is deprecated.');
        Deprecated::cls('Foo\Bar', null, 'Bar\Baz');
        $this->assertDeprecation('Foo\Bar is deprecated. Use Bar\Baz instead.');
        Deprecated::cls('Foo\Bar', null, 'Do it this way instead.');
        $this->assertDeprecation('Foo\Bar is deprecated. Do it this way instead.');
    }

    public function testWarn(): void
    {
        Deprecated::warn('Foo bar');
        $this->assertDeprecation('Foo bar is deprecated.');

        Deprecated::warn('Foo bar', 3.0);
        $this->assertDeprecation('Foo bar is deprecated since 3.0 and will be removed in 4.0.');
        Deprecated::warn('Foo bar', 3.3);
        $this->assertDeprecation('Foo bar is deprecated since 3.3 and will be removed in 4.0.');

        Deprecated::warn('Foo bar', null, 'Use baz instead.');
        $this->assertDeprecation('Foo bar is deprecated. Use baz instead.');
        Deprecated::warn('Foo bar', 3.0, 'Use baz instead.');
        $this->assertDeprecation('Foo bar is deprecated since 3.0 and will be removed in 4.0. Use baz instead.');
    }

    public function testRaw(): void
    {
        Deprecated::raw('Hello world.');
        $this->assertDeprecation('Hello world.');
    }

    protected function setUp(): void
    {
        $this->deprecations = [];
        set_error_handler(
            function ($type, $msg, $file, $line): void {
                $this->deprecations[] = $msg;
            },
            E_USER_DEPRECATED
        );
    }

    protected function tearDown(): void
    {
        restore_error_handler();
    }

    private function assertDeprecation($msg): void
    {
        static::assertNotEmpty($this->deprecations, 'No deprecations triggered.');
        static::assertEquals($msg, $this->deprecations[0]);
        $this->deprecations = [];
    }
}
