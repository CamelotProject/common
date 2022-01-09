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

use Stringable;

class TestStringable
{
    private string|Stringable $string;

    public function __construct(string|Stringable $string)
    {
        $this->string = $string;
    }

    public function __toString()
    {
        return $this->string;
    }
}
