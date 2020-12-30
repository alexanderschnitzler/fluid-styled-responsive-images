<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Set\ValueObject\SetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::PATHS, [
        __DIR__ . '/Classes',
        __DIR__ . '/Tests',
    ]);

    $parameters->set(Option::PHP_VERSION_FEATURES, '7.4');
    $parameters->set(Option::AUTO_IMPORT_NAMES, true);
    $parameters->set(Option::IMPORT_SHORT_CLASSES, false);
    $parameters->set(Option::IMPORT_DOC_BLOCKS, false);

    $parameters->set(Option::SETS, [
        SetList::PHP_53,
        SetList::PHP_54,
        SetList::PHP_55,
        SetList::PHP_56,
        SetList::PHP_70,
        SetList::PHP_71,
        SetList::PHP_72,
        SetList::PHP_73,
        SetList::PHP_74,
        SetList::PHPUNIT_CODE_QUALITY,
        SetList::PHPUNIT_EXCEPTION,
        SetList::PHPUNIT_INJECTOR,
        SetList::PHPUNIT_MOCK,
        SetList::PHPUNIT_SPECIFIC_METHOD,
        SetList::PHPUNIT_91,
    ]);

    $parameters->set(Option::EXCLUDE_RECTORS, [
        \Rector\Php74\Rector\LNumber\AddLiteralSeparatorToNumberRector::class,
        \Rector\Php73\Rector\BinaryOp\IsCountableRector::class
    ]);
};
