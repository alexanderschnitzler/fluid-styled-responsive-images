<?php
/** @var string $_EXTKEY */

$EM_CONF[$_EXTKEY] = [
    'title' => 'Fluid Styled Responsive Images',
    'description' => 'Enables creation of responsive images for fluid styled content elements.',
    'category' => 'fe',
    'version' => '9.5.0',
    'state' => 'alpha',
    'uploadfolder' => false,
    'clearcacheonload' => true,
    'author' => 'Alexander Schnitzler',
    'author_email' => 'git@alexanderschnitzler.de',
    'constraints' => [
        'depends' => [
            'php' => '7.2.0-7.4.99',
            'typo3' => '9.5.0-9.5.99',
            'fluid_styled_content' => '9.5.0-9.5.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
