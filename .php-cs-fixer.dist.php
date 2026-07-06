<?php

declare(strict_types=1);
use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$config = (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PHP71Migration:risky' => true,
        '@PHPUnit75Migration:risky' => true,
        '@PSR12:risky' => true,
        '@Symfony' => true,
        'align_multiline_comment' => ['comment_type' => 'phpdocs_only'],
        'array_indentation' => true,
        'blank_line_before_statement' => ['statements' => ['return', 'throw']],
        'declare_strict_types' => false,
        'fully_qualified_strict_types' => ['import_symbols' => true],
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => false,
            'import_functions' => false,
        ],
        'list_syntax' => ['syntax' => 'short'],
        'method_argument_space' => [
            'attribute_placement' => 'ignore',
            'on_multiline' => 'ensure_fully_multiline',
        ],
        'modifier_keywords' => true,
        'native_constant_invocation' => ['strict' => false],
        'native_function_invocation' => [
            'include' => ['@internal'],
            'strict' => false,
        ],
        'no_superfluous_phpdoc_tags' => false,
        'no_useless_return' => true,
        'ordered_imports' => [
            'imports_order' => ['class', 'function', 'const'],
            'sort_algorithm' => 'alpha',
        ],
        'phpdoc_order' => true,
        'phpdoc_to_comment' => false,
        'phpdoc_var_without_name' => false,
        'ternary_to_null_coalescing' => true,
        'void_return' => true,
    ])
    ->setFinder(
        Finder::create()
            ->in(__DIR__.'/src')
            ->in(__DIR__.'/tests')
            ->in(__DIR__.'/examples')
            ->name('*.php')
            ->append([__FILE__])
    )
;

return $config;
