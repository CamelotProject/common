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

use Camelot\Common\Assert;
use PHPUnit\Framework\TestCase;

class AssertTest extends TestCase
{
    public function testIsArrayAccessible(): void
    {
        Assert::isArrayAccessible([1, 2, 3]);
        Assert::isArrayAccessible(new \ArrayObject([1, 2, 3]));

        $this->addToAssertionCount(2);
    }

    public function testIsArrayAccessibleFailsScalar(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Assert::isArrayAccessible(123);
    }

    public function testIsArrayAccessibleFailsObject(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Assert::isArrayAccessible(new \stdClass());
    }

    public function testIsInstanceOfAny(): void
    {
        Assert::isInstanceOfAny(new \ArrayIterator(), [\Iterator::class, \ArrayAccess::class]); // both
        Assert::isInstanceOfAny(new \Exception(), [\Exception::class, \Countable::class]); // one of

        $this->addToAssertionCount(2);
    }

    public function testIsInstanceOfAnyFailsScalar(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Assert::isInstanceOfAny(new \Exception(), [\ArrayAccess::class, \Countable::class]); // neither
    }

    public function testIsInstanceOfAnyFailsObject(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Assert::isInstanceOfAny([], [\stdClass::class]); // scalar
    }

    public function testIsIterable(): void
    {
        Assert::isIterable([1, 2, 3]);
        Assert::isIterable(new \ArrayObject([1, 2, 3]));

        $this->addToAssertionCount(2);
    }

    public function testIsIterableFailsScalar(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Assert::isIterable(123);
    }

    public function testIsIterableFailsObject(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Assert::isIterable(new \stdClass());
    }

    public function testValueToString(): void
    {
        $this->assertSame('"foo"', Assert::valueToString('foo'));
    }
}
