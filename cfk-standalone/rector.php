<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector;
use Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/includes',
        __DIR__ . '/pages',
        __DIR__ . '/admin',
        __DIR__ . '/cron',
    ])
    ->withSkip([
        __DIR__ . '/includes/functions.php', // Skip for now - has many global functions
        __DIR__ . '/vendor',
    ])
    ->withPhpSets(
        php82: true
    )
    ->withSets([
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::TYPE_DECLARATION,
        LevelSetList::UP_TO_PHP_82,
    ])
    ->withRules([
        InlineConstructorDefaultToPropertyRector::class,
    ])
    ->withImportNames(
        importNames: true,
        importDocBlockNames: false,
        removeUnusedImports: true
    );
