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

namespace Camelot\Common\Tests\Fixtures;

use Camelot\Common\Deprecated;

class TestDeprecatedClass
{
    public function __construct($deprecatedClass = false)
    {
        if ($deprecatedClass) {
            Deprecated::method(null, \ArrayObject::class);
        }
    }

    public static function foo(): void
    {
        Deprecated::method(null, \ArrayObject::class);
    }

    public function __call($name, $arguments): void
    {
        Deprecated::method(null, \ArrayObject::class);
    }

    public static function __callStatic($name, $arguments): void
    {
        Deprecated::method(null, \ArrayObject::class);
    }

    public static function getArrayCopy(): void
    {
        Deprecated::method(null, \ArrayObject::class);
    }

    public static function someMethod(): void
    {
        static::deprecated();
    }

    private static function deprecated(): void
    {
        Deprecated::method(null, \ArrayObject::class, 1);
    }
}
