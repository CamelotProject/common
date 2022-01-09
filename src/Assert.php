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

namespace Camelot\Common;

/**
 * @method static void nullOrIsArrayAccessible($value, $message = '')
 * @method static void nullOrInstanceOfAny($value, array $classes, $message = '')
 * @method static void nullOrIsIterable($value, $message = '')
 * @method static void allIsArrayAccessible(array $values, $message = '')
 * @method static void allIsInstanceOfAny(array $values, array $classes, $message = '')
 * @method static void allIsIterable(array $values, $message = '')
 */
class Assert extends \Webmozart\Assert\Assert
{
    public static function isArrayAccessible($value, $message = ''): void
    {
        if (!\is_array($value) && !($value instanceof \ArrayAccess)) {
            static::reportInvalidArgument(sprintf(
                $message ?: 'Expected an array accessible. Got: %s',
                static::typeToString($value)
            ));
        }
    }

    public static function isInstanceOfAny($value, array $classes, $message = ''): void
    {
        foreach ($classes as $class) {
            if ($value instanceof $class) {
                return;
            }
        }

        static::reportInvalidArgument(sprintf(
            $message ?: 'Expected an instance of any of %2$s. Got: %s',
            static::typeToString($value),
            implode(', ', array_map(['static', 'valueToString'], $classes))
        ));
    }

    public static function isIterable($value, $message = ''): void
    {
        if (!is_iterable($value)) {
            static::reportInvalidArgument(sprintf(
                $message ?: 'Expected an iterable. Got: %s',
                static::typeToString($value)
            ));
        }
    }

    /**
     * Convert the given value to a string representation.
     *
     * This returns the class name of objects instead of `object`.
     * This returns quoted string values instead of `string`.
     * This returns `false` or `true` instead of `boolean`.
     */
    public static function valueToString(mixed $value): string
    {
        return parent::valueToString($value);
    }
}
