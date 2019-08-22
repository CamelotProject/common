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

class_exists('Camelot\Thrower\Thrower');

@trigger_error(sprintf('Using the "Camelot\Common\Thrower" class is deprecated since version 1.1, use "Camelot\Thrower\Thrower" instead.'), E_USER_DEPRECATED);

if (\false) {
    /** @deprecated since 1.1, use "Camelot\Thrower\Thrower" instead */
    class Thrower extends \Camelot\Thrower\Thrower
    {
    }
}
