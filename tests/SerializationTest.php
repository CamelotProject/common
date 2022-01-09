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
use Camelot\Common\Serialization;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Camelot\Common\Serialization
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 *
 * @internal
 */
final class SerializationTest extends TestCase
{
    public function testDump(): void
    {
        $result = Serialization::dump(new \stdClass());
        static::assertSame(serialize(new \stdClass()), $result);
    }

    public function testDumpInvalid(): void
    {
        if (!\defined('HHVM_VERSION')) {
            $message = "/Error serializing value\. Serialization of 'Closure' is not allowed/";
        } else {
            $message = '/Error serializing value\. Attempted to serialize unserializable builtin class Closure\$Camelot\\\\Common\\\\Tests\\\\SerializationTest::testDumpInvalid;\d+/';
        }
        $this->expectException(DumpException::class);
        $this->expectExceptionMessageMatches($message);

        Serialization::dump(function (): void {});
    }

    public function testParseSimple(): void
    {
        $result = Serialization::parse(serialize(new \stdClass()));
        static::assertInstanceOf(\stdClass::class, $result);
    }

    public function testParseInvalidData(): void
    {
        $this->expectException(\Camelot\Common\Exception\ParseException::class);
        $this->expectExceptionMessage('Error parsing serialized value.');

        Serialization::parse('O:9:"stdClass":0:{}');
    }

    public function testParseClassNotFound(): void
    {
        $this->expectException(\Camelot\Common\Exception\ParseException::class);
        $this->expectExceptionMessage('Error parsing serialized value. Could not find class: ThisClassShouldNotExistsDueToDropBears');

        if (\defined('HHVM_VERSION')) {
            static::markTestSkipped(
                'HHVM has not implemented "unserialize_callback_func", meaning ' .
                '__PHP_Incomplete_Class could be returned at any level and we are not going to look for them.'
            );
        }

        Serialization::parse('O:38:"ThisClassShouldNotExistsDueToDropBears":0:{}');
    }
}
