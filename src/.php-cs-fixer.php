<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in(__DIR__)
    ->exclude([
        'bootstrap',
        'storage',
        'vendor',
        'database/migrations',
        'database/seeders',
        'database/data',
    ])
    ->name('*.php')
    ->name('_ide_helper')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new Config())->setRules([
    '@PSR2' => true,
    'array_syntax' => ['syntax' => 'short'],
    'binary_operator_spaces' => false,
    'blank_line_after_opening_tag' => true,
    'blank_line_before_statement' => [
        'statements' => [
            'if',
            'switch',
            'return',
            'continue',
            'declare',
            'throw',
            'try',
            'for',
            'foreach',
        ],
    ],
    'braces' => ['allow_single_line_closure' => true],
    'class_attributes_separation' => ['elements' => ['method' => 'one']],
    'compact_nullable_typehint' => true,
    'concat_space' => ['spacing' => 'one'],
    'declare_equal_normalize' => ['space' => 'none'],
    'elseif' => true,
    'function_typehint_space' => true,
    'global_namespace_import' => ['import_classes' => true],
    'linebreak_after_opening_tag' => true,
    'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
    'method_chaining_indentation' => true,
    'new_with_braces' => true,
    'no_blank_lines_after_phpdoc' => true,
    'no_empty_statement' => true,
    'no_extra_blank_lines' => ['tokens' => ['return']],
    'no_leading_import_slash' => true,
    'no_leading_namespace_whitespace' => true,
    'no_unused_imports' => true,
    'no_useless_else' => true,
    'no_useless_return' => true,
    'no_whitespace_in_blank_line' => true,
    'ordered_imports' => [
        'imports_order' => [
            'class',
            'function',
            'const',
        ],
        'sort_algorithm' => 'alpha',
    ],
    'phpdoc_add_missing_param_annotation' => true,
    'return_type_declaration' => true,
    'single_trait_insert_per_statement' => true,
    'ternary_to_null_coalescing' => true,
    //can add , on with statements 'trailing_comma_in_multiline_array' => true
])
    ->setFinder($finder);
