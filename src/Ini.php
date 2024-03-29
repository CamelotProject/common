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

use Camelot\Thrower\Thrower;

/**
 * Wrapper around ini_get()/ini_set().
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class Ini
{
    /** @var ?array [string key => bool editable] */
    private static ?array $keys = null;

    /** Checks whether the given key exists. */
    public static function has(string $key): bool
    {
        if (static::$keys === null) {
            static::readKeys();
        }

        return array_key_exists($key, static::$keys);
    }

    /** Returns the string value of the given key or the given default if it is empty or does not exist. */
    public static function getStr(string $key, ?string $default = null): ?string
    {
        $value = ini_get($key);

        return $value === false || $value === '' ? $default : $value;
    }

    /**
     * Returns the value of the given key filtered to a boolean.
     *
     * If the key does not exist false is returned.
     */
    public static function getBool(string $key): bool
    {
        return filter_var(ini_get($key), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Returns the value of the given key filtered to an int or float.
     *
     * If the key does not exist or the value is empty the given default is returned.
     */
    public static function getNumeric(string $key, float|int $default = null): float|int|null
    {
        $value = ini_get($key);

        return $value === false || $value === '' ? $default : $value + 0;
    }

    /**
     * Parses a bytes string representation value of the given key and returns it as an int.
     *
     * Note that floats are converted to ints before being multiplied by their unit. Thus 5.5M == 5M and 0.5M == 0.
     */
    public static function getBytes(string $key, ?int $default = null): ?int
    {
        $value = ini_get($key);

        if ($value === false || $value === '') {
            return $default;
        }

        if ($value === '-1') {
            return -1;
        }

        $unit = preg_replace('/[^bkmgtpezy]/i', '', $value);
        $size = preg_replace('/[^0-9.]/', '', $value);

        return ((int) $size) * ($unit ? 1024 ** stripos('bkmgtpezy', $unit[0]) : 1);
    }

    /**
     * Set a new value for the given key.
     *
     * @throws \InvalidArgumentException when the value is not scalar or null
     * @throws \RuntimeException         when the key does not exist, it is not editable, or some unknown reason
     */
    public static function set(string $key, int|float|string|bool $value): void
    {
        $iniValue = $value === false ? '0' : (string) $value;

        $result = false;
        $ex = null;

        try {
            $result = Thrower::call('ini_set', $key, $iniValue);
        } catch (\Exception $ex) {
        }

        if ($result === false || $ex !== null) {
            if (!static::has($key)) {
                throw new \RuntimeException(
                    "The ini option '{$key}' does not exist. New ini options cannot be added.",
                    0,
                    $ex
                );
            }
            if (!static::$keys[$key]) {
                throw new \RuntimeException(
                    "Unable to change ini option '{$key}', because it is not editable at runtime.",
                    0,
                    $ex
                );
            }

            $value = Assert::valueToString($value);

            throw new \RuntimeException(sprintf('Unable to change ini option "%s" to %s.', $key, $value), 0, $ex);
        }

        // HHVM sets values w/o error, but the change is not actually applied.
        if (ini_get($key) !== $iniValue) {
            $value = Assert::valueToString($value);

            throw new \RuntimeException(sprintf('Unable to change ini option "%s" to %s.', $key, $value), 0, $ex);
        }
    }

    /** Process all ini options to get list of keys and determine which ones are editable. */
    private static function readKeys(): void
    {
        static::$keys = [];

        foreach (ini_get_all() as $key => $value) {
            static::$keys[$key] = $value['access'] === 1 /* user */ || $value['access'] === 7 /* all */;
        }
    }

    /** @codeCoverageIgnore */
    private function __construct()
    {
    }
}
