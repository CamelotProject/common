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

use Camelot\Common\Exception\DumpException;
use Camelot\Common\Exception\ParseException;
use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;
use Stringable;
use function json_last_error_msg;

/**
 * JSON parsing and dumping with error handling.
 */
final class Json
{
    /**
     * Dump JSON easy to read for humans.
     * Shortcut for JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE.
     */
    public const HUMAN = 448;

    /**
     * Dump JSON without escaping slashes or unicode.
     * Shortcut for JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE.
     */
    public const UNESCAPED = 320;

    /**
     * Dump JSON safe for HTML.
     * Shortcut for JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT.
     */
    public const HTML = 15;

    /**
     * Dumps a array/object into a JSON string.
     *
     * @param mixed $data    Data to encode into a formatted JSON string
     * @param int   $options Bitmask of JSON encode options
     *                       (defaults to JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
     * @param int   $depth   Set the maximum depth. Must be greater than zero.
     *
     * @throws DumpException If dumping fails
     */
    public static function dump(mixed $data, int $options = self::UNESCAPED, int $depth = 512): string
    {
        $options = $options | JSON_THROW_ON_ERROR;

        try {
            $json = json_encode($data, $options, $depth);
        } catch (\JsonException $e) {
            throw new DumpException(sprintf('JSON dumping failed: %s', $e->getMessage()), $e->getCode(), $e);
        }

        // If UTF-8 error, try to convert and try again before failing.
        if ($json === false && json_last_error() === JSON_ERROR_UTF8) {
            static::detectAndCleanUtf8($data);

            $json = @json_encode($data, $options, $depth);
        }

        if ($json !== false) {
            return $json;
        }

        throw new DumpException(sprintf('JSON dumping failed: %s', json_last_error_msg()), json_last_error());
    }

    /**
     * Parses JSON into a PHP array.
     *
     * @param null|string|Stringable $json    The JSON string or Stringable object
     * @param int                    $options Bitmask of JSON decode options
     * @param int                    $depth   Recursion depth
     *
     * @throws ParseException If the JSON is not valid
     */
    public static function parse(null|string|Stringable $json, int $options = 0, int $depth = 512): ?array
    {
        if ($json === null) {
            return null;
        }

        $options = $options | JSON_THROW_ON_ERROR;

        try {
            $data = json_decode((string) $json, true, $depth, $options);
        } catch (\JsonException $e) {
            throw new ParseException($e->getMessage(), $e->getCode(), $e);
        }

        if ($data === null && ($json === '' || ($code = json_last_error()) !== JSON_ERROR_NONE)) {
            if (isset($code) && ($code === JSON_ERROR_UTF8 || $code === JSON_ERROR_DEPTH)) {
                throw new ParseException(sprintf('JSON parsing failed: %s', json_last_error_msg()), $code);
            }

            try {
                (new JsonParser())->parse($json);
            } catch (ParsingException $e) {
                throw ParseException::castFromJson($e);
            }
        }

        return $data;
    }

    /** Return whether the given string is JSON. */
    public static function test(mixed $json): bool
    {
        if (!\is_string($json) && !$json instanceof Stringable) {
            return false;
        }

        // Don't call our parse(), because we don't need the extra syntax checking.
        @json_decode((string) $json);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Detect invalid UTF-8 string characters and convert to valid UTF-8.
     *
     * Valid UTF-8 input will be left unmodified, but strings containing
     * invalid UTF-8 code-points will be re-encoded as UTF-8 with an assumed
     * original encoding of ISO-8859-15. This conversion may result in
     * incorrect output if the actual encoding was not ISO-8859-15, but it
     * will be clean UTF-8 output and will not rely on expensive and fragile
     * detection algorithms.
     *
     * Function converts the input in place in the passed variable so that it
     * can be used as a callback for array_walk_recursive.
     *
     * @param mixed $data Input to check and convert if needed
     *
     * @see https://github.com/Seldaek/monolog/pull/683
     */
    private static function detectAndCleanUtf8(mixed &$data): void
    {
        if ($data instanceof \JsonSerializable) {
            $data = $data->jsonSerialize();
        } elseif ($data instanceof \ArrayObject || $data instanceof \ArrayIterator) {
            $data = $data->getArrayCopy();
        } elseif ($data instanceof \stdClass) {
            $data = (array) $data;
        }
        if (\is_array($data)) {
            array_walk_recursive($data, [static::class, 'detectAndCleanUtf8']);

            return;
        }
        if (!\is_string($data) || preg_match('//u', $data)) {
            return;
        }
        $data = preg_replace_callback(
            '/[\x80-\xFF]+/',
            fn ($m) => utf8_encode($m[0]),
            $data
        );
        $data = str_replace(
            ['¤', '¦', '¨', '´', '¸', '¼', '½', '¾'],
            ['€', 'Š', 'š', 'Ž', 'ž', 'Œ', 'œ', 'Ÿ'],
            $data
        );
    }
}
