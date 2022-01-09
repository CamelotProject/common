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

use Camelot\Common\Exception\DumpException;
use Camelot\Common\Exception\ParseException;
use Camelot\Common\Json;
use Camelot\Common\Tests\Fixtures\JsonMocker;
use Camelot\Common\Tests\Fixtures\TestJsonable;
use Camelot\Common\Tests\Fixtures\TestStringable;
use PHPUnit\Framework\TestCase;

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
        $this->expectParseException('', 0);
    }

    public function testParseErrorObjectEmptyString(): void
    {
        $this->expectParseException(new TestStringable(''), 0);
    }

    public function testParseErrorDetectExtraComma(): void
    {
        $json = '{
        "foo": "bar",
}';
        $this->expectParseException($json, 2, 'It appears you have an extra trailing comma');
    }

    public function testParseErrorDetectExtraCommaInArray(): void
    {
        $json = '{
        "foo": [
            "bar",
        ]
}';
        $this->expectParseException($json, 3, 'It appears you have an extra trailing comma');
    }

    public function testParseErrorDetectUnescapedBackslash(): void
    {
        $json = '{
        "fo\o": "bar"
}';
        $this->expectParseException($json, 1, 'Invalid string, it appears you have an unescaped backslash');
    }

    public function testParseErrorSkipsEscapedBackslash(): void
    {
        $json = '{
        "fo\\\\o": "bar"
        "a": "b"
}';
        $this->expectParseException($json, 2);
    }

    public function testParseErrorDetectMissingQuotes(): void
    {
        $json = '{
        foo: "bar"
}';
        $this->expectParseException($json, 1);
    }

    public function testParseErrorDetectArrayAsHash(): void
    {
        $json = '{
        "foo": ["bar": "baz"]
}';
        $this->expectParseException($json, 2);
    }

    public function testParseErrorDetectMissingComma(): void
    {
        $json = '{
        "foo": "bar"
        "bar": "foo"
}';
        $this->expectParseException($json, 2);
    }

    public function testParseErrorDetectMissingCommaMultiline(): void
    {
        $json = '{
        "foo": "barbar"
        "bar": "foo"
}';
        $this->expectParseException($json, 2);
    }

    public function testParseErrorDetectMissingColon(): void
    {
        $json = '{
        "foo": "bar",
        "bar" "foo"
}';
        $this->expectParseException($json, 3);
    }

    public function testParseErrorUtf8(): void
    {
        $json = "{\"message\": \"\xA4\xA6\xA8\xB4\xB8\xBC\xBD\xBE\"}";
        $this->expectParseException($json, -1, 'Malformed UTF-8 characters, possibly incorrectly encoded', JSON_ERROR_UTF8);
    }

    private function expectParseException($json, $line, $text = null, $code = JSON_ERROR_SYNTAX): void
    {
        try {
            $result = Json::parse($json);
            static::fail(sprintf(
                "Parsing should have failed but didn't.\nExpected:\n\"%s\"\nFor:\n\"%s\"\nGot:\n\"%s\"",
                $text,
                $json,
                var_export($result, true)
            ));
        } catch (ParseException $e) {
            static::assertSame($line, $e->getParsedLine());
            static::assertSame($code, $e->getCode());
            $actualMsg = $e->getMessage();
            static::assertStringStartsWith('JSON parsing failed: ', $actualMsg);
            $actualMsg = substr($actualMsg, 21);
            if ($text) {
                static::assertStringStartsWith($text, $actualMsg);
            }
        }
    }

    public function testParseErrorDepth(): void
    {
        $this->expectException(\Camelot\Common\Exception\ParseException::class);
        $this->expectExceptionMessage('JSON parsing failed: Maximum stack depth exceeded');

        Json::parse('[[["hi"]]]', 0, 1);
    }

    public function testParseExceptionGettersSetters(): void
    {
        $ex = new ParseException('Uh oh.');
        $ex->setRawMessage('Whoops.');
        $ex->setParsedLine(5);
        $ex->setSnippet('foo bar');

        static::assertEquals('Whoops.', $ex->getRawMessage());
        static::assertEquals(5, $ex->getParsedLine());
        static::assertEquals('foo bar', $ex->getSnippet());
        static::assertEquals('Whoops at line 5 (near "foo bar").', $ex->getMessage());
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
        if (!\function_exists('mb_convert_encoding')) {
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
        if (!\function_exists('mb_convert_encoding')) {
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
        $data = "\xA4\xA6\xA8\xB4\xB8\xBC\xBD\xBE";
        $this->assertJsonFormat('"€ŠšŽžŒœŸ"', $data);

        $data = [
            'foo' => new TestJsonable([
                new \ArrayObject(["\xA4"]),
                new \ArrayIterator(["\xA6"]),
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
            if ($e->getCode() !== JSON_ERROR_DEPTH) {
                static::fail('Should have thrown exception with code for max depth');
            }
            static::assertSame('JSON dumping failed: Maximum stack depth exceeded', $e->getMessage());

            return;
        }

        static::fail('Should have thrown ' . DumpException::class);
    }

    private function assertJsonFormat($json, $data, int $options = Json::HUMAN): void
    {
        static::assertEquals($json, Json::dump($data, $options));
    }

    public function testDumpFail(): void
    {
        $mocker = JsonMocker::instance();
        $mocker->setEncoder(fn () => false);
        $mocker->setLastMessageGetter(fn () => 'Unknown error');

        $this->expectException(DumpException::class);
        $this->expectExceptionMessage('JSON dumping failed: Unknown error');

        try {
            Json::dump('');
        } finally {
            $mocker->reset();
        }
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
