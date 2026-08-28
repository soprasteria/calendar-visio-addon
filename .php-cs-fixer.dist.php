<?php
/**
 * SPDX-FileCopyrightText: "Copyright 2026, Sopra Steria France"
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->notPath('vendor')
    ->in(__DIR__)
    ->name('*.php');

$config = new PhpCsFixer\Config();
return $config
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR2'                  => true,
        'array_syntax'           => ['syntax' => 'short'],
        'ordered_imports'        => ['sort_algorithm' => 'alpha'],
        'no_unused_imports'      => true,
        'declare_strict_types'   => true,
        'single_blank_line_at_eof' => true,
    ])
    ->setFinder($finder);
