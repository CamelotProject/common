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
    /** @var int */
    private $parsedLine;
    /** @var null|string */
    private $snippet;
    /** @var string */
    private $rawMessage;

    public function __construct(string $message, int $parsedLine = -1, ?string $snippet = null, int $code = 0, Throwable $previous = null)
    {
        $this->parsedLine = $parsedLine;
        $this->snippet = $snippet;
        $this->rawMessage = $message;

        $this->updateRepr();

        parent::__construct($this->message, $code, $previous);
    }

    /** Casts JsonLint ParseException to ours. */
    public static function castFromJson(JsonParseException $exception): self
    {
        $details = $exception->getDetails();
        $message = $exception->getMessage();
        $line = $details['line'] ?? -1;
        $snippet = null;

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

        return new static($message, $line, $snippet, JSON_ERROR_SYNTAX);
    }

    /** Gets the message without line number and snippet. */
    public function getRawMessage(): string
    {
        return $this->rawMessage;
    }

    /**
     * Sets the message.
     *
     * Don't include line number and snippet in this as they will be merged separately.
     */
    public function setRawMessage(string $rawMessage): void
    {
        $this->rawMessage = $rawMessage;

        $this->updateRepr();
    }

    /** Gets the line where the error occurred. */
    public function getParsedLine(): int
    {
        return $this->parsedLine;
    }

    /** Sets the line where the error occurred. */
    public function setParsedLine(int $parsedLine): void
    {
        $this->parsedLine = $parsedLine;

        $this->updateRepr();
    }

    /** Gets the snippet of code near the error. */
    public function getSnippet(): string
    {
        return $this->snippet;
    }

    /** Sets the snippet of code near the error. */
    public function setSnippet(string $snippet): void
    {
        $this->snippet = $snippet;

        $this->updateRepr();
    }

    /** Sets the exception message by joining the raw message, parsed line, and snippet. */
    private function updateRepr(): void
    {
        $this->message = $this->rawMessage;

        $dot = false;
        if (substr($this->message, -1) === '.') {
            $this->message = substr($this->message, 0, -1);
            $dot = true;
        }

        if ($this->parsedLine >= 0) {
            $this->message .= sprintf(' at line %d', $this->parsedLine);
        }

        if ($this->snippet) {
            $this->message .= sprintf(' (near "%s")', $this->snippet);
        }

        if ($dot) {
            $this->message .= '.';
        }
    }
}
