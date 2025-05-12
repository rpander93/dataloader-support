<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
  ->in(__DIR__)
;

return (new PhpCsFixer\Config())
  ->setUsingCache(true)
  ->setRiskyAllowed(true)
  ->setIndent('  ')
  ->setRules([
    '@DoctrineAnnotation' => true,
    '@Symfony' => true,
    '@PSR2' => true,
    'array_syntax' => [
      'syntax' => 'short',
    ],
    'is_null' => true,
    'braces' => false,
    'combine_consecutive_issets' => true,
    'combine_consecutive_unsets' => true,
    'control_structure_braces' => false,
    'curly_braces_position' => [
      'classes_opening_brace' => 'same_line',
      'functions_opening_brace' => 'same_line',
    ],
    'native_function_invocation' => true,
    'no_useless_else' => true,
    'no_useless_return' => true,
    'ordered_imports' => true,
    'php_unit_strict' => true,
    'phpdoc_summary' => false,
    'strict_comparison' => true,
    'declare_strict_types' => true,
  ])
  ->setFinder($finder)
;
