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

use ArrayIterator;
use ArrayObject;
use Camelot\Common\Exception\DumpException;
use Camelot\Common\Exception\ParseException;
use Camelot\Common\Json;
use Camelot\Common\Tests\Fixtures\TestJsonable;
use Camelot\Common\Tests\Fixtures\TestStringable;
use PHPUnit\Framework\TestCase;
use function function_exists;
use const JSON_ERROR_SYNTAX;

/**
 * @covers \Camelot\Common\Json
 *
 * @internal
 */
final class JsonTest extends TestCase
{
    public function testParseNull(): void
    {
        static::assertNull(Json::parse(null));
    }

    public function testParseValid(): void
    {
        static::assertEquals(['foo' => 'bar'], Json::parse('{"foo": "bar"}'));
    }

    public function testParseErrorEmptyString(): void
    {
        $this->expectParseException('', JSON_ERROR_SYNTAX);
    }

    public function testParseErrorObjectEmptyString(): void
    {
        $this->expectParseException(new TestStringable(''), JSON_ERROR_SYNTAX);
    }

    public function testParseErrorDetectExtraComma(): void
    {
        $json = '{
        "foo": "bar",
}';
        $this->expectParseException($json, JSON_ERROR_SYNTAX);
    }

    public function testParseErrorDetectExtraCommaInArray(): void
    {
        $json = '{
        "foo": [
            "bar",
        ]
}';
        $this->expectParseException($json, JSON_ERROR_SYNTAX);
    }

    public function testParseErrorDetectUnescapedBackslash(): void
    {
        $json = '{
        "fo\o": "bar"
}';
        $this->expectParseException($json, JSON_ERROR_SYNTAX);
    }

    public function testParseErrorSkipsEscapedBackslash(): void
    {
        $json = '{
        "fo\\\\o": "bar"
        "a": "b"
}';
        $this->expectParseException($json, JSON_ERROR_SYNTAX);
    }

    public function testParseErrorDetectMissingQuotes(): void
    {
        $json = '{
        foo: "bar"
}';
        $this->expectParseException($json, JSON_ERROR_SYNTAX);
    }

    public function testParseErrorDetectArrayAsHash(): void
    {
        $json = '{
        "foo": ["bar": "baz"]
}';
        $this->expectParseException($json, JSON_ERROR_SYNTAX);
    }

    public function testParseErrorDetectMissingComma(): void
    {
        $json = '{
        "foo": "bar"
        "bar": "foo"
}';
        $this->expectParseException($json, JSON_ERROR_SYNTAX);
    }

    public function testParseErrorDetectMissingCommaMultiline(): void
    {
        $json = '{
        "foo": "barbar"
        "bar": "foo"
}';
        $this->expectParseException($json, JSON_ERROR_SYNTAX);
    }

    public function testParseErrorDetectMissingColon(): void
    {
        $json = '{
        "foo": "bar",
        "bar" "foo"
}';
        $this->expectParseException($json, JSON_ERROR_SYNTAX);
    }

    public function testParseErrorUtf8(): void
    {
        $json = "{\"message\": \"\xA4\xA6\xA8\xB4\xB8\xBC\xBD\xBE\"}";
        $this->expectParseException($json, JSON_ERROR_UTF8, 'Malformed UTF-8 characters, possibly incorrectly encoded');
    }

    private function expectParseException(mixed $json, int $code, string $message = 'Syntax error'): void
    {
        try {
            $result = Json::parse($json);
            static::fail(sprintf(
                "Parsing should have failed but didn't.\nExpected:\nFor:\n\"%s\"\nGot:\n\"%s\"",
                $json,
                var_export($result, true)
            ));
        } catch (ParseException $e) {
            static::assertSame($code, $e->getCode());
            static::assertStringStartsWith($message, $e->getMessage());
        }
    }

    public function testParseErrorDepth(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Maximum stack depth exceeded');

        Json::parse('[[["hi"]]]', 0, 1);
    }

    public function testDumpSimpleJsonString(): void
    {
        $data = ['name' => 'composer/composer'];
        $json = '{
    "name": "composer/composer"
}';
        $this->assertJsonFormat($json, $data);
    }

    public function testDumpTrailingBackslash(): void
    {
        $data = ['Metadata\\' => 'src/'];
        $json = '{
    "Metadata\\\\": "src/"
}';
        $this->assertJsonFormat($json, $data);
    }

    public function testDumpEscape(): void
    {
        $data = ['Metadata\\"' => 'src/'];
        $json = '{
    "Metadata\\\\\\"": "src/"
}';
        $this->assertJsonFormat($json, $data);
    }

    public function testDumpUnicode(): void
    {
        if (!function_exists('mb_convert_encoding')) {
            static::markTestSkipped('Test requires the mbstring extension');
        }

        $data = ['Žluťoučký " kůň' => 'úpěl ďábelské ódy za €'];
        $json = '{
    "Žluťoučký \" kůň": "úpěl ďábelské ódy za €"
}';
        $this->assertJsonFormat($json, $data);
    }

    public function testDumpOnlyUnicode(): void
    {
        if (!function_exists('mb_convert_encoding')) {
            static::markTestSkipped('Test requires the mbstring extension');
        }

        $data = '\\/ƌ';
        $this->assertJsonFormat('"\\\\\\/ƌ"', $data, JSON_UNESCAPED_UNICODE);
    }

    public function testDumpEscapedSlashes(): void
    {
        $data = '\\/foo';
        $this->assertJsonFormat('"\\\\\\/foo"', $data, 0);
    }

    public function testDumpEscapedBackslashes(): void
    {
        $data = 'a\\b';
        $this->assertJsonFormat('"a\\\\b"', $data, 0);
    }

    public function testDumpEscapedUnicode(): void
    {
        $data = 'ƌ';
        $this->assertJsonFormat('"\\u018c"', $data, 0);
    }

    public function testDumpEscapesLineTerminators(): void
    {
        $this->assertJsonFormat('"JS\\u2029ON ro\\u2028cks"', 'JS ON ro cks', JSON_UNESCAPED_UNICODE);
        $this->assertJsonFormat('"JS\\u2029ON ro\\u2028cks"', 'JS ON ro cks', JSON_UNESCAPED_UNICODE);
        $this->assertJsonFormat('"JS ON ro cks"', 'JS ON ro cks', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_LINE_TERMINATORS);
    }

    public function testDumpConvertsInvalidEncodingAsLatin9(): void
    {
        static::markTestIncomplete();

        $data = "\xA4\xA6\xA8\xB4\xB8\xBC\xBD\xBE";
        $this->assertJsonFormat('"€ŠšŽžŒœŸ"', $data);

        $data = [
            'foo' => new TestJsonable([
                new ArrayObject(["\xA4"]),
                new ArrayIterator(["\xA6"]),
                (object) ["\xA8"],
            ]),
            'bar' => 4,
        ];
        $this->assertJsonFormat('{"foo":[["€"],["Š"],["š"]],"bar":4}', $data, JSON_UNESCAPED_UNICODE);
    }

    public function testDumpThrowsCorrectErrorAfterFixingUtf8Error(): void
    {
        try {
            Json::dump([["\xA4"]], 448, 1);
        } catch (DumpException $e) {
            if ($e->getCode() !== JSON_ERROR_UTF8) {
                static::fail('Should have thrown exception with code for max depth');
            }
            static::assertSame('JSON dumping failed: Malformed UTF-8 characters, possibly incorrectly encoded', $e->getMessage());

            return;
        }

        static::fail('Should have thrown ' . DumpException::class);
    }

    private function assertJsonFormat(?string $json, mixed $data, int $options = Json::HUMAN): void
    {
        static::assertEquals($json, Json::dump($data, $options));
    }

    public function testTest(): void
    {
        static::assertFalse(Json::test(null));
        static::assertFalse(Json::test(123));
        static::assertFalse(Json::test(''));
        static::assertFalse(Json::test(new TestStringable('')));
        static::assertTrue(Json::test('{}'));
        static::assertTrue(Json::test(new TestStringable('{}')));

        static::assertFalse(Json::test('{"foo": "bar",}'), 'Invalid JSON should return false');
    }
}
