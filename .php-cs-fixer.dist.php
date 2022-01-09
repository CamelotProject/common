<?php

return Camelot\CsFixer\Config::create()
    ->addRules(
        Camelot\CsFixer\Rules::create()
            ->risky()
            ->php81()
            ->phpUnit84()
    )
    ->addRules([
        '@PhpCsFixer:risky' => true,
        'header_comment' => [
            'header' => <<<'EOD'
This file is part of a Camelot Project package.

(c) The Camelot Project

For the full copyright and license information, please view the LICENSE file
that was distributed with this source code.
EOD
        ],
    ])
    ->in('src')
    ->in('tests')
;
