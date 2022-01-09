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

namespace Camelot\Common\Exception;

use Seld\JsonLint\ParsingException as JsonParseException;
use Throwable;

class ParseException extends \RuntimeException
{
    public function __construct(string $message, int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /** Casts JsonLint ParseException to ours. */
    public static function castFromJson(JsonParseException $exception): self
    {
        $details = $exception->getDetails();
        $message = $exception->getMessage();

        if (preg_match("/^Parse error on line (\\d+):\n(.+)\n.+\n(.+)$/", $message, $matches)) {
            $line = (int) $matches[1];
            $snippet = $matches[2];
            $message = $matches[3];
        }

        $trailingComma = false;
        $pos = strpos($message, ' - It appears you have an extra trailing comma');
        if ($pos > 0) {
            $message = substr($message, 0, $pos);
            $trailingComma = true;
        }

        if (str_starts_with($message, 'Expected') && $trailingComma) {
            $message = 'It appears you have an extra trailing comma';
        }

        $message = 'JSON parsing failed: ' . $message;

        return new static($message, JSON_ERROR_SYNTAX);
    }
}
