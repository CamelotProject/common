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

use Camelot\Common\Str;
use PHPUnit\Framework\TestCase;

class StrTest extends TestCase
{
    public function testReplaceFirst(): void
    {
        $this->assertSame(
            'HelloFooHelloGoodbye',
            Str::replaceFirst('HelloGoodbyeHelloGoodbye', 'Goodbye', 'Foo')
        );
        $this->assertSame(
            'HelloFooHelloGoodbye',
            Str::replaceFirst('HelloGOODBYEHelloGoodbye', 'Goodbye', 'Foo', false)
        );

        $this->assertSame(
            'HelloGoodbye',
            Str::replaceFirst('HelloGoodbye', 'red', 'blue')
        );
    }

    public function testReplaceLast(): void
    {
        $this->assertSame(
            'HelloGoodbyeFooGoodbye',
            Str::replaceLast('HelloGoodbyeHelloGoodbye', 'Hello', 'Foo')
        );
        $this->assertSame(
            'HelloGoodbyeFooGoodbye',
            Str::replaceLast('HelloGoodbyeHELLOGoodbye', 'Hello', 'Foo', false)
        );

        $this->assertSame(
            'HelloGoodbye',
            Str::replaceLast('HelloGoodbye', 'red', 'blue')
        );
    }

    public function testRemoveFirst(): void
    {
        $this->assertSame('HelloHelloGoodbye', Str::removeFirst('HelloGoodbyeHelloGoodbye', 'Goodbye'));
        $this->assertSame('HelloHelloGoodbye', Str::removeFirst('HelloGOODBYEHelloGoodbye', 'Goodbye', false));

        $this->assertSame('abc', Str::removeFirst('abc', 'zxc'));
    }

    public function testRemoveLast(): void
    {
        $this->assertSame('HelloGoodbyeGoodbye', Str::removeLast('HelloGoodbyeHelloGoodbye', 'Hello'));
        $this->assertSame('HelloGoodbyeGoodbye', Str::removeLast('HelloGoodbyeHELLOGoodbye', 'Hello', false));

        $this->assertSame('abc', Str::removeLast('abc', 'zxc'));
    }

    public function testSplitFirst(): void
    {
        $this->assertSame('herp', Str::splitFirst('herp derp foo bar', ' '));
        $this->assertSame('herp derp', Str::splitFirst('herp derp', ','));
    }

    public function testSplitFirstEmptyDelimiter(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->assertFalse(Str::splitFirst('herp derp', ''));
    }

    public function testSplitLast(): void
    {
        $this->assertSame('bar', Str::splitLast('herp derp foo bar', ' '));
        $this->assertSame('herp derp', Str::splitLast('herp derp', ','));
    }

    public function testSplitLastEmptyDelimiter(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->assertFalse(Str::splitLast('herp derp', ''));
    }

    public function testEndsWith(): void
    {
        $this->assertTrue(Str::endsWith('FooBar', 'Bar'));
        $this->assertTrue(Str::endsWith('FooBar', 'bar', false));
        $this->assertFalse(Str::endsWith('FooBar', 'Foo'));
    }

    public function testClassName(): void
    {
        $this->assertSame('StrTest', Str::className($this));
        $this->assertSame('StrTest', Str::className(static::class));
    }

    public function testCamelCase(): void
    {
        $this->assertSame('FooBar', Str::camelCase('fooBar'));
        $this->assertSame('FooBar', Str::camelCase('FooBar'));
        $this->assertSame('FooBar', Str::camelCase('foo_bar'));

        $this->assertSame('fooBar', Str::camelCase('foo_bar', true));
    }

    public function testHumanize(): void
    {
        $this->assertSame('Foo bar', Str::humanize('fooBar'));
        $this->assertSame('Foo bar', Str::humanize('FooBar'));
        $this->assertSame('Foo bar', Str::humanize('foo_bar'));
    }

    public function testSnakeCase(): void
    {
        $this->assertSame('foo_bar', Str::snakeCase('fooBar'));
        $this->assertSame('foo_bar', Str::snakeCase('FooBar'));
        $this->assertSame('foo_bar', Str::snakeCase('foo_bar'));
    }
}
