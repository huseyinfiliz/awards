<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

return (new Config())
    ->setRiskyAllowed(false)
    ->setRules([
        '@PSR12' => true,
        'concat_space' => ['spacing' => 'none'],
        'array_indentation' => true,
        'ordered_imports' => true,
        'no_unused_imports' => false,
        'binary_operator_spaces' => ['operators' => ['=>' => 'align_single_space_minimal']],
        'blank_line_before_statement' => ['statements' => ['return', 'throw', 'try']],
    ])
    ->setFinder(
        Finder::create()
            ->in([__DIR__.'/src', __DIR__.'/tests', __DIR__.'/migrations'])
            ->exclude(['integration/tmp', '.phpunit.cache'])
            ->append([__DIR__.'/extend.php'])
    );
